<?php $title = 'Edit Event'; include __DIR__ . '/../partials/header.php'; ?>
<a href="<?= site_url('admin') ?>">&larr; Dashboard</a>
<h1>Edit Event</h1>
<?php if (!empty($error)): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form class="card" method="post" action="<?= site_url('admin/events/' . $event['id'] . '/edit') ?>" enctype="multipart/form-data">
  <label>Organization</label>
  <select name="organization_id" required>
    <?php foreach ($orgs as $o): ?>
      <option value="<?= $o['id'] ?>" <?= $o['id'] == $event['organization_id'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($o['organization_name']) ?></option>
    <?php endforeach; ?>
  </select>
  <label>Title</label><input name="title" value="<?= htmlspecialchars($event['title']) ?>" required>
  <label>Description</label><textarea name="description" rows="4"><?= htmlspecialchars((string) $event['description']) ?></textarea>
  <label>Location</label><input name="location" value="<?= htmlspecialchars($event['location']) ?>" required>
  <label>Start</label><input type="datetime-local" name="start_at" value="<?= date('Y-m-d\TH:i', strtotime($event['start_at'])) ?>" required>
  <label>End</label><input type="datetime-local" name="end_at" value="<?= date('Y-m-d\TH:i', strtotime($event['end_at'])) ?>" required>
  <label>Capacity (<?= (int) $event['registered_count'] ?> registered so far)</label>
  <input type="number" name="capacity" min="1" value="<?= $event['capacity'] ? (int) $event['capacity'] : '' ?>">
  <label>Fine per missed scan (₱)</label>
  <input type="number" name="fine_per_session" min="0" step="0.01" value="<?= htmlspecialchars($event['fine_per_session']) ?>">
  <label>Poster (leave empty to keep the current one)</label>
  <?php if (!empty($event['poster_url'])): ?><img class="poster" src="<?= htmlspecialchars($event['poster_url']) ?>" alt="Current poster"><?php endif; ?>
  <input type="file" name="poster" accept="image/*">
  <button class="btn" type="submit">Save changes</button>
</form>
<?php if ($me['role'] === 'admin'): ?>
<div class="card" style="border:1px solid #c62828">
  <h3 style="color:#c62828">Danger zone</h3>
  <p>Permanently deletes this event with its <b><?= (int) $event['registered_count'] ?></b> registrations,
     all attendance scans and fines. This cannot be undone.
     To keep the records, cancel the event with the normal Delete button instead.</p>
  <form method="post" action="<?= site_url('admin/events/' . $event['id'] . '/delete-permanently') ?>"
        onsubmit="var t = prompt('This permanently deletes the event, its registrations, attendance and fines.\n\nType DELETE to confirm:'); if (t !== 'DELETE') return false; this.typed.value = t; return true;">
    <input type="hidden" name="typed" value="">
    <button class="btn red" type="submit">Delete permanently</button>
  </form>
</div>
<?php endif; ?>
<?php include __DIR__ . '/../partials/footer.php'; ?>