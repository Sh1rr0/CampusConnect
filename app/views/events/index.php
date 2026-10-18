<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>CampusConnect – Events</title></head>
<body>
<h1>Upcoming Events</h1>
<?php if ($user): ?>
  <p>Hi, <?= htmlspecialchars($user) ?> | <a href="<?= site_url('logout') ?>">Logout</a></p>
<?php else: ?>
  <p><a href="<?= site_url('login') ?>">Login</a></p>
<?php endif; ?>

<?php if (empty($events)): ?>
  <p>No approved events yet.</p>
<?php else: foreach ($events as $e): ?>
  <div style="border:1px solid #ccc;padding:10px;margin:10px 0">
    <h3><a href="<?= site_url('events/' . $e['id']) ?>"><?= htmlspecialchars($e['title']) ?></a></h3>
    <p><b><?= htmlspecialchars($e['organization_name']) ?></b> · <?= htmlspecialchars($e['location']) ?></p>
    <p><?= date('M d, Y g:i A', strtotime($e['start_at'])) ?></p>
    <p>Registered: <?= (int)$e['registered_count'] ?><?= $e['capacity'] ? ' / ' . (int)$e['capacity'] : '' ?></p>
  </div>
<?php endforeach; endif; ?>
</body></html>