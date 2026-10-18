<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class EventModel extends Model {

    public function get_approved() {
        $sql = "SELECT e.*, o.organization_name,
                  (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) AS registered_count
                FROM events e
                JOIN organizations o ON o.id = e.organization_id
                WHERE e.status = 'approved'
                ORDER BY e.start_at ASC";
        return $this->db->raw($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id) {
        $sql = "SELECT e.*, o.organization_name,
                  (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) AS registered_count
                FROM events e
                JOIN organizations o ON o.id = e.organization_id
                WHERE e.id = ?";
        return $this->db->raw($sql, array($id))->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        return $this->db->table('events')->insert($data);
    }
}