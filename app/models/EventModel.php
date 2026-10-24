<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class EventModel extends Model {
    private $base = "SELECT e.*, o.organization_name,
        (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) AS registered_count,
        (SELECT COUNT(*) FROM registrations r JOIN attendance a ON a.registration_id = r.id
          WHERE r.event_id = e.id) AS present_count
        FROM events e JOIN organizations o ON o.id = e.organization_id";

    public function get_approved() {
        return $this->db->raw($this->base . " WHERE e.status = 'approved' ORDER BY e.start_at ASC")
                        ->fetchAll(PDO::FETCH_ASSOC);
    }
    public function admin_list($only_user_id = null) {
        if ($only_user_id) {
            return $this->db->raw($this->base . " WHERE e.created_by = ? ORDER BY e.start_at DESC", array($only_user_id))
                            ->fetchAll(PDO::FETCH_ASSOC);
        }
        return $this->db->raw($this->base . " ORDER BY e.start_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    }
    public function find($id) {
        return $this->db->raw($this->base . " WHERE e.id = ?", array($id))->fetch(PDO::FETCH_ASSOC);
    }
    public function organizations() {
        return $this->db->raw("SELECT id, organization_name FROM organizations ORDER BY organization_name")
                        ->fetchAll(PDO::FETCH_ASSOC);
    }
    public function create($data) { return $this->db->table('events')->insert($data); }
    public function set_status($id, $status) {
        return $this->db->table('events')->where('id', $id)->update(array('status' => $status));
    }
}