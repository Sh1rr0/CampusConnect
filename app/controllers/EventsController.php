<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class EventsController extends Controller {
    public function __construct() {
        parent::__construct();
        $this->call->model('EventModel');
        $this->call->model('RegistrationModel');
    }

    public function index() {
        $data['events'] = $this->EventModel->get_approved();
        $data['user']   = $this->session->userdata('full_name');
        $this->call->view('events/index', $data);
    }

    public function show($id) {
        $event = $this->EventModel->find($id);
        if (!$event) { show_404(); return; }
        $data['event']   = $event;
        $data['message'] = $this->session->flashdata('message');
        $this->call->view('events/show', $data);
    }

    public function create() {
        if (!in_array($this->session->userdata('role'), array('organizer', 'admin'))) redirect('login');
        $this->call->view('events/create');
    }

    public function store() {
        $uid = $this->session->userdata('user_id');
        if (!$uid) redirect('login');

        $this->EventModel->create(array(
            'organization_id' => (int) $this->io->post('organization_id'),
            'created_by'      => $uid,
            'title'           => $this->io->post('title'),
            'description'     => $this->io->post('description'),
            'location'        => $this->io->post('location'),
            'start_at'        => $this->io->post('start_at'),
            'end_at'          => $this->io->post('end_at'),
            'capacity'        => (int) $this->io->post('capacity'),
            'status'          => 'pending'
        ));
        redirect('events');
    }

    public function register($id) {
        $uid = $this->session->userdata('user_id');
        if (!$uid) redirect('login');

        $event = $this->EventModel->find($id);
        if (!$event || $event['status'] !== 'approved') { show_404(); return; }

        if ($this->RegistrationModel->exists($id, $uid)) {
            $msg = 'You are already registered.';
        } elseif ($event['capacity'] && $event['registered_count'] >= $event['capacity']) {
            $msg = 'Sorry, this event is full.';
        } else {
            $this->RegistrationModel->register($id, $uid);
            $msg = 'Registration successful!';
        }
        $this->session->set_flashdata('message', $msg);
        redirect('events/' . $id);
    }
}