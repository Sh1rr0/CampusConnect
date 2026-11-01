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
        $this->call->model('UserModel');         
        $this->call->model('OrganizationModel');
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
    $event = $this->EventModel->find($id);
    if (!$event) { show_404(); return; }

    $status = $this->io->post('status');
    $note   = mb_substr(trim($this->io->post('note')), 0, 255);

    if ($event['status'] !== 'pending' || !in_array($status, array('approved', 'rejected'))) {
        $this->session->set_flashdata('message', 'This event is not waiting for review.');
        redirect('admin');
    }
    if ($status === 'rejected' && mb_strlen($note) < 5) {
        $this->session->set_flashdata('error', 'Write a reason (at least 5 characters) so the organization knows what to fix.');
        redirect('admin/events/' . $id . '/review');
    }

    $this->EventModel->update_event($id, array(
        'status' => $status, 'review_note' => $status === 'rejected' ? $note : null
    ));
    $this->session->set_flashdata('message', '"' . $event['title'] . '" was ' . $status . '.');
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

    if ($this->me['role'] === 'organizer' && $event['status'] === 'rejected') {
    $this->EventModel->update_event($id, array('status' => 'pending', 'review_note' => null));
    }

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

    public function review($id) {
    if ($this->me['role'] !== 'admin') redirect('admin');
    $event = $this->EventModel->find($id);
    if (!$event) { show_404(); return; }
    $this->call->view('admin/review', array(
        'me' => $this->me, 'event' => $event, 'error' => $this->session->flashdata('error')
    ));
    }      
    public function accounts() {
        if ($this->me['role'] !== 'admin') redirect('admin');
        $this->call->view('admin/accounts', array(
            'me' => $this->me, 'managers' => $this->UserModel->managers(),
            'orgs' => $this->OrganizationModel->all(),
            'message' => $this->session->flashdata('message'), 'error' => null, 'old' => array()
        ));
    }

    public function create_account() {
        if ($this->me['role'] !== 'admin') redirect('admin');

        $name    = trim($this->io->post('full_name'));
        $email   = trim($this->io->post('email'));
        $pass    = $this->io->post('password');
        $org_id  = (int) $this->io->post('organization_id');
        $new_org = trim($this->io->post('new_organization'));

        $error = null;
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Enter a name and a valid email.';
        elseif (strlen($pass) < 6) $error = 'The password must be at least 6 characters.';
        elseif ($this->UserModel->email_exists($email)) $error = 'That email is already used by another account.';
        elseif ($new_org !== '') {
            if ($this->OrganizationModel->find_by_name($new_org)) $error = 'That organization already exists. Choose it from the list instead.';
        } elseif (!$org_id || !$this->OrganizationModel->find($org_id)) {
            $error = 'Choose an organization, or type the name of a new one.';
        } elseif ($this->UserModel->manager_of($org_id)) {
            $error = 'That organization already has a manager account.';
        }

        if ($error) {   // show the form again with what was typed
            $this->call->view('admin/accounts', array(
                'me' => $this->me, 'managers' => $this->UserModel->managers(), 'orgs' => $this->OrganizationModel->all(),
                'message' => null, 'error' => $error,
                'old' => array('full_name' => $name, 'email' => $email, 'organization_id' => $org_id, 'new_organization' => $new_org)
            ));
            return;
        }

        if ($new_org !== '') {
            $this->OrganizationModel->create(array('organization_name' => $new_org, 'description' => '', 'created_by' => $this->me['id']));
            $org_id = (int) $this->OrganizationModel->find_by_name($new_org)['id'];
        }
        $this->UserModel->create(array(
            'full_name' => $name, 'email' => $email, 'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
            'role' => 'organizer', 'organization_id' => $org_id
        ));
        $this->session->set_flashdata('message', 'Account created for ' . $name . '. They can log in with ' . $email . ' and the password you set.');
        redirect('admin/accounts');
    }
}