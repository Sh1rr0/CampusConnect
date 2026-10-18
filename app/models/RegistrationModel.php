<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class RegistrationModel extends Model {

    public function exists($event_id, $student_id) {
        $row = $this->db->table('registrations')
                        ->where('event_id', $event_id)
                        ->where('student_id', $student_id)
                        ->get();
        return !empty($row);
    }

    public function register($event_id, $student_id) {
        // Only the hash is stored; the raw token is what goes into the QR code.
        $token = bin2hex(random_bytes(16));
        $this->db->table('registrations')->insert(array(
            'event_id'      => $event_id,
            'student_id'    => $student_id,
            'qr_token_hash' => hash('sha256', $token)
        ));
        return $token;
    }
}