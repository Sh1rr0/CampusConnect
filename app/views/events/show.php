<?php $title = $event['title']; include __DIR__ . '/../partials/header.php'; ?>
<div class="card">
  <?php if (!empty($event['poster_url'])): ?><img class="poster" src="<?= htmlspecialchars($event['poster_url']) ?>" alt="Poster"><?php endif; ?>
  <h1><?= htmlspecialchars($event['title']) ?></h1>
  <p><b><?= htmlspecialchars($event['organization_name']) ?></b></p>
  <p><?= nl2br(htmlspecialchars((string) $event['description'])) ?></p>
  <p><?= htmlspecialchars($event['location']) ?></p>
  <p><?= date('M d, Y g:i A', strtotime($event['start_at'])) ?> – <?= date('M d, Y g:i A', strtotime($event['end_at'])) ?></p>
  <p>Registered: <?= (int) $event['registered_count'] ?><?= $event['capacity'] ? ' / ' . (int) $event['capacity'] : '' ?></p>
  <?php if (!empty($message)): ?><div class="msg"><?= htmlspecialchars($message) ?></div><?php endif; ?>

 <?php if ($event['status'] === 'cancelled'): ?>
  <div class="msg err">This event has been cancelled.</div>
<?php elseif (empty($me['role'])): ?>
    <a class="btn" href="<?= site_url('login') ?>">Login to register</a>
  <?php elseif ($me['role'] === 'student'): ?>
    <?php if ($registered): ?>
      <a class="btn green" href="<?= site_url('my-events') ?>">View my QR pass</a>
    <?php else: ?>
      <form method="post" action="<?= site_url('events/' . $event['id'] . '/register') ?>" style="display:inline">
        <button class="btn" type="submit">Register</button>
      </form>
    <?php endif; ?>
  <?php endif; ?>
  <a class="btn gray" target="_blank" href="<?= htmlspecialchars(cal_link($event)) ?>">Add to Google Calendar</a>
</div>
<?php include __DIR__ . '/../partials/footer.php'; ?>