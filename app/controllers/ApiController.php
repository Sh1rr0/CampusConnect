<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
require_once __DIR__ . '/ApiBase.php';

class ApiController extends ApiBase {

    // ---------- auth ----------
    public function login() {
        $b    = $this->input_data();
        $pass = $b['password'] ?? '';
        $user = $this->UserModel->find_by_email(trim($b['email'] ?? ''));
        $user = $user ? (array) $user : null;

        if (!$user || !(password_verify($pass, $user['password_hash']) || $pass === $user['password_hash'])) {
            $this->send_json(['error' => 'Invalid email or password.'], 401);
        }
        $this->session->set_userdata([
            'user_id' => $user['id'], 'full_name' => $user['full_name'], 'email' => $user['email'],
            'role' => $user['role'], 'organization_id' => $user['organization_id']
        ]);
        $this->send_json(['user' => $this->with_org([
            'id' => $user['id'], 'name' => $user['full_name'], 'email' => $user['email'],
            'role' => $user['role'], 'organization_id' => $user['organization_id']
        ])]);
    }

    public function signup() {
        $b = $this->input_data();
        $name = trim($b['full_name'] ?? ''); $email = trim($b['email'] ?? ''); $pass = $b['password'] ?? '';
        $org  = (int) ($b['organization_id'] ?? 0) ?: null;

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $this->send_json(['error' => 'Enter your name and a valid email.'], 422);
        if (strlen($pass) < 6) $this->send_json(['error' => 'Password must be at least 6 characters.'], 422);
        if ($org && !$this->OrganizationModel->find($org)) $this->send_json(['error' => 'Unknown program.'], 422);
        if ($this->UserModel->email_exists($email)) $this->send_json(['error' => 'That email is already registered.'], 409);

        $this->UserModel->create([
            'full_name' => $name, 'email' => $email, 'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
            'role' => 'student', 'organization_id' => $org
        ]);
        $this->send_json(['message' => 'Account created. You can log in now.'], 201);
    }

    public function logout() {
        $this->session->unset_userdata(['user_id', 'full_name', 'email', 'role', 'organization_id']);
        $this->send_json(['ok' => true]);
    }

    public function me() {
        $me = $this->current_user();
        $this->send_json(['user' => $me['id'] ? $this->with_org($me) : null]);
    }

    public function programs() {   // for the signup dropdown
        $rows = $this->OrganizationModel->all();
        $this->send_json(['programs' => array_map(fn($o) => ['id' => $o['id'], 'name' => $o['organization_name']], $rows)]);
    }

    // ---------- students ----------
    private function visible_to($event, $me) {
        if (in_array($me['role'], ['admin', 'organizer'])) return true;
        if (!in_array($event['status'], ['approved', 'cancelled'])) return false;
        return $event['scope'] === 'school' || ($me['organization_id'] && (int) $event['organization_id'] === (int) $me['organization_id']);
    }

    public function events() {
        $me = $this->current_user();
        $this->send_json(['events' => $this->EventModel->get_visible($me['organization_id'])]);
    }

    public function event($id) {
        $event = $this->EventModel->find($id);
        $me    = $this->current_user();
        if (!$event || !$this->visible_to($event, $me)) $this->send_json(['error' => 'Event not found.'], 404);
        $this->send_json([
            'event' => $event,
            'registered' => $me['id'] ? $this->RegistrationModel->exists($id, $me['id']) : false
        ]);
    }

    public function register($id) {
        $me    = $this->need(['student']);
        $event = $this->EventModel->find($id);
        if (!$event || $event['status'] !== 'approved' || !$this->visible_to($event, $me)) $this->send_json(['error' => 'Event not available.'], 404);
        if ($this->RegistrationModel->exists($id, $me['id'])) $this->send_json(['error' => 'You are already registered for this event.'], 409);
        if ($event['capacity'] && $event['registered_count'] >= $event['capacity']) $this->send_json(['error' => 'Sorry, this event is full.'], 409);

        $token = Qr_service::token($id, $me['id']);
        $this->RegistrationModel->register($id, $me['id'], Qr_service::token_hash($token));

        try {   // a mail problem must never block the registration
            $html = '<h2>You are registered: ' . htmlspecialchars($event['title']) . '</h2>'
                  . '<p>' . htmlspecialchars($event['location']) . '<br>' . date('M d, Y g:i A', strtotime($event['start_at'])) . '</p>'
                  . '<p>Show the attached QR code at the entrance to record your attendance.</p>';
            Brevo_service::send($me['email'], $me['name'], 'Registration confirmed: ' . $event['title'], $html,
                [['name' => 'event-pass.png', 'content' => base64_encode(Qr_service::bytes($token))]]);
        } catch (Throwable $e) { /* ignore */ }

        $this->send_json(['message' => 'Registered! Your QR pass is ready.'], 201);
    }

    // "My registered events", "My Attendance", the QR code and fines, all in one call
    public function my_registrations() {
        $me      = $this->need(['student']);
        $regs    = $this->RegistrationModel->for_student($me['id']);
        $charged = $this->FineModel->for_registrations(array_column($regs, 'id'));
        $total   = 0;
        foreach ($regs as &$r) {
            $r['qr']          = Qr_service::data_uri(Qr_service::token($r['event_id'], $me['id']));
            $r['summary']     = Fine_service::summarize($r, $r['scans']);   // attended, missed, final, fine
            $r['fine_record'] = $charged[$r['id']] ?? null;                 // null until the admin charges it; then amount + paid/unpaid
            $total           += $r['summary']['fine'];
        }
        unset($r);
        $this->send_json(['registrations' => $regs, 'total_fines' => $total, 'slots' => Fine_service::$slots]);
    }
}