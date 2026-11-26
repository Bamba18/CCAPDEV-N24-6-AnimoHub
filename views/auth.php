<?php
$title = $tab == 'login' ? 'Login' : 'Sign Up';
$bodyClass = 'loginpage';
require 'views/layout/header.php';
?>
<button id="themeBtn" class="btn outline small themefloat" onclick="toggleTheme()">Dark mode</button>

<div class="authbox">
  <h1>AnimoHub</h1>
  <div class="card">
    <div class="tabs">
      <a class="tab <?= $tab == 'login' ? 'active' : '' ?>" href="index.php?page=login">Login</a>
      <a class="tab <?= $tab == 'signup' ? 'active' : '' ?>" href="index.php?page=signup">Sign Up</a>
    </div>

    <?php if ($tab == 'login'): ?>
    <form method="post" action="index.php?page=login">
      <div class="field">
        <label for="liEmail">DLSU Email</label>
        <input type="email" id="liEmail" name="email" value="<?= e($rememberedEmail ?? '') ?>" placeholder="first_last@dlsu.edu.ph" required>
      </div>
      <div class="field">
        <label for="liPass">Password</label>
        <input type="password" id="liPass" name="password" required>
      </div>
      <div class="field">
        <label><input type="checkbox" name="remember_email" value="1" <?= !empty($rememberedEmail) ? 'checked' : '' ?>> Remember email</label>
      </div>
      <button class="btn" type="submit" style="width:100%">Login</button>
    </form>
    <?php else: ?>
    <form method="post" action="index.php?page=signup">
      <div class="field">
        <label for="suName">Full Name</label>
        <input type="text" id="suName" name="name" value="<?= e($_POST['name'] ?? '') ?>" required>
      </div>
      <div class="field">
        <label for="suEmail">DLSU Email</label>
        <input type="email" id="suEmail" name="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="first_last@dlsu.edu.ph" required>
        <div class="muted">Student signup only. Ask an admin for a professor account.</div>
      </div>
      <div class="field">
        <label for="suPass">Password</label>
        <input type="password" id="suPass" name="password" required  minlength="8" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}" title="At least 8 characters with uppercase, lowercase, number and special character">
      </div>
      <div class="field">
        <label for="suPass2">Confirm Password</label>
        <input type="password" id="suPass2" name="password2" required>
      </div>
      <button class="btn" type="submit" style="width:100%">Create Account</button>
    </form>
    <?php endif; ?>
  </div>

</div>
<?php require 'views/layout/footer.php'; ?>
