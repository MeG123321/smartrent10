<?php
// navbar prosty z obsługą ról i sesji
?>
<header class="navbar">
  <div class="nav-inner container">
    <div class="brand-wrapper" style="display: flex; align-items: center; gap: 8px;">
      <img src="uploads/logo/logo2.png" alt="smartrent logo" style="height: 40px; width: auto;">
      <a class="brand" href="index.php"><?=APP_NAME?></a>
    </div>
    <nav>
      <a href="property_list.php">Oferty</a>
      <a href="help.php">Pomoc</a>
      <?php if (is_logged_in()): ?>
        <a href="user_panel.php">Moje konto</a>
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
          <a href="admin_panel.php">Admin</a>
        <?php endif; ?>
        <a href="logout.php">Wyloguj</a>
      <?php else: ?>
        <a href="login.php">Zaloguj</a>
        <a href="register.php">Zarejestruj</a>
      <?php endif; ?>
    </nav>
  </div>
</header>