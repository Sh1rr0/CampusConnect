<!DOCTYPE html>
<html><head><meta charset="utf-8"><title><?= htmlspecialchars($event['title']) ?></title></head>
<body>
<a href="<?= site_url('events') ?>">&larr; Back</a>
<h1><?= htmlspecialchars($event['title']) ?></h1>
<p><b><?= htmlspecialchars($event['organization_name']) ?></b></p>
<p><?= nl2br(htmlspecialchars($event['description'])) ?></p>
<p>Where: <?= htmlspecialchars($event['location']) ?></p>
<p>When: <?= date('M d, Y g:i A', strtotime($event['start_at'])) ?> – <?= date('M d, Y g:i A', strtotime($event['end_at'])) ?></p>
<?php if (!empty($message)): ?><p style="color:green"><?= htmlspecialchars($message) ?></p><?php endif; ?>
<form method="post" action="<?= site_url('events/' . $event['id'] . '/register') ?>">
  <button type="submit">Register</button>
</form>
</body></html>