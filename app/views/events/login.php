<?php $title = 'Login'; include __DIR__ . '/../partials/header.php'; ?>
<h1>Login</h1>
<?php if (!empty($error)): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form class="card" method="post" action="<?= site_url('login') ?>" style="max-width:380px">
  <label>Email</label><input type="email" name="email" required>
  <label>Password</label><input type="password" name="password" required>
  <button class="btn" type="submit">Login</button>
  <p>No account? <a href="<?= site_url('signup') ?>">Sign up</a></p>
</form>
<?php include __DIR__ . '/../partials/footer.php'; ?>