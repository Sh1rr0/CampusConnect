<?php $title = 'Attendance'; include __DIR__ . '/../partials/header.php'; ?>
<a href="<?= site_url('admin') ?>">&larr; Dashboard</a>
<h1><?= htmlspecialchars($event['title']) ?></h1>
<p>Registered: <b><?= count($rows) ?></b> · Present: <b style="color:#1e8e3e"><?= $present ?></b> · Absent: <b style="color:#c62828"><?= $absent ?></b></p>
<a class="btn green" href="<?= site_url('admin/events/' . $event['id'] . '/scan') ?>">Open scanner</a>
<table style="margin-top:14px">
  <tr><th>Student</th><th>Email</th><th>Registered</th><th>Status</th><th>Checked in</th></tr>
  <?php foreach ($rows as $r): ?>
  <tr>
    <td><?= htmlspecialchars($r['full_name']) ?></td><td><?= htmlspecialchars($r['email']) ?></td>
    <td><?= date('M d, g:i A', strtotime($r['registered_at'])) ?></td>
    <td><?= $r['present'] ? '<span class="badge b-ok">Present</span>' : '<span class="badge b-no">Absent</span>' ?></td>
    <td><?= $r['checked_in_at'] ? date('M d, g:i A', strtotime($r['checked_in_at'])) : '—' ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php if (empty($rows)): ?><p>No one has registered yet.</p><?php endif; ?>
<?php include __DIR__ . '/../partials/footer.php'; ?>