<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Create Event</title></head>
<body>
<h1>Create Event</h1>
<form method="post" action="<?= site_url('events/create') ?>">
  <p>Organization ID: <input type="number" name="organization_id" required></p>
  <p>Title: <input name="title" required></p>
  <p>Description: <textarea name="description"></textarea></p>
  <p>Location: <input name="location" required></p>
  <p>Start: <input type="datetime-local" name="start_at" required></p>
  <p>End: <input type="datetime-local" name="end_at" required></p>
  <p>Capacity: <input type="number" name="capacity"></p>
  <button type="submit">Submit for approval</button>
</form>
</body></html>