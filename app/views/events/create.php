<?php $title = 'Create Event'; include __DIR__ . '/../partials/header.php'; ?>
<h1>Create Event</h1>
<?php if (!empty($error)): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form class="card" method="post" action="<?= site_url('events/create') ?>" enctype="multipart/form-data">
  <label>Organization</label>
  <select name="organization_id" required>
    <?php foreach ($orgs as $o): ?><option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['organization_name']) ?></option><?php endforeach; ?>
  </select>
  <label>Title</label><input name="title" required>
  <label>Description</label><textarea name="description" rows="4"></textarea>
  <label>Location</label><input name="location" required>
  <label>Start</label><input type="datetime-local" name="start_at" required>
  <label>End</label><input type="datetime-local" name="end_at" required>
  <label>Capacity (leave empty for unlimited)</label><input type="number" name="capacity" min="1">
  <label>Fine per missed scan (₱)</label><input type="number" name="fine_per_session" min="0" step="0.01" value="25">
  <label>Poster image (uploaded to Cloudinary)</label><input type="file" name="poster" accept="image/*">
  <button class="btn" type="submit">Save event</button>
</form>
<?php include __DIR__ . '/../partials/footer.php'; ?>