<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
require_once __DIR__ . '/../libraries/Qr_service.php';
require_once __DIR__ . '/../libraries/Fine_service.php';
require_once __DIR__ . '/../libraries/Cloudinary_service.php';

class AdminController extends Controller {
    private $me;

    public function __construct() {
        parent::__construct();
        $this->call->model('EventModel');
        $this->call->model('RegistrationModel');
        $this->me = array(
            'id'   => $this->session->userdata('user_id'),
            'name' => $this->session->userdata('full_name'),
            'role' => $this->session->userdata('role')
        );
        if (!in_array($this->me['role'], array('admin', 'organizer'))) redirect('login');
    }

    public function dashboard() {
    $only = $this->me['role'] === 'organizer' ? $this->me['id'] : null;
    $this->call->view('admin/dashboard', array(
        'me' => $this->me,
        'events' => $this->EventModel->admin_list($only),
        'message' => $this->session->flashdata('message')
    ));
    }

    public function set_status($id) {
        if ($this->me['role'] !== 'admin') redirect('admin');
        $status = $this->io->post('status');
        if (in_array($status, array('approved', 'rejected', 'cancelled', 'pending'))) {
            $this->EventModel->set_status($id, $status);
        }
        redirect('admin');
    }

    public function attendees($id) {
    $event = $this->EventModel->find($id);
    if (!$event) { show_404(); return; }
    $rows = $this->RegistrationModel->attendees($id);
    $total_fines = 0;
    foreach ($rows as &$r) {
        $r['summary'] = Fine_service::summarize($event, $r['scans']);
        $total_fines += $r['summary']['fine'];
    }
    unset($r);
    $this->call->view('admin/attendees', array(
        'me' => $this->me, 'event' => $event, 'rows' => $rows, 'total_fines' => $total_fines
    ));
    }

    public function scanner($id) {
        $event = $this->EventModel->find($id);
        header('Permissions-Policy: camera=(self)');   
        if (!$event) { show_404(); return; }
        $this->call->view('admin/scanner', array('me' => $this->me, 'event' => $event));
    }

    public function checkin() {
    header('Content-Type: application/json');
    $token    = trim($this->io->post('token'));
    $event_id = (int) $this->io->post('event_id');
    $slot     = $this->io->post('slot');

    if (!isset(Fine_service::$slots[$slot])) {
        echo json_encode(array('ok' => false, 'message' => 'Choose a session first.')); return;
    }
    $reg = $token ? $this->RegistrationModel->find_by_hash(Qr_service::token_hash($token)) : null;
    if (!$reg) { echo json_encode(array('ok' => false, 'message' => 'Invalid QR code.')); return; }
    if ($event_id && (int) $reg['event_id'] !== $event_id) {
        echo json_encode(array('ok' => false, 'message' => 'This pass is for another event: ' . $reg['title'])); return;
    }
    if ($reg['event_status'] === 'cancelled') {
    echo json_encode(array('ok' => false, 'message' => 'This event is cancelled.')); return;
    }
    $label = Fine_service::$slots[$slot];
    if ($this->RegistrationModel->has_scan($reg['id'], $slot)) {
        echo json_encode(array('ok' => false, 'message' => $reg['full_name'] . ' already has ' . $label . '.')); return;
    }
    $this->RegistrationModel->mark_present($reg['id'], $slot, $this->me['id']);
    echo json_encode(array('ok' => true, 'message' => $label . ' recorded: ' . $reg['full_name']));
    }

   
    private function can_manage($event) {
    return $this->me['role'] === 'admin' || (int) $event['created_by'] === (int) $this->me['id'];
    }

    public function edit($id) {
    $event = $this->EventModel->find($id);
    if (!$event) { show_404(); return; }
    if (!$this->can_manage($event)) redirect('admin');
    $this->call->view('admin/edit_event', array(
        'me' => $this->me, 'event' => $event,
        'orgs' => $this->EventModel->organizations(), 'error' => null
    ));
    }

    public function update($id) {
    $event = $this->EventModel->find($id);
    if (!$event) { show_404(); return; }
    if (!$this->can_manage($event)) redirect('admin');

    $start = strtotime($this->io->post('start_at'));
    $end   = strtotime($this->io->post('end_at'));
    $cap   = (int) $this->io->post('capacity') ?: null;

    $error = null;
    if (!$start || !$end || $end <= $start) {
        $error = 'End time must be after the start time.';
    } elseif ($cap && $cap < (int) $event['registered_count']) {
        $error = 'Capacity cannot be lower than the ' . (int) $event['registered_count'] . ' students already registered.';
    }
    if ($error) {
        $this->call->view('admin/edit_event', array(
            'me' => $this->me, 'event' => $event,
            'orgs' => $this->EventModel->organizations(), 'error' => $error
        ));
        return;
    }

    $poster = $event['poster_url'];  
    if (!empty($_FILES['poster']['tmp_name']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK
        && $_FILES['poster']['size'] < 5 * 1024 * 1024 && @getimagesize($_FILES['poster']['tmp_name'])) {
        $new = Cloudinary_service::upload($_FILES['poster']['tmp_name']);
        if ($new) $poster = $new;
    }

    $fine = $this->io->post('fine_per_session');
    $fine = ($fine === null || $fine === '') ? 25 : max(0, (float) $fine);

    $this->EventModel->update_event($id, array(
        'organization_id'  => (int) $this->io->post('organization_id'),
        'title'            => trim($this->io->post('title')),
        'description'      => $this->io->post('description'),
        'location'         => trim($this->io->post('location')),
        'start_at'         => date('Y-m-d H:i:s', $start),
        'end_at'           => date('Y-m-d H:i:s', $end),
        'capacity'         => $cap,
        'poster_url'       => $poster,
        'fine_per_session' => $fine
    ));
    $this->session->set_flashdata('message', 'Event updated.');
    redirect('admin');
    }

    public function cancel($id) {
    $event = $this->EventModel->find($id);
    
    if (!$event) { show_404(); return; }
    if (!$this->can_manage($event)) redirect('admin');

    if (!in_array($event['status'], array('approved', 'pending'))) {
        $msg = 'Only approved or pending events can be cancelled.';
    } else {
        $this->EventModel->set_status($id, 'cancelled');
        $msg = '"' . $event['title'] . '" was cancelled. Registrations are kept, and you can reopen it any time.';
    }
    $this->session->set_flashdata('message', $msg);
    redirect('admin');
    }

public function reopen($id) {
    $event = $this->EventModel->find($id);
    if (!$event) { show_404(); return; }
    if (!$this->can_manage($event)) redirect('admin');

    if ($event['status'] !== 'cancelled') {
        $msg = 'Only cancelled events can be reopened.';
    } else {
        // Admins reopen straight to approved; organizers need admin approval again
        $new = $this->me['role'] === 'admin' ? 'approved' : 'pending';
        $this->EventModel->set_status($id, $new);
        $msg = '"' . $event['title'] . '" was reopened'
             . ($new === 'pending' ? ' and is waiting for admin approval.' : ' and is live again.');
    }
    $this->session->set_flashdata('message', $msg);
    redirect('admin');
}
    public function delete_permanently($id) {
    if ($this->me['role'] !== 'admin') redirect('admin');   // admins only
    $event = $this->EventModel->find($id);
    if (!$event) { show_404(); return; }

    // Server-side check: the admin must have typed DELETE in the confirmation box
    if ($this->io->post('typed') !== 'DELETE') {
        $this->session->set_flashdata('message', 'Nothing was deleted: the confirmation text did not match.');
        redirect('admin');
    }

    $this->EventModel->delete_with_records($id);
    $this->session->set_flashdata('message',
        'Permanently deleted "' . $event['title'] . '" with ' . (int) $event['registered_count'] . ' registrations.');
    redirect('admin');
    }
}