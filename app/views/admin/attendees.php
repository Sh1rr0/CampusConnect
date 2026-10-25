<?php $title = 'Attendance & Fines'; include __DIR__ . '/../partials/header.php'; ?>
<a href="<?= site_url('admin') ?>">&larr; Dashboard</a>
<h1><?= htmlspecialchars($event['title']) ?></h1>
<?php $ended = strtotime($event['end_at']) < time(); ?>
<p>Registered: <b><?= count($rows) ?></b> ·
   Fine per missed scan: <b><?= Fine_service::peso($event['fine_per_session']) ?></b> ·
   Total fines: <b style="color:#c62828"><?= $ended ? Fine_service::peso($total_fines) : 'Pending (event not ended yet)' ?></b></p>
<a class="btn green" href="<?= site_url('admin/events/' . $event['id'] . '/scan') ?>">Open scanner</a>
<div style="overflow-x:auto"><table style="margin-top:14px">
  <tr>
    <th>Student</th>
    <?php foreach (Fine_service::$slots as $label): ?><th><?= $label ?></th><?php endforeach; ?>
    <th>Attended</th><th>Fine</th>
  </tr>
  <?php foreach ($rows as $r): $s = $r['summary']; ?>
  <tr>
    <td><?= htmlspecialchars($r['full_name']) ?><br><small><?= htmlspecialchars($r['email']) ?></small></td>
    <?php foreach (Fine_service::$slots as $key => $label): $t = $r['scans'][$key] ?? null; ?>
      <td><?= $t ? '✔ ' . date('g:i A', strtotime($t))
                 : ($s['final'] ? '<span style="color:#c62828">✖ missed</span>' : '—') ?></td>
    <?php endforeach; ?>
    <td><?= $s['attended'] ?>/<?= count(Fine_service::$slots) ?></td>
    <td><?= $s['final'] ? ($s['fine'] > 0 ? '<b style="color:#c62828">' . Fine_service::peso($s['fine']) . '</b>' : '₱0.00') : 'Pending' ?></td>
  </tr>
  <?php endforeach; ?>
</table></div>
<?php if (empty($rows)): ?><p>No one has registered yet.</p><?php endif; ?>
<?php include __DIR__ . '/../partials/footer.php'; ?>