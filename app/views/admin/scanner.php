<?php $title = 'Scanner'; include __DIR__ . '/../partials/header.php'; ?>
<a href="<?= site_url('admin') ?>">&larr; Dashboard</a>
<h1>Scan attendance: <?= htmlspecialchars($event['title']) ?></h1>
<div id="reader" style="max-width:420px"></div>
<div id="result" class="msg" style="display:none"></div>
<p><a class="btn" href="<?= site_url('admin/events/' . $event['id'] . '/attendees') ?>">View attendance list</a></p>
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
let busy = false;
function showResult(ok, text) {
  const el = document.getElementById('result');
  el.style.display = 'block'; el.className = 'msg' + (ok ? '' : ' err'); el.textContent = text;
}
function onScan(token) {
  if (busy) return; busy = true;
  const fd = new FormData();
  fd.append('token', token);
  fd.append('event_id', '<?= (int) $event['id'] ?>');
  fetch('<?= site_url('admin/checkin') ?>', { method: 'POST', body: fd })
    .then(r => r.json()).then(d => showResult(d.ok, d.message))
    .catch(() => showResult(false, 'Request failed. Check your connection.'))
    .finally(() => setTimeout(() => { busy = false; }, 2000));
}
new Html5QrcodeScanner('reader', { fps: 10, qrbox: 250 }, false).render(onScan);
</script>
<?php include __DIR__ . '/../partials/footer.php'; ?>