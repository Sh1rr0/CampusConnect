<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class RegistrationModel extends Model {
    public function exists($event_id, $student_id) {
        return (bool) $this->db->table('registrations')
                    ->where('event_id', $event_id)->where('student_id', $student_id)->get();
    }
    public function register($event_id, $student_id, $token_hash) {
        return $this->db->table('registrations')->insert(array(
            'event_id' => $event_id, 'student_id' => $student_id, 'qr_token_hash' => $token_hash
        ));
    }
    // A student's registrations with attendance status
    public function for_student($student_id) {
        $sql = "SELECT r.id, r.event_id, r.registered_at, e.title, e.location, e.description,
                       e.start_at, e.end_at, a.checked_in_at
                FROM registrations r
                JOIN events e ON e.id = r.event_id
                LEFT JOIN attendance a ON a.registration_id = r.id
                WHERE r.student_id = ? ORDER BY e.start_at ASC";
        return $this->db->raw($sql, array($student_id))->fetchAll(PDO::FETCH_ASSOC);
    }
    // Everyone registered for an event; checked_in_at is NULL for absentees
    public function attendees($event_id) {
        $sql = "SELECT u.full_name, u.email, r.registered_at, a.checked_in_at,
                       (a.id IS NOT NULL) AS present
                FROM registrations r
                JOIN users u ON u.id = r.student_id
                LEFT JOIN attendance a ON a.registration_id = r.id
                WHERE r.event_id = ?
                ORDER BY present DESC, u.full_name ASC";
        return $this->db->raw($sql, array($event_id))->fetchAll(PDO::FETCH_ASSOC);
    }
    public function find_by_hash($hash) {
        $sql = "SELECT r.id, r.event_id, u.full_name, e.title
                FROM registrations r
                JOIN users u ON u.id = r.student_id
                JOIN events e ON e.id = r.event_id
                WHERE r.qr_token_hash = ?";
        return $this->db->raw($sql, array($hash))->fetch(PDO::FETCH_ASSOC);
    }
    public function is_checked_in($registration_id) {
        return (bool) $this->db->table('attendance')->where('registration_id', $registration_id)->get();
    }
    public function mark_present($registration_id, $scanned_by) {
        return $this->db->table('attendance')->insert(array(
            'registration_id' => $registration_id, 'scanned_by' => $scanned_by
        ));
    }
}