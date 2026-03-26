<?php
// ---> Officer dashboard for managing provider approvals and system analytics

require_once '../php/config.php';
requireRole('officer');

$pageTitle = 'Officer Dashboard';
$userId    = $_SESSION['user_id'];
$db        = getDbConnection();

// Handle approve or reject action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');
    $provider_id = (int)($_POST['provider_id'] ?? 0);

    if (in_array($action, ['approve', 'reject']) && $provider_id > 0) {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        $update_stmt = $db->prepare("UPDATE providers SET status=? WHERE providerID=?");
        $update_stmt->bind_param('si', $status, $provider_id);
        $update_stmt->execute();
        setFlash('success', 'Provider registration has been ' . $status . '.');
        redirect(APP_URL . '/officer-dashboard.php');
    }
}

// Get pending provider approvals
$pending_stmt = $db->prepare("
    SELECT p.*, u.name, u.email, u.phone
    FROM providers p JOIN users u ON p.userID = u.id
    WHERE p.status = 'pending'
    ORDER BY p.providerID DESC
");
$pending_stmt->execute();
$pending_providers = $pending_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get all providers with course and enrollment counts
$all_providers_stmt = $db->prepare("
    SELECT p.*, u.name AS contact_name, u.email,
           COUNT(DISTINCT c.courseID) AS course_count,
           COUNT(DISTINCT e.enrollID) AS enroll_count
    FROM providers p
    JOIN users u ON p.userID = u.id
    LEFT JOIN courses c ON p.providerID = c.providerID
    LEFT JOIN enrollments e ON c.courseID = e.courseID AND e.paymentStatus='paid'
    GROUP BY p.providerID
    ORDER BY p.status, p.providerID DESC
");
$all_providers_stmt->execute();
$all_providers = $all_providers_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get system statistics
$stats_row = $db->query("SELECT
    (SELECT COUNT(*) FROM users WHERE role='learner') AS total_learners,
    (SELECT COUNT(*) FROM providers WHERE status='approved') AS total_providers,
    (SELECT COUNT(*) FROM courses) AS total_courses,
    (SELECT COUNT(*) FROM enrollments WHERE paymentStatus='paid') AS total_enrollments,
    (SELECT COUNT(*) FROM providers WHERE status='pending') AS pending_count,
    (SELECT SUM(c.fee) FROM enrollments e JOIN courses c ON e.courseID=c.courseID WHERE e.paymentStatus='paid') AS total_revenue
")->fetch_assoc();

// Get monthly enrollment trends
$system_monthly = $db->query("
    SELECT DATE_FORMAT(enrollDate,'%b %Y') AS month_label,
           DATE_FORMAT(enrollDate,'%Y-%m') AS month_key,
           COUNT(*) AS count
    FROM enrollments WHERE paymentStatus='paid'
      AND enrollDate >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month_key, month_label
    ORDER BY month_key
")->fetch_all(MYSQLI_ASSOC);

$system_monthly_labels = array_column($system_monthly, 'month_label');
$system_monthly_counts = array_column($system_monthly, 'count');

// Reports data
$report_providers = $db->query("
    SELECT p.providerID, p.org_name, p.status, u.email, u.name AS contact,
           COUNT(DISTINCT c.courseID) AS course_count,
           COUNT(DISTINCT e.enrollID) AS enroll_count,
           COALESCE(AVG(r.rating), 0) AS avg_rating
    FROM providers p
    JOIN users u ON p.userID = u.id
    LEFT JOIN courses c ON p.providerID = c.providerID
    LEFT JOIN enrollments e ON c.courseID = e.courseID AND e.paymentStatus = 'paid'
    LEFT JOIN reviews r ON c.courseID = r.courseID
    GROUP BY p.providerID
    ORDER BY enroll_count DESC
")->fetch_all(MYSQLI_ASSOC);

$report_summary = $db->query("SELECT
    (SELECT COUNT(*) FROM providers WHERE status='approved') AS approved_providers,
    (SELECT COUNT(*) FROM courses) AS total_courses,
    (SELECT COUNT(*) FROM enrollments WHERE paymentStatus='paid') AS total_enrollments,
    (SELECT COUNT(*) FROM users WHERE role='learner') AS total_learners,
    (SELECT COALESCE(SUM(c.fee),0) FROM enrollments e JOIN courses c ON e.courseID=c.courseID WHERE e.paymentStatus='paid') AS total_revenue
")->fetch_assoc();

$recent_enrollments = $db->query("
    SELECT e.enrollDate, u.name AS learner, c.title AS course, p.org_name, c.fee, e.paymentStatus
    FROM enrollments e
    JOIN users u ON e.learnerID=u.id
    JOIN courses c ON e.courseID=c.courseID
    JOIN providers p ON c.providerID=p.providerID
    ORDER BY e.enrollDate DESC LIMIT 15
")->fetch_all(MYSQLI_ASSOC);

$db->close();
include '../includes/header.php';
?>

<main>
<div class="dashboard-wrapper">
  <!-- SIDEBAR -->
  <aside class="dashboard-sidebar">
    <div class="sidebar-user">
      <div class="sidebar-avatar" style="font-size: 1.5rem;">
        <i class="fas fa-user" style="color: white;"></i>
      </div>
      <div class="sidebar-user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
      <div class="sidebar-user-role">Ministry Officer</div>
    </div>
    <nav class="sidebar-nav">
      <div class="sidebar-section-title">Dashboard</div>
      <div class="nav-item">
        <a href="#" class="nav-link active" onclick="showSection('overview',this);return false;">
          <i class="fas fa-tachometer-alt" style="width:18px;margin-right:8px;"></i>Overview
        </a>
      </div>
      <div class="nav-item">
        <a href="#" class="nav-link" onclick="showSection('approvals',this);return false;">
          <i class="fas fa-user-check" style="width:18px;margin-right:8px;"></i>Pending Approvals
          <?php if ($stats_row['pending_count']>0): ?>
          <span style="background:var(--danger);color:white;border-radius:20px;padding:1px 8px;font-size:.72rem;margin-left:auto;"><?= $stats_row['pending_count'] ?></span>
          <?php endif; ?>
        </a>
      </div>
      <div class="nav-item">
        <a href="#" class="nav-link" onclick="showSection('providers',this);return false;">
          <i class="fas fa-building" style="width:18px;margin-right:8px;"></i>All Providers
        </a>
      </div>
      <div class="nav-item">
        <a href="#" class="nav-link" onclick="showSection('analytics',this);return false;">
          <i class="fas fa-chart-line" style="width:18px;margin-right:8px;"></i>Analytics
        </a>
      </div>
      <div class="nav-item">
        <a href="#" class="nav-link" onclick="showSection('reports',this);return false;">
          <i class="fas fa-file-alt" style="width:18px;margin-right:8px;"></i>Reports
        </a>
      </div>
      <div class="sidebar-section-title">System</div>
      <div class="nav-item">
        <a href="<?= APP_URL ?>/php/logout.php" class="nav-link" style="color:rgba(239,68,68,.8) !important;">
          <i class="fas fa-sign-out-alt" style="width:18px;margin-right:8px;"></i>Logout
        </a>
      </div>
    </nav>
  </aside>

  <!-- MAIN -->
  <div class="dashboard-main">
    <?php renderFlash(); ?>

    <!-- OVERVIEW -->
    <section id="section-overview">
      <div class="d-flex align-items-center justify-content-between mb-4" data-aos="fade-down">
        <div>
          <h1 style="font-size:1.5rem;margin-bottom:4px;">Ministry Officer Dashboard</h1>
          <p style="color:var(--text-muted);margin:0;font-size:.9rem;"><?= date('l, d F Y') ?></p>
        </div>
        <?php if ($stats_row['pending_count']>0): ?>
        <button class="btn-primary-ems" style="background:var(--danger);"
                onclick="showSection('approvals',document.querySelector('[onclick*=approvals]'))">
          <?= $stats_row['pending_count'] ?> Pending
        </button>
        <?php endif; ?>
      </div>

      <div class="row mb-4">
        <?php
        $officer_stats = [
          ['color'=>'blue','value'=>number_format($stats_row['total_learners']),'label'=>'Registered Learners','icon'=>'fa-user-graduate'],
          ['color'=>'purple','value'=>number_format($stats_row['total_providers']),'label'=>'Training Providers','icon'=>'fa-building'],
          ['color'=>'green','value'=>number_format($stats_row['total_courses']),'label'=>'Active Courses','icon'=>'fa-book-open'],
          ['color'=>'orange','value'=>number_format($stats_row['total_enrollments']),'label'=>'Total Enrollments','icon'=>'fa-user-check'],
          ['color'=>'green','value'=>'RM '.number_format($stats_row['total_revenue']??0,2),'label'=>'Platform Revenue','icon'=>'fa-chart-bar'],
          ['color'=>'red','value'=>number_format($stats_row['pending_count']),'label'=>'Pending Approvals','icon'=>'fa-hourglass-end'],
        ];
        foreach ($officer_stats as $i => $stat_card):
        ?>
        <div class="col-6 col-xl-4 mb-3" data-aos="fade-up" data-aos-delay="<?= $i*60 ?>">
          <div class="dash-stat-card">
            <div class="dash-stat-icon <?= $stat_card['color'] ?>">
              <i class="fas <?= $stat_card['icon'] ?>" style="font-size:1.6rem;"></i>
            </div>
            <div>
              <div class="dash-stat-value" style="font-size:1.4rem;"><?= $stat_card['value'] ?></div>
              <div class="dash-stat-label"><?= $stat_card['label'] ?></div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="card-ems p-4" data-aos="fade-up" data-aos-delay="300">
        <div class="chart-title">System-Wide Monthly Enrollments</div>
        <div class="chart-subtitle">All paid enrollments across all providers</div>
        <div style="height:260px;"><canvas id="sysMonthlyChart"></canvas></div>
      </div>
    </section>

    <!-- APPROVALS -->
    <section id="section-approvals" style="display:none;">
      <div class="mb-4">
        <h2 style="font-size:1.4rem;margin-bottom:4px;">
          <i class="fas fa-user-check me-2" style="color:var(--warning);margin-right:8px;"></i>Pending Provider Registrations
        </h2>
        <p style="color:var(--text-muted);margin:0;font-size:.9rem;">Review and approve or reject training provider applications.</p>
      </div>

      <?php if (empty($pending_providers)): ?>
      <div class="text-center py-5" style="background:white;border-radius:var(--radius);border:1px solid var(--border);">
        <i class="fas fa-check-circle" style="font-size:2.5rem;color:var(--success);display:block;margin-bottom:14px;"></i>
        <h5 style="margin-bottom:6px;">All caught up!</h5>
        <p style="color:var(--text-muted);">No pending provider registrations at this time.</p>
      </div>
      <?php else: ?>
      <div class="row">
        <?php foreach ($pending_providers as $i => $prov): ?>
        <div class="col-md-6 mb-4" data-aos="fade-up" data-aos-delay="<?= $i*80 ?>">
          <div class="card-ems p-4" style="border-left:4px solid var(--warning);">
            <div class="d-flex align-items-start gap-3 mb-3" style="gap:14px;">
              <div class="provider-logo" style="width:48px;height:48px;flex-shrink:0;background:linear-gradient(135deg,#fffbeb,#fef3c7);">
                <i class="fas fa-building" style="color:var(--warning);font-size:1.1rem;"></i>
              </div>
              <div style="flex:1;min-width:0;">
                <h5 style="font-size:.95rem;font-weight:700;margin-bottom:2px;"><?= htmlspecialchars($prov['org_name']) ?></h5>
                <div style="font-size:.82rem;color:var(--text-muted);">
                  <i class="fas fa-user me-1" style="margin-right:3px;"></i><?= htmlspecialchars($prov['name']) ?>
                </div>
              </div>
              <span class="badge-status badge-pending">Pending</span>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px;font-size:.82rem;">
              <div style="color:var(--text-muted);">
                <i class="fas fa-envelope me-1" style="margin-right:3px;"></i><?= htmlspecialchars($prov['email']) ?>
              </div>
              <div style="color:var(--text-muted);">
                <i class="fas fa-phone me-1" style="margin-right:3px;"></i><?= htmlspecialchars($prov['phone'] ?? 'N/A') ?>
              </div>
            </div>

            <?php if ($prov['org_profile']): ?>
            <p style="font-size:.83rem;color:var(--text-muted);margin-bottom:14px;
                      display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;">
              <?= htmlspecialchars($prov['org_profile']) ?>
            </p>
            <?php endif; ?>

            <?php if ($prov['document_path']): ?>
            <div style="margin-bottom:14px;">
              <a href="<?= APP_URL . '/' . htmlspecialchars($prov['document_path']) ?>"
                 target="_blank" class="btn-outline-ems" style="padding:6px 14px;font-size:.82rem;">
                <i class="fas fa-file-alt"></i> View Document
              </a>
            </div>
            <?php endif; ?>

            <div style="display:flex;gap:8px;">
              <form method="POST" action="officer-dashboard.php" style="flex:1;">
                <input type="hidden" name="action" value="approve" />
                <input type="hidden" name="provider_id" value="<?= $prov['providerID'] ?>" />
                <button type="submit" class="btn-primary-ems w-100"
                        style="padding:9px;background:var(--success);justify-content:center;font-size:.88rem;">
                  <i class="fas fa-check"></i> Approve
                </button>
              </form>
              <form method="POST" action="officer-dashboard.php" style="flex:1;">
                <input type="hidden" name="action" value="reject" />
                <input type="hidden" name="provider_id" value="<?= $prov['providerID'] ?>" />
                <button type="submit" class="btn-primary-ems w-100"
                        style="padding:9px;background:var(--danger);justify-content:center;font-size:.88rem;"
                        onclick="return confirm('Reject this provider application?')">
                  <i class="fas fa-times"></i> Reject
                </button>
              </form>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </section>

    <!-- ALL PROVIDERS -->
    <section id="section-providers" style="display:none;">
      <h2 style="font-size:1.4rem;margin-bottom:20px;">
        <i class="fas fa-building me-2" style="color:var(--primary);margin-right:8px;"></i>All Training Providers
      </h2>
      <div class="table-responsive-ems">
        <table class="table-ems">
          <thead>
            <tr><th>Provider</th><th>Contact</th><th>Courses</th><th>Enrollments</th><th>Status</th><th>Document</th></tr>
          </thead>
          <tbody>
            <?php foreach ($all_providers as $prov): ?>
            <tr>
              <td>
                <div style="font-weight:600;"><?= htmlspecialchars($prov['org_name']) ?></div>
                <div style="font-size:.78rem;color:var(--text-muted);"><?= htmlspecialchars($prov['email']) ?></div>
              </td>
              <td style="font-size:.85rem;color:var(--text-muted);"><?= htmlspecialchars($prov['contact_name']) ?></td>
              <td style="font-weight:600;"><?= $prov['course_count'] ?></td>
              <td style="font-weight:600;"><?= $prov['enroll_count'] ?></td>
              <td>
                <span class="badge-status badge-<?= $prov['status'] ?>">
                  <?= ucfirst($prov['status']) ?>
                </span>
              </td>
              <td>
                <?php if ($prov['document_path']): ?>
                <a href="<?= APP_URL . '/' . htmlspecialchars($prov['document_path']) ?>"
                   target="_blank" class="btn-outline-ems" style="padding:4px 10px;font-size:.78rem;">
                  <i class="fas fa-file"></i> View
                </a>
                <?php else: ?>
                <span style="color:var(--text-muted);font-size:.82rem;">None</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- ANALYTICS -->
    <section id="section-analytics" style="display:none;">
      <h2 style="font-size:1.4rem;margin-bottom:24px;">
        <i class="fas fa-chart-line me-2" style="color:var(--primary);margin-right:8px;"></i>System Analytics
      </h2>
      <div class="row">
        <div class="col-lg-8 mb-4">
          <div class="chart-card">
            <div class="chart-title">System-Wide Monthly Enrollments</div>
            <div class="chart-subtitle">All providers combined</div>
            <div style="height:280px;"><canvas id="officerMonthlyChart"></canvas></div>
          </div>
        </div>
        <div class="col-lg-4 mb-4">
          <div class="chart-card">
            <div class="chart-title">Provider Status</div>
            <div class="chart-subtitle">Registration breakdown</div>
            <div style="height:280px;"><canvas id="providerStatusChart"></canvas></div>
          </div>
        </div>
      </div>
    </section>

    <!-- REPORTS -->
    <section id="section-reports" style="display:none;">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h2 style="font-size:1.4rem;margin-bottom:4px;">
            <i class="fas fa-file-alt me-2" style="color:var(--primary);margin-right:8px;"></i>System Reports
          </h2>
          <p style="color:var(--text-muted);margin:0;font-size:.9rem;">Comprehensive analytics for all providers &amp; enrollments.</p>
        </div>
        <button onclick="window.print()" class="btn-primary-ems">
          <i class="fas fa-print"></i> Print Report
        </button>
      </div>

      <!-- Summary Banner -->
      <div style="background:linear-gradient(135deg,var(--primary),var(--accent));border-radius:var(--radius);padding:24px;margin-bottom:24px;" data-aos="fade-up">
        <h4 style="color:white;font-size:.95rem;margin-bottom:16px;opacity:.85;">
          <i class="fas fa-chart-pie me-2" style="margin-right:8px;"></i>Platform Summary
        </h4>
        <div class="row">
          <?php
          $summaryCards = [
            ['icon'=>'fa-building','label'=>'Active Providers','value'=>number_format($report_summary['approved_providers'])],
            ['icon'=>'fa-book-open','label'=>'Total Courses','value'=>number_format($report_summary['total_courses'])],
            ['icon'=>'fa-user-graduate','label'=>'Learners','value'=>number_format($report_summary['total_learners'])],
            ['icon'=>'fa-certificate','label'=>'Paid Enrollments','value'=>number_format($report_summary['total_enrollments'])],
            ['icon'=>'fa-wallet','label'=>'Total Revenue','value'=>'RM '.number_format($report_summary['total_revenue'],2)],
            ['icon'=>'fa-clock','label'=>'Pending','value'=>number_format($stats_row['pending_count'])],
          ];
          foreach ($summaryCards as $i => $sc): ?>
          <div class="col-6 col-md-4 col-lg-2 mb-2">
            <div style="background:rgba(255,255,255,0.15);border-radius:10px;padding:14px;text-align:center;">
              <i class="fas <?= $sc['icon'] ?>" style="color:white;font-size:1.2rem;margin-bottom:6px;display:block;opacity:.85;"></i>
              <div style="font-size:1.2rem;font-weight:800;color:white;"><?= $sc['value'] ?></div>
              <div style="font-size:.7rem;color:rgba(255,255,255,.7);margin-top:2px;"><?= $sc['label'] ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Provider Performance Table -->
      <div class="card-ems p-4 mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h4 style="font-size:1rem;font-weight:700;margin:0;">
            <i class="fas fa-building me-2" style="color:var(--primary);margin-right:8px;"></i>Provider Performance
          </h4>
          <a href="<?= APP_URL ?>/pages/analytics.php" class="btn-outline-ems" style="padding:7px 14px;font-size:.82rem;" target="_blank">
            <i class="fas fa-chart-bar me-1" style="margin-right:4px;"></i>Full Analytics
          </a>
        </div>
        <div class="table-responsive-ems">
          <table class="table-ems">
            <thead>
              <tr><th>#</th><th>Provider</th><th>Contact</th><th>Courses</th><th>Enrollments</th><th>Avg Rating</th><th>Status</th></tr>
            </thead>
            <tbody>
              <?php foreach ($report_providers as $i => $prov): ?>
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
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Recent Enrollments -->
      <div class="card-ems p-4">
        <h4 style="font-size:1rem;font-weight:700;margin-bottom:16px;">
          <i class="fas fa-list me-2" style="color:var(--accent);margin-right:8px;"></i>Recent Enrollments (Last 15)
        </h4>
        <div class="table-responsive-ems">
          <table class="table-ems">
            <thead>
              <tr><th>Date</th><th>Learner</th><th>Course</th><th>Provider</th><th>Fee</th><th>Status</th></tr>
            </thead>
            <tbody>
              <?php foreach ($recent_enrollments as $en): ?>
              <tr>
                <td style="font-size:.82rem;color:var(--text-muted);white-space:nowrap;">
                  <?= date('d M Y', strtotime($en['enrollDate'])) ?>
                </td>
                <td style="font-weight:600;font-size:.88rem;"><?= htmlspecialchars($en['learner']) ?></td>
                <td style="font-size:.85rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
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
    </section>

  </div><!-- end dashboard-main -->
</div><!-- end dashboard-wrapper -->
</main>

<script>
function showSection(name, link) {
  document.querySelectorAll('[id^="section-"]').forEach(function(s){ s.style.display='none'; });
  var el = document.getElementById('section-' + name);
  if (el) el.style.display = 'block';
  document.querySelectorAll('.sidebar-nav .nav-link').forEach(function(l){ l.classList.remove('active'); });
  if (link) link.classList.add('active');
  if (name === 'analytics') initOfficerCharts();
}

var officerChartsInit = false;
function initOfficerCharts() {
  if (officerChartsInit) return;
  officerChartsInit = true;
  var labels = <?= json_encode($system_monthly_labels) ?>;
  var counts = <?= json_encode(array_map('intval', $system_monthly_counts)) ?>;
  initMonthlyEnrollmentChart('officerMonthlyChart', labels, counts);

  var approved = <?= count(array_filter($all_providers, fn($p)=>$p['status']==='approved')) ?>;
  var pending  = <?= count(array_filter($all_providers, fn($p)=>$p['status']==='pending')) ?>;
  var rejected = <?= count(array_filter($all_providers, fn($p)=>$p['status']==='rejected')) ?>;
  new Chart(document.getElementById('providerStatusChart'), {
    type: 'doughnut',
    data: {
      labels: ['Approved', 'Pending', 'Rejected'],
      datasets: [{
        data: [approved, pending, rejected],
        backgroundColor: ['#10B981', '#F59E0B', '#EF4444'],
        borderColor: '#fff', borderWidth: 3
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false, cutout: '65%',
      plugins: {
        legend: { position: 'bottom', labels: { color: '#374151', font: { size: 12 }, padding: 16 } }
      }
    }
  });
}

$(document).ready(function(){
  initMonthlyEnrollmentChart('sysMonthlyChart',
    <?= json_encode($system_monthly_labels) ?>,
    <?= json_encode(array_map('intval', $system_monthly_counts)) ?>
  );
});
</script>

<?php
?>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="<?= APP_URL ?>/js/Main.JS"></script>
<script src="<?= APP_URL ?>/js/Charts.JS"></script>
</body>
</html>