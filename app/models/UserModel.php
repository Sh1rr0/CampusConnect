<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class UserModel extends Model {
    public function find_by_email($email) {
        return $this->db->table('users')->where('email', $email)->where('status', 'active')->get();
    }
    public function email_exists($email) {
        return (bool) $this->db->table('users')->where('email', $email)->get();
    }
    public function create($data) {
        return $this->db->table('users')->insert($data);
    }
}