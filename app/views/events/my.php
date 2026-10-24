<?php $title = 'My QR Passes'; include __DIR__ . '/../partials/header.php'; ?>
<h1>My QR Passes</h1>
<?php if (empty($regs)): ?><p>You haven't registered for any events yet. <a href="<?= site_url('events') ?>">Browse events</a></p><?php endif; ?>
<?php foreach ($regs as $r): ?>
  <div class="card" style="display:flex;gap:20px;flex-wrap:wrap;align-items:center">
    <img src="<?= $r['qr'] ?>" alt="QR pass" width="180" height="180">
    <div>
      <h3><?= htmlspecialchars($r['title']) ?></h3>
      <p>📍 <?= htmlspecialchars($r['location']) ?><br>🕒 <?= date('M d, Y g:i A', strtotime($r['start_at'])) ?></p>
      <?php if ($r['checked_in_at']): ?>
        <span class="badge b-ok">Attended <?= date('M d, g:i A', strtotime($r['checked_in_at'])) ?></span>
      <?php else: ?><span class="badge b-wait">Not yet checked in</span><?php endif; ?>
      <p><a class="btn gray" target="_blank" href="<?= htmlspecialchars(cal_link($r)) ?>">Add to Google Calendar</a></p>
    </div>
  </div>
<?php endforeach; ?>
<?php include __DIR__ . '/../partials/footer.php'; ?>