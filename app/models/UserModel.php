<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class UserModel extends Model {
    public function find_by_email($email) {
        return $this->db->table('users')
                        ->where('email', $email)
                        ->where('status', 'active')
                        ->get();
    }
}