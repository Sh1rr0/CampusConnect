<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
require_once __DIR__ . '/ApiBase.php';

class ApiAdminController extends ApiBase {

    // ================= organizations (admin) =================
    public function organizations() {
        $this->need(['admin']);
        $this->send_json(['organizations' => $this->OrganizationModel->all()]);
    }

    public function create_organization() {
        $me = $this->need(['admin']);
        $b = $this->input_data(); $name = trim($b['organization_name'] ?? '');
        if ($name === '') $this->send_json(['error' => 'Organization name is required.'], 422);
        $this->OrganizationModel->create(['organization_name' => $name, 'description' => $b['description'] ?? '', 'created_by' => $me['id']]);
        $this->send_json(['message' => 'Organization created.'], 201);
    }

    public function update_organization($id) {
        $this->need(['admin']);
        if (!$this->OrganizationModel->find($id)) $this->send_json(['error' => 'Organization not found.'], 404);
        $b = $this->input_data(); $name = trim($b['organization_name'] ?? '');
        if ($name === '') $this->send_json(['error' => 'Organization name is required.'], 422);
        $this->OrganizationModel->update_org($id, ['organization_name' => $name, 'description' => $b['description'] ?? '']);
        $this->send_json(['message' => 'Organization updated.']);
    }

    public function delete_organization($id) {
        $this->need(['admin']);
        $org = $this->OrganizationModel->find($id);
        if (!$org) $this->send_json(['error' => 'Organization not found.'], 404);
        if ($org['event_count'] || $org['student_count'] || $org['manager_count']) {
            $this->send_json(['error' => 'This organization still has events or members, so it cannot be deleted.'], 409);
        }
        $this->OrganizationModel->delete_org($id);
        $this->send_json(['message' => 'Organization deleted.']);
    }

    // ================= organization manager accounts (admin) =================
    public function managers() {
        $this->need(['admin']);
        $this->send_json(['managers' => $this->UserModel->managers()]);
    }

    public function create_manager() {
        $this->need(['admin']);
        $b = $this->input_data();
        $name = trim($b['full_name'] ?? ''); $email = trim($b['email'] ?? ''); $pass = $b['password'] ?? '';
        $org  = (int) ($b['organization_id'] ?? 0);

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $this->send_json(['error' => 'Enter a name and a valid email.'], 422);
        if (strlen($pass) < 6) $this->send_json(['error' => 'Password must be at least 6 characters.'], 422);
        if (!$org || !$this->OrganizationModel->find($org)) $this->send_json(['error' => 'Choose a valid organization.'], 422);
        if ($this->UserModel->email_exists($email)) $this->send_json(['error' => 'That email is already used.'], 409);
        if ($this->UserModel->manager_of($org)) $this->send_json(['error' => 'This organization already has a manager.'], 409);

        $this->UserModel->create([
            'full_name' => $name, 'email' => $email, 'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
            'role' => 'organizer', 'organization_id' => $org
        ]);
        $this->send_json(['message' => 'Manager account created.'], 201);
    }

    public function update_manager($id) {
        $this->need(['admin']);
        $u = $this->UserModel->find($id);
        if (!$u || $u['role'] !== 'organizer') $this->send_json(['error' => 'Manager not found.'], 404);

        $b = $this->input_data();
        $name = trim($b['full_name'] ?? $u['full_name']); $email = trim($b['email'] ?? $u['email']);
        $org  = (int) ($b['organization_id'] ?? $u['organization_id']);
        $status = $b['status'] ?? $u['status'];

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $this->send_json(['error' => 'Enter a name and a valid email.'], 422);
        if (!in_array($status, ['active', 'inactive'])) $this->send_json(['error' => 'Invalid status.'], 422);
        if (!$org || !$this->OrganizationModel->find($org)) $this->send_json(['error' => 'Choose a valid organization.'], 422);
        if ($this->UserModel->email_exists($email, $id)) $this->send_json(['error' => 'That email is already used.'], 409);
        if ($this->UserModel->manager_of($org, $id)) $this->send_json(['error' => 'That organization already has a manager.'], 409);

        $data = ['full_name' => $name, 'email' => $email, 'organization_id' => $org, 'status' => $status];
        if (!empty($b['password'])) {
            if (strlen($b['password']) < 6) $this->send_json(['error' => 'Password must be at least 6 characters.'], 422);
            $data['password_hash'] = password_hash($b['password'], PASSWORD_DEFAULT);
        }
        $this->UserModel->update_user($id, $data);
        $this->send_json(['message' => 'Manager updated.']);
    }

    public function delete_manager($id) {
        $this->need(['admin']);
        $u = $this->UserModel->find($id);
        if (!$u || $u['role'] !== 'organizer') $this->send_json(['error' => 'Manager not found.'], 404);
        if ($this->UserModel->has_history($id)) {   // keep their events and scans: deactivate instead
            $this->UserModel->update_user($id, ['status' => 'inactive']);
            $this->send_json(['message' => 'This manager has events or scans on record, so the account was deactivated instead of deleted.']);
        }
        $this->UserModel->delete_user($id);
        $this->send_json(['message' => 'Manager deleted.']);
    }

    // ================= events (admin + manager) =================
    public function events() {
        $me     = $this->need(['admin', 'organizer']);
        $events = $me['role'] === 'admin' ? $this->EventModel->admin_list() : $this->EventModel->for_organization($me['organization_id']);
        foreach ($events as &$e) {
            $e['ended'] = strtotime($e['end_at']) < time();
            $e['fines'] = ($e['ended'] && $e['status'] !== 'cancelled')
                ? ((int) $e['registered_count'] * count(Fine_service::$slots) - (int) $e['scan_count']) * (float) $e['fine_per_session']
                : null;
        }
        unset($e);
        $this->send_json(['events' => $events]);
    }

    // multipart/form-data when a poster file is included
    public function create_event() {
        $me = $this->need(['admin', 'organizer']);
        $b  = $this->input_data();
        list($fields, $error) = $this->event_fields($b);
        if ($error) $this->send_json(['error' => $error], 422);

        if ($me['role'] === 'admin') {
            list($scope, $org, $error) = $this->scope_fields($b);
            if ($error) $this->send_json(['error' => $error], 422);
            $status = 'approved';
        } else {
            if (!$me['organization_id']) $this->send_json(['error' => 'Your account is not linked to an organization.'], 403);
            $scope = 'program'; $org = (int) $me['organization_id']; $status = 'pending';   // needs admin approval
        }
        $fields += ['organization_id' => $org, 'scope' => $scope, 'created_by' => $me['id'],
                    'poster_url' => $this->upload_poster(), 'status' => $status];
        $this->EventModel->create($fields);
        $this->send_json(['message' => $status === 'pending' ? 'Event submitted for admin approval.' : 'Event created.', 'status' => $status], 201);
    }

    public function update_event($id) {
        $me    = $this->need(['admin', 'organizer']);
        $event = $this->load_managed_event($id, $me);
        $b     = $this->input_data();

        if ($me['role'] === 'organizer' && !in_array($event['status'], ['pending', 'rejected'])) {
            $this->send_json(['error' => 'Approved events can only be changed through a change request.'], 409);
        }
        list($fields, $error) = $this->event_fields($b, (int) $event['registered_count']);
        if ($error) $this->send_json(['error' => $error], 422);

        $fields['poster_url'] = $this->upload_poster() ?: $event['poster_url'];
        if ($me['role'] === 'admin' && isset($b['scope'])) {
            list($scope, $org, $error) = $this->scope_fields($b);
            if ($error) $this->send_json(['error' => $error], 422);
            $fields['scope'] = $scope; $fields['organization_id'] = $org;
        }
        if ($me['role'] === 'organizer') { $fields['status'] = 'pending'; $fields['review_note'] = null; }   // resubmitted for approval

        $this->EventModel->update_event($id, $fields);
        $this->send_json(['message' => $me['role'] === 'organizer' ? 'Saved and resubmitted for approval.' : 'Event updated.']);
    }

    // Admin: permanent delete (with registrations only if confirm = "DELETE"). Manager: only when nobody has registered.
    public function delete_event($id) {
        $me    = $this->need(['admin', 'organizer']);
        $event = $this->load_managed_event($id, $me);
        $b     = $this->input_data();

        if ((int) $event['registered_count'] > 0) {
            if ($me['role'] !== 'admin') $this->send_json(['error' => 'This event has registrations. Cancel it instead.'], 409);
            if (($b['confirm'] ?? '') !== 'DELETE') $this->send_json(['error' => 'Send {"confirm":"DELETE"} to delete an event that has registrations.'], 422);
            $this->EventModel->delete_with_records($id);
        } else {
            $this->EventModel->delete_event($id);
        }
        $this->send_json(['message' => 'Event deleted.']);
    }

    public function cancel($id) {
        $me    = $this->need(['admin', 'organizer']);
        $event = $this->load_managed_event($id, $me);
        if (!in_array($event['status'], ['approved', 'pending'])) $this->send_json(['error' => 'Only approved or pending events can be cancelled.'], 409);
        $this->EventModel->set_status($id, 'cancelled');
        $this->send_json(['message' => 'Event cancelled.', 'status' => 'cancelled']);
    }

    public function reopen($id) {
        $me    = $this->need(['admin', 'organizer']);
        $event = $this->load_managed_event($id, $me);
        if ($event['status'] !== 'cancelled') $this->send_json(['error' => 'Only cancelled events can be reopened.'], 409);
        $new = $me['role'] === 'admin' ? 'approved' : 'pending';
        $this->EventModel->set_status($id, $new);
        $this->send_json(['message' => 'Event reopened.', 'status' => $new]);
    }

    // Admin approves or rejects (body: status = approved|rejected, note = reason shown to the manager)
    public function set_status($id) {
        $this->need(['admin']);
        $b = $this->input_data(); $status = $b['status'] ?? '';
        $event = $this->EventModel->find($id);
        if (!$event) $this->send_json(['error' => 'Event not found.'], 404);
        if (!in_array($status, ['approved', 'rejected'])) $this->send_json(['error' => 'Invalid status.'], 422);
        if ($event['status'] === 'cancelled') $this->send_json(['error' => 'Reopen the event first.'], 409);

        $this->EventModel->update_event($id, ['status' => $status, 'review_note' => $status === 'rejected' ? trim($b['note'] ?? '') : null]);
        $this->send_json(['message' => 'Event ' . $status . '.', 'status' => $status]);
    }

    // Set the fine amount (admin any time; manager until the event has ended)
    public function set_fine($id) {
        $me    = $this->need(['admin', 'organizer']);
        $event = $this->load_managed_event($id, $me);
        $b     = $this->input_data();
        if (!isset($b['fine_per_session']) || !is_numeric($b['fine_per_session']) || $b['fine_per_session'] < 0) {
            $this->send_json(['error' => 'Enter a fine amount of 0 or more.'], 422);
        }
        if ($me['role'] === 'organizer' && strtotime($event['end_at']) < time()) $this->send_json(['error' => 'The event has ended, so only the admin can change the fine.'], 409);
        $this->EventModel->update_event($id, ['fine_per_session' => (float) $b['fine_per_session']]);
        $this->send_json(['message' => 'Fine amount updated.']);
    }

    // ================= change requests =================
    public function request_change($id) {   // manager, for an approved event
        $me    = $this->need(['organizer']);
        $event = $this->load_managed_event($id, $me);
        if ($event['status'] !== 'approved') $this->send_json(['error' => 'Only approved events need a change request. Edit this one directly.'], 409);
        if ($this->ChangeRequestModel->pending_for($id)) $this->send_json(['error' => 'There is already a pending change request for this event.'], 409);

        $b = $this->input_data();
        list($fields, $error) = $this->event_fields($b, (int) $event['registered_count']);
        if ($error) $this->send_json(['error' => $error], 422);
        if ($poster = $this->upload_poster()) $fields['poster_url'] = $poster;

        $this->ChangeRequestModel->create($id, $me['id'], json_encode($fields), trim($b['reason'] ?? ''));
        $this->send_json(['message' => 'Change request sent to the admin.'], 201);
    }

    public function change_requests() {   // admin: all. manager: their organization's (to see approval status)
        $me   = $this->need(['admin', 'organizer']);
        $rows = $this->ChangeRequestModel->list($_GET['status'] ?? '', $me['role'] === 'organizer' ? $me['organization_id'] : null);
        foreach ($rows as &$r) $r['changes'] = json_decode($r['changes'], true);
        unset($r);
        $this->send_json(['requests' => $rows]);
    }

    public function approve_change($id) {
        $me  = $this->need(['admin']);
        $req = $this->ChangeRequestModel->find($id);
        if (!$req) $this->send_json(['error' => 'Request not found.'], 404);
        if ($req['status'] !== 'pending') $this->send_json(['error' => 'This request was already reviewed.'], 409);

        $event   = $this->EventModel->find($req['event_id']);
        $allowed = array_flip(['title', 'description', 'location', 'start_at', 'end_at', 'capacity', 'fine_per_session', 'poster_url']);
        $changes = array_intersect_key(json_decode($req['changes'], true) ?: [], $allowed);
        if (!empty($changes['capacity']) && $changes['capacity'] < (int) $event['registered_count']) {
            $this->send_json(['error' => 'The new capacity is lower than the number of registered students.'], 409);
        }
        $this->EventModel->update_event($event['id'], $changes);
        $this->ChangeRequestModel->review($id, 'approved', null, $me['id']);
        $this->send_json(['message' => 'Change approved and applied.']);
    }

    public function reject_change($id) {
        $me  = $this->need(['admin']);
        $req = $this->ChangeRequestModel->find($id);
        if (!$req) $this->send_json(['error' => 'Request not found.'], 404);
        if ($req['status'] !== 'pending') $this->send_json(['error' => 'This request was already reviewed.'], 409);
        $this->ChangeRequestModel->review($id, 'rejected', trim($this->input_data()['note'] ?? ''), $me['id']);
        $this->send_json(['message' => 'Change request rejected.']);
    }

    // ================= attendance =================
    public function attendees($id) {   // registrations, who attended / missed, scans, fines
        $me    = $this->need(['admin', 'organizer']);
        $event = $this->load_managed_event($id, $me);
        $rows  = $this->RegistrationModel->attendees($id);
        $total = 0;
        foreach ($rows as &$r) {
            $r['summary'] = Fine_service::summarize($event, $r['scans']);
            $total       += $r['summary']['fine'];
        }
        unset($r);
        $this->send_json(['event' => $event, 'rows' => $rows, 'total_fines' => $total, 'slots' => Fine_service::$slots]);
    }

    // body: { token, event_id, slot }  (slot = am_in | am_out | pm_in | pm_out)
    public function checkin() {
        $me    = $this->need(['admin', 'organizer']);
        $b     = $this->input_data();
        $slot  = $b['slot'] ?? '';
        $token = trim($b['token'] ?? '');
        $event_id = (int) ($b['event_id'] ?? 0);

        if (!isset(Fine_service::$slots[$slot])) $this->send_json(['ok' => false, 'message' => 'Choose a session first.']);
        $reg = $token ? $this->RegistrationModel->find_by_hash(Qr_service::token_hash($token)) : null;
        if (!$reg) $this->send_json(['ok' => false, 'message' => 'Invalid QR code.']);
        if ($event_id && (int) $reg['event_id'] !== $event_id) $this->send_json(['ok' => false, 'message' => 'This pass is for another event: ' . $reg['title']]);

        $event = $this->EventModel->find($reg['event_id']);
        if (!$this->can_manage($event, $me)) $this->send_json(['ok' => false, 'message' => 'You cannot scan for this event.']);
        if ($reg['event_status'] === 'cancelled') $this->send_json(['ok' => false, 'message' => 'This event is cancelled.']);

        $label = Fine_service::$slots[$slot];
        if ($this->RegistrationModel->has_scan($reg['id'], $slot)) $this->send_json(['ok' => false, 'message' => $reg['full_name'] . ' already has ' . $label . '.']);

        $this->RegistrationModel->mark_present($reg['id'], $slot, $me['id']);
        $this->send_json(['ok' => true, 'message' => $label . ' recorded: ' . $reg['full_name']]);
    }

    // ================= fines =================
    // GET ?event_id=&status=uncharged|unpaid|paid&q=name-or-email   (managers only see their organization)
    public function fines() {
        $me   = $this->need(['admin', 'organizer']);
       $org = $me['role'] === 'organizer' ? $me['organization_id'] : ((int) ($_GET['organization_id'] ?? 0) ?: null);
        $rows = $this->FineModel->report((int) ($_GET['event_id'] ?? 0), $org, trim($_GET['q'] ?? ''));

        if (!empty($_GET['status'])) $rows = array_values(array_filter($rows, fn($r) => $r['state'] === $_GET['status']));
        $totals = ['uncharged' => 0, 'unpaid' => 0, 'paid' => 0];
        foreach ($rows as $r) $totals[$r['state']] += $r['amount'];
        $this->send_json(['fines' => $rows, 'totals' => $totals]);
    }

    // Admin: turn the missed scans of an ended event into fines (safe to run again: unpaid fines refresh, paid ones stay)
    public function charge_fines($id) {
        $me    = $this->need(['admin']);
        $event = $this->EventModel->find($id);
        if (!$event) $this->send_json(['error' => 'Event not found.'], 404);
        if ($event['status'] === 'cancelled') $this->send_json(['error' => 'Cancelled events have no fines.'], 409);
        if (strtotime($event['end_at']) >= time()) $this->send_json(['error' => 'Fines can only be charged after the event has ended.'], 409);

        $count = 0; $total = 0;
        foreach ($this->FineModel->report($id) as $r) {
            if ($r['state'] === 'paid') continue;
            $amount = $r['missed'] * (float) $event['fine_per_session'];
            $this->FineModel->charge($r['registration_id'], $r['missed'], $amount, $me['id']);
            if ($r['missed'] > 0) { $count++; $total += $amount; }
        }
        $this->send_json(['message' => "Charged $count students.", 'students' => $count, 'total' => $total]);
    }

    public function mark_paid($fine_id) {   // body: { paid: true | false }
        $me = $this->need(['admin']);
        if (!$this->FineModel->find($fine_id)) $this->send_json(['error' => 'Fine not found.'], 404);
        $b    = $this->input_data();
        $paid = isset($b['paid']) ? filter_var($b['paid'], FILTER_VALIDATE_BOOLEAN) : true;
        $this->FineModel->set_paid($fine_id, $paid, $me['id']);
        $this->send_json(['message' => $paid ? 'Marked as paid.' : 'Marked as unpaid.', 'status' => $paid ? 'paid' : 'unpaid']);
    }
}