<?php

require_once '../php/config.php';
requireLogin();

if (!in_array($_SESSION['user_role'], ['provider', 'officer'])) {
    redirect(APP_URL . '/pages/error.php?code=403');
}

$role     = $_SESSION['user_role'];
$userId   = $_SESSION['user_id'];
$db       = getDbConnection();

// Determine scope
$provId = null;
if ($role === 'provider') {
    $ps = $db->prepare("SELECT providerID, org_name FROM providers WHERE userID=? AND status='approved' LIMIT 1");
    $ps->bind_param('i', $userId);
    $ps->execute();
    $prov = $ps->get_result()->fetch_assoc();
    if (!$prov) redirect(APP_URL . '/pages/provider-dashboard.php');
    $provId  = $prov['providerID'];
    $scopeName = $prov['org_name'];
} else {
    // Officer can filter by provider
    $filterProvId = (int)($_GET['provider'] ?? 0);
    if ($filterProvId) {
        $fps = $db->prepare("SELECT providerID, org_name FROM providers WHERE providerID=?");
        $fps->bind_param('i', $filterProvId);
        $fps->execute();
        $fp = $fps->get_result()->fetch_assoc();
        $provId    = $fp ? $fp['providerID'] : null;
        $scopeName = $fp ? $fp['org_name'] : 'All Providers';
    } else {
        $scopeName = 'All Providers';
    }
}

// Monthly enrollments (12 months)
if ($provId) {
    $ms = $db->prepare("
        SELECT DATE_FORMAT(e.enrollDate,'%b %Y') AS month_label,
               DATE_FORMAT(e.enrollDate,'%Y-%m') AS month_key,
               COUNT(*) AS count
        FROM enrollments e JOIN courses c ON e.courseID=c.courseID
        WHERE c.providerID=? AND e.paymentStatus='paid'
          AND e.enrollDate >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY month_key,month_label ORDER BY month_key
    ");
    $ms->bind_param('i', $provId);
} else {
    $ms = $db->prepare("
        SELECT DATE_FORMAT(enrollDate,'%b %Y') AS month_label,
               DATE_FORMAT(enrollDate,'%Y-%m') AS month_key,
               COUNT(*) AS count
        FROM enrollments WHERE paymentStatus='paid'
          AND enrollDate >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY month_key,month_label ORDER BY month_key
    ");
}
$ms->execute();
$monthlyData   = $ms->get_result()->fetch_all(MYSQLI_ASSOC);
$monthlyLabels = array_column($monthlyData, 'month_label');
$monthlyCounts = array_map('intval', array_column($monthlyData, 'count'));

// Yearly enrollments
if ($provId) {
    $ys = $db->prepare("
        SELECT YEAR(e.enrollDate) AS yr, COUNT(*) AS count
        FROM enrollments e JOIN courses c ON e.courseID=c.courseID
        WHERE c.providerID=? AND e.paymentStatus='paid'
        GROUP BY yr ORDER BY yr
    ");
    $ys->bind_param('i', $provId);
} else {
    $ys = $db->prepare("
        SELECT YEAR(enrollDate) AS yr, COUNT(*) AS count
        FROM enrollments WHERE paymentStatus='paid'
        GROUP BY yr ORDER BY yr
    ");
}
$ys->execute();
$yearlyData    = $ys->get_result()->fetch_all(MYSQLI_ASSOC);
$yearlyLabels  = array_map(fn($r) => (string)$r['yr'], $yearlyData);
$yearlyCounts  = array_map('intval', array_column($yearlyData, 'count'));

// Rating distribution
if ($provId) {
    $rs = $db->prepare("
        SELECT r.rating, COUNT(*) AS count
        FROM reviews r JOIN courses c ON r.courseID=c.courseID
        WHERE c.providerID=? GROUP BY r.rating ORDER BY r.rating DESC
    ");
    $rs->bind_param('i', $provId);
} else {
    $rs = $db->prepare("SELECT rating, COUNT(*) AS count FROM reviews GROUP BY rating ORDER BY rating DESC");
}
$rs->execute();
$ratingRows  = $rs->get_result()->fetch_all(MYSQLI_ASSOC);
$ratingDist  = array_fill(0, 5, 0);
foreach ($ratingRows as $row) {
    $ratingDist[5 - $row['rating']] = (int)$row['count'];
}

// Top courses
if ($provId) {
    $tcs = $db->prepare("
        SELECT c.title, COUNT(DISTINCT e.enrollID) AS enroll_count,
               COALESCE(AVG(r.rating),0) AS avg_rating,
               COALESCE(a.views, 0) AS view_count
        FROM courses c
        LEFT JOIN enrollments e ON c.courseID=e.courseID AND e.paymentStatus='paid'
        LEFT JOIN reviews r ON c.courseID=r.courseID
        LEFT JOIN analytics a ON c.courseID=a.courseID
        WHERE c.providerID=?
        GROUP BY c.courseID ORDER BY enroll_count DESC LIMIT 8
    ");
    $tcs->bind_param('i', $provId);
} else {
    $tcs = $db->prepare("
        SELECT c.title, COUNT(DISTINCT e.enrollID) AS enroll_count,
               COALESCE(AVG(r.rating),0) AS avg_rating,
               COALESCE(a.views, 0) AS view_count
        FROM courses c
        LEFT JOIN enrollments e ON c.courseID=e.courseID AND e.paymentStatus='paid'
        LEFT JOIN reviews r ON c.courseID=r.courseID
        LEFT JOIN analytics a ON c.courseID=a.courseID
        GROUP BY c.courseID ORDER BY enroll_count DESC LIMIT 8
    ");
}
$tcs->execute();
$topCourses   = $tcs->get_result()->fetch_all(MYSQLI_ASSOC);
$courseLabels = array_map(fn($c) => substr($c['title'], 0, 22), $topCourses);
$courseEnrs   = array_map(fn($c) => (int)$c['enroll_count'], $topCourses);
$courseViews  = array_map(fn($c) => (int)$c['view_count'], $topCourses);

// Provider filter list for officer
$providerList = [];
if ($role === 'officer') {
    $pl = $db->query("SELECT providerID, org_name FROM providers WHERE status='approved' ORDER BY org_name");
    $providerList = $pl->fetch_all(MYSQLI_ASSOC);
}

$db->close();

$pageTitle = 'Analytics';
$activeNav = '';
include '../includes/header.php';
?>

<main>
<div class="page-header">
  <div class="container">
    <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:12px;margin-bottom:16px;">
      <a href="<?= APP_URL ?>/pages/<?= $role==='officer'?'officer-dashboard.php':'provider-dashboard.php' ?>" 
         class="btn-outline-ems" style="padding:10px 20px;display:inline-flex;align-items:center;gap:8px;">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
      </a>
    </div>
    <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:12px;">
      <div>
        <h1>Analytics &amp; Reports</h1>
        <p style="color:var(--text-muted);margin:0;font-size:.9rem;">
          Scope: <strong><?= htmlspecialchars($scopeName) ?></strong>
        </p>
      </div>
      <?php if ($role === 'officer' && !empty($providerList)): ?>
      <form method="GET" action="analytics.php" style="display:flex;gap:8px;align-items:center;">
        <select name="provider" onchange="this.form.submit()"
                style="border:1.5px solid var(--border);border-radius:8px;padding:8px 14px;
                       font-size:.88rem;background:white;color:var(--text);">
          <option value="">All Providers</option>
          <?php foreach ($providerList as $pl): ?>
          <option value="<?= $pl['providerID'] ?>" <?= ($provId??0)==$pl['providerID']?'selected':'' ?>>
            <?= htmlspecialchars($pl['org_name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<section class="section-padding">
  <div class="container">
    <!-- Charts Row 1 -->
    <div class="row mb-5">
      <div class="col-12 mb-4" data-aos="fade-up">
        <div class="card-ems p-4">
          <h5 class="chart-title mb-1">Monthly Enrollments (Last 12 Months)</h5>
          <p class="chart-subtitle mb-3">Paid enrollments by month</p>
          <?php if (empty($monthlyCounts)): ?>
          <div style="height:300px;display:flex;align-items:center;justify-content:center;color:var(--text-muted);">
            <p style="margin:0;"><i class="fas fa-chart-line me-2"></i>No enrollment data available</p>
          </div>
          <?php else: ?>
          <div style="position: relative; height: 350px; width: 100%;">
            <canvas id="monthlyChart"></canvas>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="row mb-5">
      <div class="col-12 mb-4" data-aos="fade-up">
        <div class="card-ems p-4">
          <h5 class="chart-title mb-1">Rating Distribution</h5>
          <p class="chart-subtitle mb-3">All course reviews</p>
          <?php if (array_sum($ratingDist) === 0): ?>
          <div style="height:300px;display:flex;align-items:center;justify-content:center;color:var(--text-muted);">
            <p style="margin:0;"><i class="fas fa-star me-2"></i>No review data available</p>
          </div>
          <?php else: ?>
          <div style="position: relative; height: 350px; width: 100%;">
            <canvas id="ratingChart"></canvas>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Charts Row 3 -->
    <div class="row mb-5">
      <div class="col-12 mb-4" data-aos="fade-up">
        <div class="card-ems p-4">
          <h5 class="chart-title mb-1">Yearly Enrollment Trend</h5>
          <p class="chart-subtitle mb-3">Total enrollments per year</p>
          <?php if (empty($yearlyCounts)): ?>
          <div style="height:300px;display:flex;align-items:center;justify-content:center;color:var(--text-muted);">
            <p style="margin:0;"><i class="fas fa-chart-bar me-2"></i>No enrollment data available</p>
          </div>
          <?php else: ?>
          <div style="position: relative; height: 350px; width: 100%;">
            <canvas id="yearlyChart"></canvas>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Charts Row 4 -->
    <div class="row mb-5">
      <div class="col-12 mb-4" data-aos="fade-up">
        <div class="card-ems p-4">
          <h5 class="chart-title mb-1">Top Courses by Enrollment</h5>
          <p class="chart-subtitle mb-3">Up to 8 most popular courses</p>
          <?php if (empty($courseEnrs)): ?>
          <div style="height:300px;display:flex;align-items:center;justify-content:center;color:var(--text-muted);">
            <p style="margin:0;"><i class="fas fa-book me-2"></i>No course data available</p>
          </div>
          <?php else: ?>
          <div style="position: relative; height: 350px; width: 100%;">
            <canvas id="courseChart"></canvas>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Top Courses Table -->
    <div class="card-ems p-4 mb-5" data-aos="fade-up">
      <h4 style="font-size:1.1rem;font-weight:700;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
        <i class="fas fa-trophy" style="color:var(--warning);"></i>Course Performance Summary
      </h4>
      <div class="table-responsive-ems">
        <table class="table-ems">
          <thead>
            <tr><th>#</th><th>Course Title</th><th>Enrollments</th><th>Avg Rating</th><th>Performance</th></tr>
          </thead>
          <tbody>
            <?php if (empty($topCourses)): ?>
            <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:32px;">No data available.</td></tr>
            <?php else: ?>
            <?php $maxEnr = max(array_column($topCourses, 'enroll_count') ?: [1]); ?>
            <?php foreach ($topCourses as $i => $c): ?>
            <tr>
              <td style="color:var(--text-muted);font-weight:700;"><?= $i+1 ?></td>
              <td style="font-weight:600;max-width:280px;"><?= htmlspecialchars($c['title']) ?></td>
              <td style="font-weight:700;color:var(--primary);"><?= $c['enroll_count'] ?></td>
              <td>
                <div class="star-rating" style="font-size:.8rem;display:inline-block;">
                  <?php for($s=1;$s<=5;$s++): ?>
                  <i class="<?= $s<=round($c['avg_rating'])?'fas':'far' ?> fa-star"></i>
                  <?php endfor; ?>
                </div>
                <span style="font-size:.8rem;color:var(--text-muted);margin-left:4px;">
                  <?= number_format($c['avg_rating'],1) ?>
                </span>
              </td>
              <td>
                <div style="background:var(--border);border-radius:20px;height:8px;width:120px;overflow:hidden;">
                  <?php $pct = $maxEnr > 0 ? round(($c['enroll_count']/$maxEnr)*100) : 0; ?>
                  <div style="width:<?= max($pct, 2) ?>%;height:100%;border-radius:20px;
                              background:linear-gradient(90deg,var(--primary),var(--accent));min-width:4px;transition:width 0.3s ease;"></div>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>
</main>

<?php include '../includes/footer.php'; ?>

<script>
(function() {
  function initCharts() {
    <?php if (!empty($monthlyCounts)): ?>
    initMonthlyEnrollmentChart('monthlyChart',
      <?= json_encode($monthlyLabels) ?>,
      <?= json_encode($monthlyCounts) ?>
    );
    <?php endif; ?>

    <?php if (!empty($yearlyCounts)): ?>
    initYearlyTrendChart('yearlyChart',
      <?= json_encode($yearlyLabels) ?>,
      <?= json_encode($yearlyCounts) ?>
    );
    <?php endif; ?>

    <?php if (array_sum($ratingDist) > 0): ?>
    initRatingChart('ratingChart', <?= json_encode($ratingDist) ?>);
    <?php endif; ?>

    <?php if (!empty($courseEnrs)): ?>
    initCourseViewsChart('courseChart',
      <?= json_encode($courseLabels) ?>,
      <?= json_encode($courseViews) ?>,
      <?= json_encode($courseEnrs) ?>
    );
    <?php endif; ?>
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCharts);
  } else {
    initCharts();
  }
})();
</script>