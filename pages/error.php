<?php

require_once 'php/config.php';

$code = (int)($_GET['code'] ?? 404);
$validCodes = [400, 403, 404, 500];
if (!in_array($code, $validCodes)) $code = 404;

http_response_code($code);

$codeMap = [
    400 => ['title'=>'Bad Request',       'msg'=>'The server could not process your request.'],
    403 => ['title'=>'Access Denied',     'msg'=>'You do not have permission to access this page.'],
    404 => ['title'=>'Page Not Found',    'msg'=>'The page you are looking for doesn\'t exist or has been moved.'],
    500 => ['title'=>'Server Error',      'msg'=>'Something went wrong on our end. Please try again later.'],
];

$info = $codeMap[$code];
$pageTitle = $code . ' ' . $info['title'];
include 'includes/header.php';
?>

<main>
<div class="error-page">
  <div class="container text-center" data-aos="fade-up">
    <div class="error-code"><?= $code ?></div>
    <h2 style="font-size:1.8rem;margin-bottom:12px;"><?= $info['title'] ?></h2>
    <p style="color:var(--text-muted);max-width:480px;margin:0 auto 32px;font-size:1rem;">
      <?= $info['msg'] ?>
    </p>
    <div style="display:flex;justify-content:center;gap:12px;flex-wrap:wrap;">
      <a href="index.php" class="btn-primary-ems" style="padding:12px 28px;font-size:1rem;">
        <i class="fas fa-home"></i> Go Home
      </a>
      <a href="javascript:history.back()" class="btn-outline-ems" style="padding:12px 28px;font-size:1rem;">
        <i class="fas fa-arrow-left"></i> Go Back
      </a>
      <a href="contact.php" class="btn-accent-ems" style="padding:12px 28px;font-size:1rem;">
        <i class="fas fa-envelope"></i> Contact Support
      </a>
    </div>
    <div style="margin-top:48px;">
      <p style="font-size:.85rem;color:var(--text-muted);margin-bottom:14px;">You might be looking for:</p>
      <div style="display:flex;justify-content:center;gap:10px;flex-wrap:wrap;">
        <?php
        $links = [
          ['url'=>'courses.php','icon'=>'fa-book-open','label'=>'Browse Courses'],
          ['url'=>'login.php','icon'=>'fa-sign-in-alt','label'=>'Login'],
          ['url'=>'signup.php','icon'=>'fa-user-plus','label'=>'Register'],
          ['url'=>'about.php','icon'=>'fa-info-circle','label'=>'About Us'],
        ];
        foreach ($links as $link): ?>
        <a href="<?= $link['url'] ?>" style="display:inline-flex;align-items:center;gap:6px;
           padding:8px 16px;border-radius:8px;border:1px solid var(--border);font-size:.85rem;
           color:var(--text-muted);transition:all .3s;"
           onmouseover="this.style.background='var(--primary-light)';this.style.color='var(--primary)';this.style.borderColor='var(--primary)'"
           onmouseout="this.style.background='transparent';this.style.color='var(--text-muted)';this.style.borderColor='var(--border)'">
          <i class="fas <?= $link['icon'] ?>"></i> <?= $link['label'] ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
</main>

<?php include 'includes/footer.php'; ?>
