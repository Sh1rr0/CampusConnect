<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
require_once __DIR__ . '/../libraries/Qr_service.php';
require_once __DIR__ . '/../libraries/Brevo_service.php';
require_once __DIR__ . '/../libraries/Cloudinary_service.php';
require_once __DIR__ . '/../libraries/Fine_service.php';

class ApiBase extends Controller {
    public function __construct() {
        parent::__construct();
        foreach (['UserModel', 'EventModel', 'RegistrationModel', 'OrganizationModel', 'FineModel', 'ChangeRequestModel'] as $m) {
            $this->call->model($m);
        }
    }

    protected function send_json($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function input_data() {
        $raw  = file_get_contents('php://input');
        $json = $raw ? json_decode($raw, true) : null;
        return is_array($json) ? $json : $_POST;
    }

    protected function current_user() {
        return [
            'id'              => $this->session->userdata('user_id'),
            'name'            => $this->session->userdata('full_name'),
            'email'           => $this->session->userdata('email'),
            'role'            => $this->session->userdata('role'),
            'organization_id' => $this->session->userdata('organization_id')
        ];
    }

    protected function with_org($user) {
        $org = !empty($user['organization_id']) ? $this->OrganizationModel->find($user['organization_id']) : null;
        $user['organization_name'] = $org ? $org['organization_name'] : null;
        return $user;
    }

    protected function need($roles = []) {
        $me = $this->current_user();
        if (!$me['id']) $this->send_json(['error' => 'Please log in first.'], 401);
        if ($roles && !in_array($me['role'], $roles)) $this->send_json(['error' => 'You are not allowed to do that.'], 403);
        return $me;
    }

    // Admin: every event. Organization manager: only program events of their own organization.
    protected function can_manage($event, $me) {
        if ($me['role'] === 'admin') return true;
        return $me['role'] === 'organizer' && $me['organization_id'] && $event['scope'] === 'program'
            && (int) $event['organization_id'] === (int) $me['organization_id'];
    }

    protected function load_managed_event($id, $me) {
        $event = $this->EventModel->find($id);
        if (!$event) $this->send_json(['error' => 'Event not found.'], 404);
        if (!$this->can_manage($event, $me)) $this->send_json(['error' => 'You cannot manage this event.'], 403);
        return $event;
    }

    // Validates the fields shared by create / update / change request
    protected function event_fields($b, $min_capacity = 0) {
        $title = trim($b['title'] ?? '');
        $location = trim($b['location'] ?? '');
        $start = strtotime($b['start_at'] ?? '');
        $end   = strtotime($b['end_at'] ?? '');
        $cap   = (int) ($b['capacity'] ?? 0) ?: null;

        if ($title === '' || $location === '') return [null, 'Title and location are required.'];
        if (!$start || !$end || $end <= $start) return [null, 'End time must be after the start time.'];
        if ($cap && $cap < $min_capacity) return [null, "Capacity cannot be lower than the $min_capacity students already registered."];

        $fine = $b['fine_per_session'] ?? '';
        $fine = ($fine === '' || $fine === null) ? 25 : max(0, (float) $fine);

        return [[
            'title' => $title, 'description' => $b['description'] ?? '', 'location' => $location,
            'start_at' => date('Y-m-d H:i:s', $start), 'end_at' => date('Y-m-d H:i:s', $end),
            'capacity' => $cap, 'fine_per_session' => $fine
        ], null];
    }

    // Admin only: school-wide, or program event for a chosen organization
    protected function scope_fields($b) {
        if (($b['scope'] ?? 'school') !== 'program') return ['school', null, null];
        $org = (int) ($b['organization_id'] ?? 0);
        if (!$org || !$this->OrganizationModel->find($org)) return [null, null, 'Choose the organization for this program event.'];
        return ['program', $org, null];
    }

    protected function upload_poster() {
        if (!empty($_FILES['poster']['tmp_name']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK
            && $_FILES['poster']['size'] < 5 * 1024 * 1024 && @getimagesize($_FILES['poster']['tmp_name'])) {
            return Cloudinary_service::upload($_FILES['poster']['tmp_name']);
        }
        return null;
    }
}