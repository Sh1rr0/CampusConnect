<?php $title = 'Dashboard'; include __DIR__ . '/../partials/header.php'; ?>
<h1>Event Dashboard</h1>
<?php if (!empty($message)): ?><div class="msg"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<div style="overflow-x:auto"><table>
  <tr><th>Event</th><th>Date</th><th>Status</th><th>Registered</th><th>Attended (1+ scan)</th><th>Fines</th><th>Actions</th></tr>
  <?php foreach ($events as $e):
    $cls   = array('approved' => 'b-ok', 'pending' => 'b-wait', 'rejected' => 'b-no', 'cancelled' => 'b-gray');
    $ended = strtotime($e['end_at']) < time();
    $fines = $e['status'] === 'cancelled' ? 0
    : ((int) $e['registered_count'] * count(Fine_service::$slots) - (int) $e['scan_count']) * (float) $e['fine_per_session']; ?>
  <tr>
    <td><?= htmlspecialchars($e['title']) ?><br><small><?= htmlspecialchars($e['organization_name']) ?></small></td>
    <td><?= date('M d, Y g:i A', strtotime($e['start_at'])) ?></td>
    <td><span class="badge <?= $cls[$e['status']] ?? 'b-gray' ?>"><?= htmlspecialchars($e['status']) ?></span></td>
    <td><?= (int) $e['registered_count'] ?></td>
    <td><?= (int) $e['present_count'] ?></td>
    <td><?= $ended ? Fine_service::peso($fines) : 'Pending' ?></td>
   <td>
  <a class="btn" href="<?= site_url('admin/events/' . $e['id'] . '/attendees') ?>">Attendance &amp; fines</a>
  <?php if ($e['status'] !== 'cancelled'): ?>
    <a class="btn green" href="<?= site_url('admin/events/' . $e['id'] . '/scan') ?>">Scan</a>
  <?php endif; ?>
  <a class="btn gray" href="<?= site_url('admin/events/' . $e['id'] . '/edit') ?>">Edit</a>

  <?php if ($e['status'] === 'cancelled'): ?>
    <form method="post" action="<?= site_url('admin/events/' . $e['id'] . '/reopen') ?>" style="display:inline"
          onsubmit="return confirm('Reopen this event?');">
      <button class="btn green" type="submit">Reopen event</button>
    </form>
  <?php elseif (in_array($e['status'], array('approved', 'pending'))): ?>
    <form method="post" action="<?= site_url('admin/events/' . $e['id'] . '/cancel') ?>" style="display:inline"
          onsubmit="return confirm('Cancel this event? Students keep their registrations, but no fines apply while it is cancelled.');">
      <button class="btn red" type="submit">Cancel event</button>
    </form>
  <?php endif; ?>

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