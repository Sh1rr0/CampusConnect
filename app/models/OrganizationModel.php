<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class OrganizationModel extends Model {
    private $sel = "SELECT o.*,
        (SELECT COUNT(*) FROM users u WHERE u.organization_id = o.id AND u.role = 'student') AS student_count,
        (SELECT COUNT(*) FROM users u WHERE u.organization_id = o.id AND u.role = 'organizer') AS manager_count,
        (SELECT COUNT(*) FROM events e WHERE e.organization_id = o.id) AS event_count
        FROM organizations o";

    public function all() { return $this->db->raw($this->sel . " ORDER BY o.organization_name")->fetchAll(PDO::FETCH_ASSOC); }
    public function find($id) { return $this->db->raw($this->sel . " WHERE o.id = ?", [$id])->fetch(PDO::FETCH_ASSOC); }
    public function create($data) { return $this->db->table('organizations')->insert($data); }
    public function update_org($id, $data) { return $this->db->table('organizations')->where('id', $id)->update($data); }
    public function delete_org($id) { return $this->db->table('organizations')->where('id', $id)->delete(); }
    public function find_by_name($name) { return $this->db->raw($this->sel . " WHERE o.organization_name = ?", [$name])->fetch(PDO::FETCH_ASSOC); }
  
    public function sees($student_org, $event_org) {
    if (!$student_org || !$event_org) return false;
    if ((int) $student_org === (int) $event_org) return true;
    return (bool) $this->db->raw(
        "SELECT 1 FROM organization_links WHERE organization_id = ? AND linked_organization_id = ?",
        [$student_org, $event_org]
    )->fetch(PDO::FETCH_ASSOC);
    }

    public function can_join($student_org, $event) {
    if (($event['audience'] ?? '') === 'campus') return true;
    return $this->sees($student_org, $event['organization_id']);
    }
}

