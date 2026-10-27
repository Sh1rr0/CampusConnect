<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
require_once __DIR__ . '/../libraries/Fine_service.php';

class FineModel extends Model {
    // Charged fines for a list of registrations: array(registration_id => row)
    public function for_registrations($ids) {
        if (!$ids) return [];
        $in   = implode(',', array_fill(0, count($ids), '?'));
        $rows = $this->db->raw("SELECT registration_id, amount, status, paid_at FROM fines
                                WHERE registration_id IN ($in)", array_values($ids))->fetchAll(PDO::FETCH_ASSOC);
        $map = [];
        foreach ($rows as $r) $map[$r['registration_id']] = $r;
        return $map;
    }

    public function find($id) { return $this->db->table('fines')->where('id', $id)->get(); }

    // Insert or refresh an UNPAID fine. Paid fines are never touched.
    public function charge($registration_id, $missed, $amount, $by) {
        $row = $this->db->table('fines')->where('registration_id', $registration_id)->get();
        if ($row && $row['status'] === 'paid') return;
        if ($missed <= 0) {
            if ($row) $this->db->table('fines')->where('id', $row['id'])->delete();
            return;
        }
        $data = ['missed_count' => $missed, 'amount' => $amount];
        if ($row) $this->db->table('fines')->where('id', $row['id'])->update($data);
        else      $this->db->table('fines')->insert($data + ['registration_id' => $registration_id, 'charged_by' => $by]);
    }

    public function set_paid($id, $paid, $by) {
        return $this->db->table('fines')->where('id', $id)->update([
            'status'  => $paid ? 'paid' : 'unpaid',
            'paid_at' => $paid ? date('Y-m-d H:i:s') : null,
            'marked_by' => $by
        ]);
    }

    // One row per student/event that has a fine (charged) or owes one (ended event, missed scans)
    public function report($event_id = 0, $organization_id = null, $q = '') {
        $sql = "SELECT r.id AS registration_id, u.id AS student_id, u.full_name, u.email,
                       e.id AS event_id, e.title, e.end_at, e.status AS event_status, e.fine_per_session,
                       (SELECT COUNT(*) FROM attendance a WHERE a.registration_id = r.id) AS scan_count,
                       f.id AS fine_id, f.amount AS charged_amount, f.status AS fine_status, f.paid_at
                FROM registrations r
                JOIN users u ON u.id = r.student_id
                JOIN events e ON e.id = r.event_id
                LEFT JOIN fines f ON f.registration_id = r.id
                WHERE 1 = 1";
        $p = [];
        if ($event_id)        { $sql .= " AND e.id = ?";              $p[] = $event_id; }
        if ($organization_id) { $sql .= " AND e.organization_id = ?"; $p[] = $organization_id; }
        if ($q !== '')        { $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ?)"; $p[] = "%$q%"; $p[] = "%$q%"; }
        $sql .= " ORDER BY e.end_at DESC, u.full_name ASC";

        $slots = count(Fine_service::$slots);
        $out   = [];
        foreach ($this->db->raw($sql, $p)->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $missed = $slots - (int) $r['scan_count'];
            $ended  = strtotime($r['end_at']) < time() && $r['event_status'] !== 'cancelled';
            if ($r['fine_id'])           { $r['state'] = $r['fine_status']; $r['amount'] = (float) $r['charged_amount']; }
            elseif ($ended && $missed > 0) { $r['state'] = 'uncharged';       $r['amount'] = $missed * (float) $r['fine_per_session']; }
            else continue;
            $r['missed'] = $missed;
            $out[] = $r;
        }
        return $out;
    }
}