<?php $title = 'Review event'; include __DIR__ . '/../partials/header.php'; ?>
<a href="<?= site_url('admin') ?>">&larr; Back to dashboard</a>
<h1>Review event</h1>

<div class="card">
  <?php if (!empty($event['poster_url'])): ?>
    <img class="poster" src="<?= htmlspecialchars($event['poster_url']) ?>" alt="Event poster" style="max-width:100%;max-height:320px;border-radius:8px">
  <?php else: ?><p><i>No poster uploaded</i></p><?php endif; ?>

  <h2><?= htmlspecialchars($event['title']) ?></h2>
  <p><b>Organization:</b> <?= htmlspecialchars($event['organization_name']) ?>
     (<?= $event['scope'] === 'school' ? 'School-wide' : 'Program only' ?>)</p>
  <p><b>Starts:</b> <?= date('M d, Y g:i A', strtotime($event['start_at'])) ?></p>
  <p><b>Ends:</b> <?= date('M d, Y g:i A', strtotime($event['end_at'])) ?></p>
  <p><b>Venue:</b> <?= htmlspecialchars($event['location']) ?></p>
  <p><b>Capacity:</b> <?= $event['capacity'] ? (int) $event['capacity'] : 'Unlimited' ?></p>
  <p><b>Fine per missed scan:</b> <?= Fine_service::peso($event['fine_per_session']) ?></p>
  <p><b>Description:</b><br><?= nl2br(htmlspecialchars((string) $event['description'])) ?: '<i>No description</i>' ?></p>
</div>

<?php if ($event['status'] !== 'pending'): ?>
  <div class="card">
    This event was already reviewed: <b><?= htmlspecialchars($event['status']) ?></b>
    <?php if (!empty($event['review_note'])): ?><p>Reason given: <?= htmlspecialchars($event['review_note']) ?></p><?php endif; ?>
  </div>
<?php else: ?>
  <div class="card">
    <h3>Your decision</h3>
    <p>Check the poster, schedule, venue and description above first.</p>
    <?php if (!empty($error)): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="post" action="<?= site_url('admin/events/' . $event['id'] . '/status') ?>"
          onsubmit="return confirm('Approve this event? Students will be able to see it and register.');">
      <input type="hidden" name="status" value="approved">
      <button class="btn green" type="submit">Approve event</button>
    </form>

    <hr>

    <form method="post" action="<?= site_url('admin/events/' . $event['id'] . '/status') ?>">
      <input type="hidden" name="status" value="rejected">
      <label><b>Not approved? Write the reason.</b> The organization will see this note.</label>
      <textarea name="note" rows="4" minlength="5" maxlength="255" required
        placeholder="Example: The venue is already booked on that date. Please choose another date or place."></textarea>
      <button class="btn red" type="submit">Reject and send reason</button>
    </form>
  </div>
<?php endif; ?>
<?php include __DIR__ . '/../partials/footer.php'; ?>