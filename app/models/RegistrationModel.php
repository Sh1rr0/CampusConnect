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

    // array(registration_id => array(slot => checked_in_at))
    private function scans_for($ids) {
        if (!$ids) return array();
        $in   = implode(',', array_fill(0, count($ids), '?'));
        $rows = $this->db->raw("SELECT registration_id, slot, checked_in_at FROM attendance
                                WHERE registration_id IN ($in)", array_values($ids))->fetchAll(PDO::FETCH_ASSOC);
        $map = array();
        foreach ($rows as $r) $map[$r['registration_id']][$r['slot']] = $r['checked_in_at'];
        return $map;
    }

    public function for_student($student_id) {
        $sql = "SELECT r.id, r.event_id, r.registered_at, e.title, e.location, e.description,
                       e.start_at, e.end_at, e.fine_per_session, e.status
                FROM registrations r JOIN events e ON e.id = r.event_id
                WHERE r.student_id = ? ORDER BY e.start_at ASC";
        $regs  = $this->db->raw($sql, array($student_id))->fetchAll(PDO::FETCH_ASSOC);
        $scans = $this->scans_for(array_column($regs, 'id'));
        foreach ($regs as &$r) $r['scans'] = $scans[$r['id']] ?? array();
        unset($r);
        return $regs;
    }

    public function attendees($event_id) {
        $sql = "SELECT r.id, u.full_name, u.email, r.registered_at
                FROM registrations r JOIN users u ON u.id = r.student_id
                WHERE r.event_id = ? ORDER BY u.full_name ASC";
        $rows  = $this->db->raw($sql, array($event_id))->fetchAll(PDO::FETCH_ASSOC);
        $scans = $this->scans_for(array_column($rows, 'id'));
        foreach ($rows as &$r) $r['scans'] = $scans[$r['id']] ?? array();
        unset($r);
        return $rows;
    }

    public function find_by_hash($hash) {
        $sql = "SELECT r.id, r.event_id, u.full_name, e.title, e.status AS event_status
                FROM registrations r
                JOIN users u ON u.id = r.student_id
                JOIN events e ON e.id = r.event_id
                WHERE r.qr_token_hash = ?";
        return $this->db->raw($sql, array($hash))->fetch(PDO::FETCH_ASSOC);
    }

    public function has_scan($registration_id, $slot) {
        return (bool) $this->db->table('attendance')
                    ->where('registration_id', $registration_id)->where('slot', $slot)->get();
    }
    public function mark_present($registration_id, $slot, $scanned_by) {
        return $this->db->table('attendance')->insert(array(
            'registration_id' => $registration_id, 'slot' => $slot, 'scanned_by' => $scanned_by
        ));
    }
}