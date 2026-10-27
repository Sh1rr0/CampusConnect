<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class UserModel extends Model {
    public function find_by_email($email) {
        return $this->db->table('users')->where('email', $email)->where('status', 'active')->get();
    }
    public function email_exists($email, $except_id = 0) {
        $u = $this->db->table('users')->where('email', $email)->get();
        return $u && (int) $u['id'] !== (int) $except_id;
    }
    public function find($id) { return $this->db->table('users')->where('id', $id)->get(); }
    public function create($data) { return $this->db->table('users')->insert($data); }
    public function update_user($id, $data) { return $this->db->table('users')->where('id', $id)->update($data); }
    public function delete_user($id) { return $this->db->table('users')->where('id', $id)->delete(); }

    public function managers() {
        return $this->db->raw("SELECT u.id, u.full_name, u.email, u.status, u.organization_id, o.organization_name
                               FROM users u LEFT JOIN organizations o ON o.id = u.organization_id
                               WHERE u.role = 'organizer' ORDER BY u.full_name")->fetchAll(PDO::FETCH_ASSOC);
    }
    public function manager_of($organization_id, $except_id = 0) {
        return $this->db->raw("SELECT id FROM users WHERE role = 'organizer' AND organization_id = ? AND id <> ?",
                              [$organization_id, $except_id])->fetch(PDO::FETCH_ASSOC);
    }
    // Events created or scans recorded by this user (deleting them would break foreign keys)
    public function has_history($id) {
        $r = $this->db->raw("SELECT (SELECT COUNT(*) FROM events WHERE created_by = ?)
                                  + (SELECT COUNT(*) FROM attendance WHERE scanned_by = ?)
                                  + (SELECT COUNT(*) FROM organizations WHERE created_by = ?) AS n",
                            [$id, $id, $id])->fetch(PDO::FETCH_ASSOC);
        return (int) $r['n'] > 0;
    }
}