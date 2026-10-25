<?php $title = 'My QR Passes'; include __DIR__ . '/../partials/header.php'; ?>
<h1>My QR Passes</h1>
<?php if (!empty($regs)): ?>
  <div class="card"><b>Total fines: <span style="color:#c62828"><?= Fine_service::peso($total_fines) ?></span></b>
  <br><small>Fines become final after each event ends.</small></div>
<?php else: ?>
  <p>You haven't registered for any events yet. <a href="<?= site_url('events') ?>">Browse events</a></p>
<?php endif; ?>
<?php foreach ($regs as $r): $s = $r['summary']; ?>
  <div class="card" style="display:flex;gap:20px;flex-wrap:wrap;align-items:center">
    <img src="<?= $r['qr'] ?>" alt="QR pass" width="180" height="180">
    <div>
      <h3><?= htmlspecialchars($r['title']) ?></h3>
      <p><?= htmlspecialchars($r['location']) ?><br> <?= date('M d, Y g:i A', strtotime($r['start_at'])) ?></p>
      <p>
        <?php foreach (Fine_service::$slots as $key => $label): $t = $r['scans'][$key] ?? null; ?>
          <?php if ($t): ?><span class="badge b-ok"><?= $label ?> ✔ <?= date('g:i A', strtotime($t)) ?></span>
          <?php elseif ($s['final']): ?><span class="badge b-no"><?= $label ?> ✖ missed</span>
          <?php else: ?><span class="badge b-gray"><?= $label ?> —</span><?php endif; ?>
        <?php endforeach; ?>
      </p>
      <p><b>Fine:</b>
        <?php if ($s['final']): ?>
          <?= Fine_service::peso($s['fine']) ?> (<?= $s['missed'] ?> missed × <?= Fine_service::peso($s['rate']) ?>)
        <?php else: ?>Pending until the event ends<?php endif; ?>
      </p>
      <p><a class="btn gray" target="_blank" href="<?= htmlspecialchars(cal_link($r)) ?>">Add to Google Calendar</a></p>
    </div>
  </div>
<?php endforeach; ?>
<?php include __DIR__ . '/../partials/footer.php'; ?>