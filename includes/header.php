<?php

$pageTitle  = $pageTitle  ?? APP_NAME;
$activeNav  = $activeNav  ?? '';
$isLoggedIn = !empty($_SESSION['user_id']);
$userRole   = $_SESSION['user_role'] ?? '';
$userName   = getSessionUserName();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="EduSkill Marketplace System" />
  <title><?= htmlspecialchars($pageTitle) ?> | <?= APP_NAME ?></title>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />

  <!-- Bootstrap 4 -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />

  <!-- FontAwesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  <!-- AOS (Animate on Scroll) -->
  <link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css" />

  <!-- Main Stylesheet -->
  <link rel="stylesheet" href="<?= APP_URL ?>/css/Main.CSS" />
</head>
<body>

<button id="backToTop" title="Back to top" style="display:none;">
  <i class="fas fa-arrow-up"></i>
</button>

<!-- Toast Container -->
<div class="toast-container-ems"></div>

<nav class="navbar navbar-ems navbar-expand-lg">
  <div class="container">
    <!-- Brand -->
    <a class="navbar-brand d-flex align-items-center" href="<?= APP_URL ?>/pages/index.php">
  <img src="<?= APP_URL ?>/assets/images/logo.png" alt="EduSkill Logo"
       style="height:55px; width:auto; object-fit:contain;">
</a>

    <!-- Mobile Toggle -->
    <button class="navbar-toggler border-0" type="button" data-toggle="collapse"
            data-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false">
      Menu
    </button>

    <!-- Nav Links -->
    <div class="collapse navbar-collapse" id="navbarMain">
      <ul class="navbar-nav mx-auto">
        <li class="nav-item">
          <a class="nav-link <?= $activeNav === 'home' ? 'active' : '' ?>"
             href="<?= APP_URL ?>/pages/index.php">
            Home
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $activeNav === 'courses' ? 'active' : '' ?>"
             href="<?= APP_URL ?>/pages/courses.php">
            Courses
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $activeNav === 'about' ? 'active' : '' ?>"
             href="<?= APP_URL ?>/pages/about.php">
            About
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $activeNav === 'contact' ? 'active' : '' ?>"
             href="<?= APP_URL ?>/pages/contact.php">
            Contact
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $activeNav === 'faq' ? 'active' : '' ?>"
             href="<?= APP_URL ?>/pages/faq.php">
            FAQ
          </a>
        </li>
      </ul>

      <!-- Auth Buttons -->
      <div class="d-flex align-items-center" style="gap:10px;">
        <?php if ($isLoggedIn): ?>
          <!-- Logged-in dropdown -->
          <div class="dropdown">
            <button class="btn-outline-ems dropdown-toggle" type="button"
                    data-toggle="dropdown" style="cursor:pointer;">
              <?= $userName ?>
            </button>
            <div class="dropdown-menu dropdown-menu-right"
                 style="border:1px solid var(--border);border-radius:var(--radius-sm);box-shadow:var(--shadow-md);min-width:180px;padding:8px;">
              <?php if ($userRole === 'learner'): ?>
                <a class="dropdown-item" href="<?= APP_URL ?>/pages/learner-dashboard.php" style="border-radius:6px;padding:8px 14px;font-size:.9rem;">
                  Dashboard
                </a>
              <?php elseif ($userRole === 'provider'): ?>
                <a class="dropdown-item" href="<?= APP_URL ?>/pages/provider-dashboard.php" style="border-radius:6px;padding:8px 14px;font-size:.9rem;">
                  Dashboard
                </a>
              <?php elseif ($userRole === 'officer'): ?>
                <a class="dropdown-item" href="<?= APP_URL ?>/pages/officer-dashboard.php" style="border-radius:6px;padding:8px 14px;font-size:.9rem;">
                  Dashboard
                </a>
              <?php endif; ?>
              <div class="dropdown-divider" style="margin:6px 0;"></div>
              <a class="dropdown-item" href="<?= APP_URL ?>/php/logout.php" style="border-radius:6px;padding:8px 14px;font-size:.9rem;color:var(--danger);">
                Logout
              </a>
            </div>
          </div>
        <?php else: ?>
          <a href="<?= APP_URL ?>/pages/login.php" class="btn-outline-ems">
            Login
          </a>
          <a href="<?= APP_URL ?>/pages/signup.php" class="btn-primary-ems">
            Sign Up
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>