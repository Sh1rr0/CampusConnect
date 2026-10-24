<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
require_once __DIR__ . '/../libraries/Qr_service.php';

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
        $only = $this->me['role'] === 'organizer' ? $this->me['id'] : null;   // organizers see their own events
        $this->call->view('admin/dashboard', array('me' => $this->me, 'events' => $this->EventModel->admin_list($only)));
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
        $present = count(array_filter($rows, function ($r) { return $r['present']; }));
        $this->call->view('admin/attendees', array(
            'me' => $this->me, 'event' => $event, 'rows' => $rows,
            'present' => $present, 'absent' => count($rows) - $present
        ));
    }

    public function scanner($id) {
        $event = $this->EventModel->find($id);
        if (!$event) { show_404(); return; }
        $this->call->view('admin/scanner', array('me' => $this->me, 'event' => $event));
    }

    // POST: token, event_id -> JSON
    public function checkin() {
        header('Content-Type: application/json');
        $token    = trim($this->io->post('token'));
        $event_id = (int) $this->io->post('event_id');

        $reg = $token ? $this->RegistrationModel->find_by_hash(Qr_service::token_hash($token)) : null;
        if (!$reg) { echo json_encode(array('ok' => false, 'message' => 'Invalid QR code.')); return; }
        if ($event_id && (int) $reg['event_id'] !== $event_id) {
            echo json_encode(array('ok' => false, 'message' => 'This pass is for another event: ' . $reg['title'])); return;
        }
        if ($this->RegistrationModel->is_checked_in($reg['id'])) {
            echo json_encode(array('ok' => false, 'message' => $reg['full_name'] . ' is already checked in.')); return;
        }
        $this->RegistrationModel->mark_present($reg['id'], $this->me['id']);
        echo json_encode(array('ok' => true, 'message' => 'Checked in: ' . $reg['full_name']));
    }
}