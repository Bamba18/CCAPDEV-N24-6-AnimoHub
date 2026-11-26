<?php
$title = 'Access denied';
require 'views/layout/header.php';
?>
<div class="container">
  <div class="card">
    <h1>Access denied</h1>
    <p>Your account does not have permission to do that.</p>
    <a class="btn" href="index.php?page=dashboard">Back to dashboard</a>
  </div>
</div>
<?php require 'views/layout/footer.php'; ?>
