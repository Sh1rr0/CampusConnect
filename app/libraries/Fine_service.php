<?php
class Fine_service {
    public static $slots = array(
        'am_in'  => 'Morning IN',
        'am_out' => 'Morning OUT',
        'pm_in'  => 'Afternoon IN',
        'pm_out' => 'Afternoon OUT'
    );

    // $scans: array(slot => checked_in_at) for ONE student in ONE event
    public static function summarize($event, $scans) {
    $attended  = count(array_intersect_key($scans, self::$slots));
    $missed    = count(self::$slots) - $attended;
    $final     = strtotime($event['end_at']) < time();
    $cancelled = ($event['status'] ?? '') === 'cancelled';
    $rate      = (float) $event['fine_per_session'];
    return array(
        'attended' => $attended, 'missed' => $missed, 'final' => $final, 'rate' => $rate,
        'fine' => ($final && !$cancelled) ? $missed * $rate : 0
    );
    }

    public static function peso($n) { return '₱' . number_format($n, 2); }
}