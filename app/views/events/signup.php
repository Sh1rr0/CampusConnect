<?php $title = 'Sign up'; include __DIR__ . '/../partials/header.php'; ?>
<h1>Create a student account</h1>
<?php if (!empty($error)): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form class="card" method="post" action="<?= site_url('signup') ?>" style="max-width:380px">
  <label>Full name</label><input name="full_name" required>
  <label>Email</label><input type="email" name="email" required>
  <label>Password (min 6 characters)</label><input type="password" name="password" required>
  <button class="btn" type="submit">Sign up</button>
</form>
<?php include __DIR__ . '/../partials/footer.php'; ?>