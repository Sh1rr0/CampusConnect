<?php $title = 'Organization accounts'; include __DIR__ . '/../partials/header.php'; ?>
<h1>Organization accounts</h1>
<?php if (!empty($message)): ?><div class="msg"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<form class="card" method="post" action="<?= site_url('admin/accounts') ?>">
  <h3>Add an organization account</h3>
  <p>One manager account per organization or department.</p>

  <label>Manager's full name</label>
  <input name="full_name" value="<?= htmlspecialchars($old['full_name'] ?? '') ?>" required>

  <label>Email (used to log in)</label>
  <input type="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>

  <label>Password (at least 6 characters)</label>
  <input type="text" name="password" required autocomplete="off">

  <label>Organization</label>
  <select name="organization_id">
    <option value="">Select an organization</option>
    <?php foreach ($orgs as $o): ?>
      <option value="<?= $o['id'] ?>" <?= (int) ($old['organization_id'] ?? 0) === (int) $o['id'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($o['organization_name']) ?><?= $o['manager_count'] ? ' (has a manager)' : '' ?>
      </option>
    <?php endforeach; ?>
  </select>

  <label>Or create a new organization</label>
  <input name="new_organization" value="<?= htmlspecialchars($old['new_organization'] ?? '') ?>"
         placeholder="Only fill this if the organization is not in the list">

  <button class="btn green" type="submit">Create account</button>
</form>

<h3>Existing accounts</h3>
<div style="overflow-x:auto"><table>
  <tr><th>Name</th><th>Email</th><th>Organization</th><th>Status</th></tr>
  <?php foreach ($managers as $m): ?>
  <tr>
    <td><?= htmlspecialchars($m['full_name']) ?></td>
    <td><?= htmlspecialchars($m['email']) ?></td>
    <td><?= htmlspecialchars($m['organization_name'] ?? '—') ?></td>
    <td><span class="badge <?= $m['status'] === 'active' ? 'b-ok' : 'b-gray' ?>"><?= htmlspecialchars($m['status']) ?></span></td>
  </tr>
  <?php endforeach; ?>
</table></div>
<?php if (empty($managers)): ?><p>No organization accounts yet.</p><?php endif; ?>
<?php include __DIR__ . '/../partials/footer.php'; ?>