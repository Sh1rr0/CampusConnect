<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
require_once __DIR__ . '/../libraries/Qr_service.php';
require_once __DIR__ . '/../libraries/Brevo_service.php';
require_once __DIR__ . '/../libraries/Cloudinary_service.php';
require_once __DIR__ . '/../libraries/Fine_service.php';

class EventsController extends Controller {
    public function __construct() {
        parent::__construct();
        $this->call->model('EventModel');
        $this->call->model('RegistrationModel');
        $this->call->model('OrganizationModel');
    }

    private function me() {
        return array(
            'id'    => $this->session->userdata('user_id'),
            'name'  => $this->session->userdata('full_name'),
            'email' => $this->session->userdata('email'),
            'role'  => $this->session->userdata('role'),
            'organization_id' => $this->session->userdata('organization_id')
        );
    }

    private function show_create_form($me, $error = null) {
    $orgs   = $this->EventModel->organizations();   // every organization
    $my_org = '';
    foreach ($orgs as $o) if ((int) $o['id'] === (int) $me['organization_id']) $my_org = $o['organization_name'];
    $this->call->view('events/create', array('me' => $me, 'orgs' => $orgs, 'my_org' => $my_org, 'error' => $error));
    }

    public function index() {
        $this->call->view('events/index', array(
            'events' => $this->EventModel->get_approved(), 'me' => $this->me()
        ));
    }

    public function show($id) {
        $event = $this->EventModel->find($id);
        if (!$event) { show_404(); return; }
        $me = $this->me();
        $this->call->view('events/show', array(
            'event'      => $event,
            'me'         => $me,
            'registered' => $me['id'] ? $this->RegistrationModel->exists($id, $me['id']) : false,
            'message'    => $this->session->flashdata('message'),
            'can_join' => $me['role'] === 'student' ? $this->OrganizationModel->can_join($me['organization_id'], $event) : false,
        ));
    }

    public function create() {
        $me = $this->me();
        if (!in_array($me['role'], array('organizer', 'admin'))) redirect('login');
        $this->show_create_form($me);
    }
public function store() {
    $me = $this->me();
    if (!in_array($me['role'], array('organizer', 'admin'))) redirect('login');

    // Managers always create for their own organization. Admins choose one.
    $org_id = $me['role'] === 'admin' ? (int) $this->io->post('organization_id') : (int) $me['organization_id'];
    $valid  = array_map('intval', array_column($this->EventModel->organizations(), 'id'));
    if (!$org_id || !in_array($org_id, $valid)) {
        $this->show_create_form($me, $me['role'] === 'admin'
            ? 'Choose an organization.'
            : 'Your account is not linked to an organization. Ask the admin to link it.');
        return;
    }

    $start = strtotime($this->io->post('start_at'));
    $end   = strtotime($this->io->post('end_at'));
    if (!$start || !$end || $end <= $start) {
        $this->show_create_form($me, 'End time must be after the start time.');
        return;
    }

    $poster = null;   // Cloudinary
    if (!empty($_FILES['poster']['tmp_name']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK
        && $_FILES['poster']['size'] < 5 * 1024 * 1024 && @getimagesize($_FILES['poster']['tmp_name'])) {
        $poster = Cloudinary_service::upload($_FILES['poster']['tmp_name']);
    }

    $fine = $this->io->post('fine_per_session');
    $fine = ($fine === null || $fine === '') ? 25 : max(0, (float) $fine);

    $this->EventModel->create(array(
        'organization_id'  => $org_id,
        'created_by'       => $me['id'],
        'title'            => trim($this->io->post('title')),
        'description'      => $this->io->post('description'),
        'location'         => trim($this->io->post('location')),
        'start_at'         => date('Y-m-d H:i:s', $start),
        'end_at'           => date('Y-m-d H:i:s', $end),
        'capacity'         => (int) $this->io->post('capacity') ?: null,
        'poster_url'       => $poster,
        'status'           => $me['role'] === 'admin' ? 'approved' : 'pending',
        'fine_per_session' => $fine
    ));
    redirect('admin');
    }

    public function register($id) {
        $me = $this->me();
        if ($me['role'] !== 'student') redirect('login');

        $event = $this->EventModel->find($id);
        if (!$event || $event['status'] !== 'approved') { show_404(); return; }
        if (!$this->OrganizationModel->can_join($me['organization_id'], $event)) {
        $this->session->set_flashdata('message',
        'This event is for ' . $event['organization_name'] . ' students only. You can join your own program\'s events and USG events.');
         redirect('events/' . $id);
        }

        if ($this->RegistrationModel->exists($id, $me['id'])) {
            $msg = 'You are already registered for this event.';
        } elseif ($event['capacity'] && $event['registered_count'] >= $event['capacity']) {
            $msg = 'Sorry, this event is full.';
        } else {
            $token = Qr_service::token($id, $me['id']);
            $this->RegistrationModel->register($id, $me['id'], Qr_service::token_hash($token));
            $msg = 'Registered! Your QR pass is under My QR Passes.';

            try {
                $html = '<h2>You are registered: ' . htmlspecialchars($event['title']) . '</h2>'
                      . '<p>' . htmlspecialchars($event['location']) . '<br>' . date('M d, Y g:i A', strtotime($event['start_at'])) . '</p>'
                      . '<p>Show the attached QR code at the entrance to record your attendance.</p>';
                Brevo_service::send($me['email'], $me['name'], 'Registration confirmed: ' . $event['title'], $html,
                    array(array('name' => 'event-pass.png', 'content' => base64_encode(Qr_service::bytes($token)))));
            } catch (Throwable $e) { /* ignore */ }
        }
        $this->session->set_flashdata('message', $msg);
        redirect('events/' . $id);
    }

    public function my() {
    $me = $this->me();
    if (!$me['id']) redirect('login');
    $regs  = $this->RegistrationModel->for_student($me['id']);
    $total = 0;
    foreach ($regs as &$r) {
        $r['qr']      = Qr_service::data_uri(Qr_service::token($r['event_id'], $me['id']));
        $r['summary'] = Fine_service::summarize($r, $r['scans']);
        $total       += $r['summary']['fine'];
    }
    unset($r);
    $this->call->view('events/my', array('me' => $me, 'regs' => $regs, 'total_fines' => $total));
}
}