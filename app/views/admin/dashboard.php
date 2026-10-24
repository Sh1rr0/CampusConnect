<?php $title = 'Dashboard'; include __DIR__ . '/../partials/header.php'; ?>
<h1>Event Dashboard</h1>
<div style="overflow-x:auto"><table>
  <tr><th>Event</th><th>Date</th><th>Status</th><th>Registered</th><th>Present</th><th>Absent</th><th>Actions</th></tr>
  <?php foreach ($events as $e):
    $absent = (int) $e['registered_count'] - (int) $e['present_count'];
    $cls = array('approved' => 'b-ok', 'pending' => 'b-wait', 'rejected' => 'b-no', 'cancelled' => 'b-gray'); ?>
  <tr>
    <td><?= htmlspecialchars($e['title']) ?><br><small><?= htmlspecialchars($e['organization_name']) ?></small></td>
    <td><?= date('M d, Y g:i A', strtotime($e['start_at'])) ?></td>
    <td><span class="badge <?= $cls[$e['status']] ?? 'b-gray' ?>"><?= htmlspecialchars($e['status']) ?></span></td>
    <td><?= (int) $e['registered_count'] ?></td><td><?= (int) $e['present_count'] ?></td><td><?= $absent ?></td>
    <td>
      <a class="btn" href="<?= site_url('admin/events/' . $e['id'] . '/attendees') ?>">Attendance</a>
      <a class="btn green" href="<?= site_url('admin/events/' . $e['id'] . '/scan') ?>">Scan</a>
      <?php if ($me['role'] === 'admin' && $e['status'] === 'pending'): ?>
        <form method="post" action="<?= site_url('admin/events/' . $e['id'] . '/status') ?>" style="display:inline">
          <button class="btn green" name="status" value="approved">Approve</button>
          <button class="btn red" name="status" value="rejected">Reject</button>
        </form>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
</table></div>
<?php if (empty($events)): ?><p>No events yet.</p><?php endif; ?>
<?php include __DIR__ . '/../partials/footer.php'; ?>