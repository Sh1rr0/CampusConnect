<?php $title = 'Events'; include __DIR__ . '/../partials/header.php'; ?>
<h1>Upcoming Events</h1>
<?php if (empty($events)): ?><p>No approved events yet.</p><?php endif; ?>
<?php foreach ($events as $e): ?>
  <div class="card">
    <?php if (!empty($e['poster_url'])): ?><img class="poster" src="<?= htmlspecialchars($e['poster_url']) ?>" alt="Poster"><?php endif; ?>
    <h3><a href="<?= site_url('events/' . $e['id']) ?>"><?= htmlspecialchars($e['title']) ?></a></h3>
    <p><b><?= htmlspecialchars($e['organization_name']) ?></b> · <?= htmlspecialchars($e['location']) ?></p>
    <p><?= date('M d, Y g:i A', strtotime($e['start_at'])) ?></p>
    <p>Registered: <?= (int) $e['registered_count'] ?><?= $e['capacity'] ? ' / ' . (int) $e['capacity'] : '' ?></p>
    <a class="btn" href="<?= site_url('events/' . $e['id']) ?>">View</a>
  </div>
<?php endforeach; ?>
<?php include __DIR__ . '/../partials/footer.php'; ?>