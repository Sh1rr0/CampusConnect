<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class ChangeRequestModel extends Model {
    private $sel = "SELECT c.*, e.title, e.organization_id, u.full_name AS requested_by_name
                    FROM event_change_requests c
                    JOIN events e ON e.id = c.event_id
                    JOIN users u ON u.id = c.requested_by";

    public function create($event_id, $user_id, $changes_json, $reason) {
        return $this->db->table('event_change_requests')->insert([
            'event_id' => $event_id, 'requested_by' => $user_id, 'changes' => $changes_json, 'reason' => $reason
        ]);
    }
    public function pending_for($event_id) {
        return (bool) $this->db->table('event_change_requests')->where('event_id', $event_id)->where('status', 'pending')->get();
    }
    public function find($id) { return $this->db->raw($this->sel . " WHERE c.id = ?", [$id])->fetch(PDO::FETCH_ASSOC); }
    public function list($status = '', $organization_id = null) {
        $sql = $this->sel . " WHERE 1 = 1"; $p = [];
        if ($status !== '')   { $sql .= " AND c.status = ?";          $p[] = $status; }
        if ($organization_id) { $sql .= " AND e.organization_id = ?"; $p[] = $organization_id; }
        return $this->db->raw($sql . " ORDER BY c.created_at DESC", $p)->fetchAll(PDO::FETCH_ASSOC);
    }
    public function review($id, $status, $note, $admin_id) {
        return $this->db->table('event_change_requests')->where('id', $id)->update([
            'status' => $status, 'review_note' => $note, 'reviewed_by' => $admin_id, 'reviewed_at' => date('Y-m-d H:i:s')
        ]);
    }
}