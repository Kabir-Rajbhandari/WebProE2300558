<?php

require_once '../php/config.php';
requireRole('officer');

$db = getDbConnection();

// All providers with full stats
$provStmt = $db->prepare("
    SELECT p.providerID, p.org_name, p.status, u.email, u.name AS contact,
           COUNT(DISTINCT c.courseID) AS course_count,
           COUNT(DISTINCT e.enrollID) AS enroll_count,
           COALESCE(SUM(c.fee), 0) AS revenue_potential,
           COALESCE(AVG(r.rating), 0) AS avg_rating
    FROM providers p
    JOIN users u ON p.userID = u.id
    LEFT JOIN courses c ON p.providerID = c.providerID
    LEFT JOIN enrollments e ON c.courseID = e.courseID AND e.paymentStatus = 'paid'
    LEFT JOIN reviews r ON c.courseID = r.courseID
    GROUP BY p.providerID
    ORDER BY enroll_count DESC
");
$provStmt->execute();
$providers = $provStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Summary stats
$summaryStmt = $db->query("SELECT
    (SELECT COUNT(*) FROM providers WHERE status='approved') AS approved_providers,
    (SELECT COUNT(*) FROM providers WHERE status='pending') AS pending_providers,
    (SELECT COUNT(*) FROM courses) AS total_courses,
    (SELECT COUNT(*) FROM enrollments WHERE paymentStatus='paid') AS total_enrollments,
    (SELECT COUNT(*) FROM users WHERE role='learner') AS total_learners,
    (SELECT COALESCE(SUM(c.fee),0) FROM enrollments e JOIN courses c ON e.courseID=c.courseID WHERE e.paymentStatus='paid') AS total_revenue
");
$summary = $summaryStmt->fetch_assoc();

// Recent enrollments
$recentStmt = $db->query("
    SELECT e.enrollDate, u.name AS learner, c.title AS course, p.org_name, c.fee, e.paymentStatus
    FROM enrollments e
    JOIN users u ON e.learnerID=u.id
    JOIN courses c ON e.courseID=c.courseID
    JOIN providers p ON c.providerID=p.providerID
    ORDER BY e.enrollDate DESC LIMIT 20
");
$recentEnrollments = $recentStmt->fetch_all(MYSQLI_ASSOC);

$db->close();

$pageTitle = 'Reports';
$activeNav = '';
include '../includes/header.php';
?>

<main>
<div class="page-header">
  <div class="container">
    <div class="breadcrumb-ems">
      <a href="<?= APP_URL ?>/pages/officer-dashboard.php">Dashboard</a><span>/</span>
      <span style="color:var(--text);">Reports</span>
    </div>
    <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:12px;">
      <div>
        <h1>System Reports</h1>
        <p style="color:var(--text-muted);margin:0;">Comprehensive analytics for all providers &amp; enrollments.</p>
      </div>
      <button onclick="window.print()" class="btn-primary-ems">
        <i class="fas fa-print"></i> Print Report
      </button>
    </div>
  </div>
</div>

<section class="section-padding-sm">
  <div class="container">
    <?php renderFlash(); ?>

    <!-- Summary Cards -->
    <div style="background:linear-gradient(135deg,var(--primary),var(--accent));border-radius:var(--radius);padding:30px;margin-bottom:28px;"
         data-aos="fade-up">
      <h4 style="color:white;font-size:1rem;margin-bottom:20px;opacity:.85;">
        <i class="fas fa-chart-pie me-2" style="margin-right:8px;"></i>Platform Summary
      </h4>
      <div class="row">
        <?php
        $summaryCards = [
          ['icon'=>'fa-building','label'=>'Active Providers','value'=>number_format($summary['approved_providers'])],
          ['icon'=>'fa-book-open','label'=>'Total Courses','value'=>number_format($summary['total_courses'])],
          ['icon'=>'fa-user-graduate','label'=>'Registered Learners','value'=>number_format($summary['total_learners'])],
          ['icon'=>'fa-certificate','label'=>'Paid Enrollments','value'=>number_format($summary['total_enrollments'])],
          ['icon'=>'fa-wallet','label'=>'Total Revenue','value'=>'RM '.number_format($summary['total_revenue'],2)],
          ['icon'=>'fa-clock','label'=>'Pending Approvals','value'=>number_format($summary['pending_providers'])],
        ];
        foreach ($summaryCards as $i => $sc): ?>
        <div class="col-6 col-md-4 col-lg-2 mb-3" data-aos="fade-up" data-aos-delay="<?= $i*50 ?>">
          <div style="background:rgba(255,255,255,0.15);border-radius:10px;padding:16px;text-align:center;
                      backdrop-filter:blur(4px);">
            <i class="fas <?= $sc['icon'] ?>" style="color:white;font-size:1.3rem;margin-bottom:8px;display:block;opacity:.85;"></i>
            <div style="font-size:1.3rem;font-weight:800;color:white;"><?= $sc['value'] ?></div>
            <div style="font-size:.72rem;color:rgba(255,255,255,.7);margin-top:2px;"><?= $sc['label'] ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Provider Performance Table -->
    <div class="card-ems p-4 mb-4" data-aos="fade-up">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 style="font-size:1rem;font-weight:700;margin:0;">
          <i class="fas fa-building me-2" style="color:var(--primary);margin-right:8px;"></i>Provider Performance Report
        </h4>
        <a href="<?= APP_URL ?>/pages/analytics.php" class="btn-outline-ems" style="padding:7px 14px;font-size:.82rem;">
          <i class="fas fa-chart-bar me-1" style="margin-right:4px;"></i>View Charts
        </a>
      </div>
      <div class="table-responsive-ems">
        <table class="table-ems">
          <thead>
            <tr>
              <th>#</th>
              <th>Provider / Organisation</th>
              <th>Contact</th>
              <th>Courses</th>
              <th>Enrollments</th>
              <th>Avg Rating</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($providers as $i => $prov): ?>
            <tr>
              <td style="color:var(--text-muted);"><?= $i+1 ?></td>
              <td>
                <div style="font-weight:600;"><?= htmlspecialchars($prov['org_name']) ?></div>
                <div style="font-size:.76rem;color:var(--text-muted);"><?= htmlspecialchars($prov['email']) ?></div>
              </td>
              <td style="font-size:.85rem;color:var(--text-muted);"><?= htmlspecialchars($prov['contact']) ?></td>
              <td style="font-weight:600;color:var(--primary);"><?= $prov['course_count'] ?></td>
              <td style="font-weight:700;"><?= $prov['enroll_count'] ?></td>
              <td>
                <div class="star-rating" style="font-size:.75rem;">
                  <?php for($s=1;$s<=5;$s++): ?>
                  <i class="<?= $s<=round($prov['avg_rating'])?'fas':'far' ?> fa-star"></i>
                  <?php endfor; ?>
                </div>
                <span style="font-size:.76rem;color:var(--text-muted);"><?= number_format($prov['avg_rating'],1) ?></span>
              </td>
              <td>
                <span class="badge-status badge-<?= $prov['status'] ?>">
                  <?= ucfirst($prov['status']) ?>
                </span>
              </td>
              <td>
                <a href="<?= APP_URL ?>/pages/analytics.php?provider=<?= $prov['providerID'] ?>" class="btn-outline-ems" style="padding:4px 10px;font-size:.78rem;">
                  <i class="fas fa-chart-bar"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Recent Enrollments -->
    <div class="card-ems p-4" data-aos="fade-up">
      <h4 style="font-size:1rem;font-weight:700;margin-bottom:16px;">
        <i class="fas fa-list me-2" style="color:var(--accent);margin-right:8px;"></i>Recent Enrollments (Last 20)
      </h4>
      <div class="table-responsive-ems">
        <table class="table-ems">
          <thead>
            <tr><th>Date</th><th>Learner</th><th>Course</th><th>Provider</th><th>Fee</th><th>Status</th></tr>
          </thead>
          <tbody>
            <?php foreach ($recentEnrollments as $en): ?>
            <tr>
              <td style="font-size:.82rem;color:var(--text-muted);white-space:nowrap;">
                <?= date('d M Y', strtotime($en['enrollDate'])) ?>
              </td>
              <td style="font-weight:600;font-size:.88rem;"><?= htmlspecialchars($en['learner']) ?></td>
              <td style="font-size:.85rem;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                <?= htmlspecialchars($en['course']) ?>
              </td>
              <td style="font-size:.82rem;color:var(--text-muted);"><?= htmlspecialchars($en['org_name']) ?></td>
              <td style="font-weight:600;">
                <?= $en['fee']>0?'RM '.number_format($en['fee'],2):'Free' ?>
              </td>
              <td>
                <span class="badge-status <?= $en['paymentStatus']==='paid'?'badge-approved':'badge-pending' ?>">
                  <?= ucfirst($en['paymentStatus']) ?>
                </span>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>
</main>

<?php include '../includes/footer.php'; ?>
