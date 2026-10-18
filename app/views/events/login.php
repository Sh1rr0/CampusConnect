<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Login</title></head>
<body>
<h1>Login</h1>
<?php if (!empty($error)): ?><p style="color:red"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post" action="<?= site_url('login') ?>">
  <p>Email: <input type="email" name="email" required></p>
  <p>Password: <input type="password" name="password" required></p>
  <button type="submit">Login</button>
</form>
</body></html>