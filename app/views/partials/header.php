<?php
$me    = isset($me) ? $me : array('name' => null, 'role' => null);
$title = isset($title) ? $title : 'CampusConnect';
if (!function_exists('cal_link')) {
    function cal_link($e) {
        $f = function ($d) { return date('Ymd\THis', strtotime($d)); };
        return 'https://calendar.google.com/calendar/render?action=TEMPLATE'
            . '&text=' . urlencode($e['title']) . '&dates=' . $f($e['start_at']) . '/' . $f($e['end_at'])
            . '&location=' . urlencode($e['location']) . '&details=' . urlencode(strip_tags((string) $e['description']));
    }
}
?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($title) ?> – CampusConnect</title>
<style>
 body{font-family:system-ui,Segoe UI,Arial,sans-serif;margin:0;background:#f5f6fa;color:#222}
 header{background:#1f3a93;color:#fff;padding:12px 24px;display:flex;gap:18px;align-items:center;flex-wrap:wrap}
 header a{color:#fff;text-decoration:none} header .brand{font-weight:700;font-size:18px;margin-right:auto}
 main{max-width:960px;margin:24px auto;padding:0 16px}
 .card{background:#fff;border-radius:10px;padding:16px;margin:14px 0;box-shadow:0 1px 4px rgba(0,0,0,.08)}
 .card img.poster{max-width:100%;max-height:220px;border-radius:8px;display:block;margin-bottom:10px}
 .btn{display:inline-block;background:#1f3a93;color:#fff;border:0;border-radius:6px;padding:8px 14px;cursor:pointer;text-decoration:none;font-size:14px}
 .btn.green{background:#1e8e3e}.btn.red{background:#c62828}.btn.gray{background:#666}
 table{width:100%;border-collapse:collapse;background:#fff}th,td{padding:8px 10px;border-bottom:1px solid #eee;text-align:left;font-size:14px}
 .badge{padding:2px 8px;border-radius:10px;font-size:12px;color:#fff}.b-ok{background:#1e8e3e}.b-no{background:#c62828}.b-wait{background:#e69500}.b-gray{background:#777}
 input,select,textarea{padding:8px;width:100%;box-sizing:border-box;margin:4px 0 10px;border:1px solid #ccc;border-radius:6px}
 .msg{padding:10px;border-radius:6px;background:#e8f5e9;color:#1b5e20;margin:10px 0}.err{background:#fdecea;color:#b71c1c}
</style></head><body>
<header>
  <a class="brand" href="<?= site_url('events') ?>">CampusConnect</a>
  <a href="<?= site_url('events') ?>">Events</a>
  <?php if ($me['role'] === 'student'): ?><a href="<?= site_url('my-events') ?>">My QR Passes</a><?php endif; ?>
  <?php if (in_array($me['role'], array('admin', 'organizer'))): ?>
    <a href="<?= site_url('admin') ?>">Dashboard</a><a href="<?= site_url('events/create') ?>">Create Event</a>
  <?php endif; ?>
  <?php if ($me['name']): ?><span><?= htmlspecialchars($me['name']) ?></span><a href="<?= site_url('logout') ?>">Logout</a>
  <?php else: ?><a href="<?= site_url('login') ?>">Login</a><a href="<?= site_url('signup') ?>">Sign up</a><?php endif; ?>
</header><main>