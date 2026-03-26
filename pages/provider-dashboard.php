<?php
// ---> Provider dashboard for creating and managing courses with analytics

require_once 'php/config.php';
requireRole('provider');

$pageTitle = 'Provider Dashboard';
$userId    = $_SESSION['user_id'];
$db        = getDbConnection();

// Get provider information
$provider_stmt = $db->prepare("SELECT * FROM providers WHERE userID = ? LIMIT 1");
$provider_stmt->bind_param('i', $userId);
$provider_stmt->execute();
$provider = $provider_stmt->get_result()->fetch_assoc();

if (!$provider || $provider['status'] !== 'approved') {
    setFlash('warning', 'Your account is pending Ministry approval.');
    redirect(APP_URL . '/login.php');
}
$provider_id = $provider['providerID'];

// Handle course deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_course') {
    $course_id = (int)($_POST['course_id'] ?? 0);
    $delete_stmt = $db->prepare("DELETE FROM courses WHERE courseID=? AND providerID=?");
    $delete_stmt->bind_param('ii', $course_id, $provider_id);
    $delete_stmt->execute();
    setFlash('success', 'Course deleted successfully.');
    redirect(APP_URL . '/provider-dashboard.php');
}

// Handle course status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_status') {
    $course_id  = (int)($_POST['course_id'] ?? 0);
    $new_status = ($_POST['new_status'] ?? '') === 'active' ? 'active' : 'inactive';
    $tog = $db->prepare("UPDATE courses SET status=? WHERE courseID=? AND providerID=?");
    $tog->bind_param('sii', $new_status, $course_id, $provider_id);
    $tog->execute();
    setFlash('success', 'Course status updated to ' . $new_status . '.');
    redirect(APP_URL . '/provider-dashboard.php');
}

// Get all courses
$courses_stmt = $db->prepare("
    SELECT c.*,
           COUNT(DISTINCT e.enrollID) AS enroll_count,
           COALESCE(AVG(r.rating), 0) AS avg_rating
    FROM courses c
    LEFT JOIN enrollments e ON c.courseID = e.courseID AND e.paymentStatus='paid'
    LEFT JOIN reviews r ON c.courseID = r.courseID
    WHERE c.providerID = ?
    GROUP BY c.courseID
    ORDER BY c.courseID DESC
");
$courses_stmt->bind_param('i', $provider_id);
$courses_stmt->execute();
$courses = $courses_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total_courses      = count($courses);
$total_enrollments  = array_sum(array_column($courses, 'enroll_count'));
$total_revenue      = 0;
foreach ($courses as $c) { $total_revenue += $c['fee'] * $c['enroll_count']; }
$avg_rating = $total_courses > 0 ? array_sum(array_column($courses, 'avg_rating')) / $total_courses : 0;

// Enrollment trends
$monthly_stmt = $db->prepare("
    SELECT DATE_FORMAT(e.enrollDate,'%b %Y') AS month_label,
           DATE_FORMAT(e.enrollDate,'%Y-%m') AS month_key,
           COUNT(*) AS count
    FROM enrollments e
    JOIN courses c ON e.courseID = c.courseID
    WHERE c.providerID = ? AND e.paymentStatus='paid'
      AND e.enrollDate >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month_key, month_label
    ORDER BY month_key
");
$monthly_stmt->bind_param('i', $provider_id);
$monthly_stmt->execute();
$monthly_data = $monthly_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$monthly_labels = array_column($monthly_data, 'month_label');
$monthly_counts = array_column($monthly_data, 'count');

$db->close();
include 'includes/header.php';
?>

<main>
<div class="dashboard-wrapper">

  <!-- SIDEBAR -->
  <aside class="dashboard-sidebar">
    <div class="sidebar-user">
      <div class="sidebar-avatar" style="font-size: 1.5rem;">
        <i class="fas fa-user" style="color: white;"></i>
      </div>
      <div class="sidebar-user-name"><?= htmlspecialchars(substr($provider['org_name'], 0, 22)) ?></div>
      <div class="sidebar-user-role">Training Provider</div>
    </div>

    <nav class="sidebar-nav">
      <div class="sidebar-section-title">Dashboard</div>
      <div class="nav-item">
        <a href="#" class="nav-link active" onclick="showSection('overview',this);return false;">
          <i class="fas fa-tachometer-alt" style="width:18px;margin-right:8px;"></i>Overview
        </a>
      </div>
      <div class="nav-item">
        <a href="#" class="nav-link" onclick="showSection('courses',this);return false;">
          <i class="fas fa-book" style="width:18px;margin-right:8px;"></i>My Courses
          <span style="background:var(--accent);color:white;border-radius:20px;padding:1px 8px;font-size:.72rem;margin-left:auto;"><?= $total_courses ?></span>
        </a>
      </div>
      <div class="nav-item">
        <a href="#" class="nav-link" onclick="showSection('analytics',this);return false;">
          <i class="fas fa-chart-bar" style="width:18px;margin-right:8px;"></i>Analytics
        </a>
      </div>
      <div class="nav-item">
        <a href="#" class="nav-link" onclick="showSection('reports',this);return false;">
          <i class="fas fa-file-alt" style="width:18px;margin-right:8px;"></i>Reports
        </a>
      </div>

      <div class="sidebar-section-title">Actions</div>
      <div class="nav-item">
        <a href="course-form.php" class="nav-link" style="color:rgba(168,85,247,.9) !important;">
          <i class="fas fa-plus-circle" style="width:18px;margin-right:8px;"></i>Add New Course
        </a>
      </div>
      <div class="nav-item">
        <a href="php/logout.php" class="nav-link" style="color:rgba(239,68,68,.8) !important;">
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
          <h1 style="font-size:1.5rem;margin-bottom:4px;">
            <?= htmlspecialchars($provider['org_name']) ?>
          </h1>
          <p style="color:var(--text-muted);margin:0;font-size:.9rem;">
            Provider Dashboard &mdash; <?= date('d F Y') ?>
          </p>
        </div>
        <a href="course-form.php" class="btn-accent-ems d-none d-md-flex">
          <i class="fas fa-plus"></i> Add Course
        </a>
      </div>

      <div class="row mb-4">
        <?php
        $prov_stats = [
          ['color'=>'purple','value'=>$total_courses,'label'=>'Total Courses','icon'=>'fa-book-open'],
          ['color'=>'blue','value'=>$total_enrollments,'label'=>'Total Enrollments','icon'=>'fa-users'],
          ['color'=>'green','value'=>'RM '.number_format($total_revenue,2),'label'=>'Total Revenue','icon'=>'fa-chart-line'],
          ['color'=>'orange','value'=>number_format($avg_rating,1).' / 5','label'=>'Avg Rating','icon'=>'fa-star'],
        ];
        foreach ($prov_stats as $i => $sc):
        ?>
        <div class="col-6 col-xl-3 mb-3" data-aos="fade-up" data-aos-delay="<?= $i*80 ?>">
          <div class="dash-stat-card">
            <div class="dash-stat-icon <?= $sc['color'] ?>">
              <i class="fas <?= $sc['icon'] ?>" style="font-size:1.6rem;"></i>
            </div>
            <div>
              <div class="dash-stat-value"><?= $sc['value'] ?></div>
              <div class="dash-stat-label"><?= $sc['label'] ?></div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="card-ems p-4 mb-4" data-aos="fade-up" data-aos-delay="200">
        <div class="chart-title">Monthly Enrollment Trend</div>
        <div class="chart-subtitle">Last 6 months of paid enrollments</div>
        <div style="height:260px;"><canvas id="monthlyChart"></canvas></div>
      </div>

      <div class="card-ems p-4" data-aos="fade-up" data-aos-delay="300">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 style="margin:0;font-size:1rem;font-weight:700;">Recent Courses</h5>
          <button class="btn-outline-ems" style="padding:6px 14px;font-size:.82rem;"
                  onclick="showSection('courses',document.querySelector('[onclick*=courses]'))">View All</button>
        </div>
        <?php if (empty($courses)): ?>
        <div class="text-center py-4">
          <p style="color:var(--text-muted);margin-bottom:14px;">No courses yet. Add your first course!</p>
          <a href="course-form.php" class="btn-accent-ems"><i class="fas fa-plus"></i> Add Course</a>
        </div>
        <?php else: ?>
        <div class="table-responsive-ems">
          <table class="table-ems">
            <thead><tr><th>Course</th><th>Fee</th><th>Seats Left</th><th>Status</th><th>Enrollments</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach (array_slice($courses, 0, 5) as $c): ?>
              <tr>
                <td style="font-weight:600;"><?= htmlspecialchars(substr($c['title'],0,40)) ?><?= strlen($c['title'])>40?'...':'' ?></td>
                <td><?= $c['fee'] > 0 ? 'RM '.number_format($c['fee'],2) : '<span style="color:var(--success);">Free</span>' ?></td>
                <td style="font-weight:700;color:<?= $c['available_seats'] > 0 ? 'var(--primary)' : 'var(--danger)' ?>;">
                  <?= $c['available_seats'] ?>
                </td>
                <td>
                  <span class="badge-status <?= $c['status']==='active' ? 'badge-approved' : 'badge-pending' ?>">
                    <?= $c['status']==='active' ? 'Active' : 'Inactive' ?>
                  </span>
                </td>
                <td><?= $c['enroll_count'] ?></td>
                <td>
                  <div style="display:flex;gap:6px;">
                    <a href="course-form.php?id=<?= $c['courseID'] ?>" class="btn-outline-ems" style="padding:4px 10px;font-size:.78rem;">Edit</a>
                    <button onclick="confirmDeleteCourse(<?= $c['courseID'] ?>,'<?= htmlspecialchars(addslashes($c['title'])) ?>')"
                            class="btn-primary-ems" style="padding:4px 10px;font-size:.78rem;background:var(--danger);">Delete</button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </section>

    <!-- COURSES SECTION -->
    <section id="section-courses" style="display:none;">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <h2 style="font-size:1.4rem;margin:0;"><i class="fas fa-book me-2" style="color:var(--accent);margin-right:8px;"></i>My Courses</h2>
        <a href="course-form.php" class="btn-accent-ems"><i class="fas fa-plus"></i> Add New Course</a>
      </div>
      <?php if (empty($courses)): ?>
      <div class="text-center py-5" style="background:white;border-radius:var(--radius);border:1px solid var(--border);">
        <i class="fas fa-book" style="font-size:3rem;color:var(--border);display:block;margin-bottom:14px;"></i>
        <p style="color:var(--text-muted);margin-bottom:16px;">No courses added yet.</p>
        <a href="course-form.php" class="btn-accent-ems"><i class="fas fa-plus"></i> Add First Course</a>
      </div>
      <?php else: ?>
      <div class="table-responsive-ems">
        <table class="table-ems">
          <thead>
            <tr>
              <th>#</th><th>Course Title</th><th>Category</th><th>Fee</th>
              <th>Seats Left</th><th>Status</th><th>Enrollments</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($courses as $i => $c): ?>
            <tr>
              <td style="color:var(--text-muted);"><?= $i+1 ?></td>
              <td>
                <div style="font-weight:600;"><?= htmlspecialchars($c['title']) ?></div>
                <div style="font-size:.78rem;color:var(--text-muted);"><?= htmlspecialchars(substr($c['description'],0,60)) ?>...</div>
              </td>
              <td><span class="badge-category"><?= htmlspecialchars($c['category'] ?? 'General') ?></span></td>
              <td><?= $c['fee']>0?'RM '.number_format($c['fee'],2):'<span style="color:var(--success);">Free</span>' ?></td>
              <td>
                <span style="font-weight:700;color:<?= $c['available_seats'] > 0 ? 'var(--primary)' : 'var(--danger)' ?>;">
                  <?= (int)$c['available_seats'] ?>
                  <?php if ($c['available_seats'] <= 0): ?>
                  <span style="font-size:.7rem;font-weight:400;"> (Full)</span>
                  <?php endif; ?>
                </span>
              </td>
              <td>
                <!-- Toggle status button -->
                <form method="POST" action="provider-dashboard.php" style="display:inline;">
                  <input type="hidden" name="action" value="toggle_status" />
                  <input type="hidden" name="course_id" value="<?= $c['courseID'] ?>" />
                  <input type="hidden" name="new_status" value="<?= $c['status']==='active' ? 'inactive' : 'active' ?>" />
                  <button type="submit" class="badge-status <?= $c['status']==='active' ? 'badge-approved' : 'badge-pending' ?>"
                          style="border:none;cursor:pointer;font-size:.75rem;"
                          title="Click to toggle status">
                    <?= $c['status']==='active' ? 'Active' : 'Inactive' ?>
                  </button>
                </form>
              </td>
              <td><span style="font-weight:700;color:var(--primary);"><?= $c['enroll_count'] ?></span></td>
              <td>
                <div style="display:flex;gap:6px;">
                  <a href="course-detail.php?id=<?= $c['courseID'] ?>" class="btn-outline-ems" style="padding:5px 10px;font-size:.78rem;" title="View">
                    <i class="fas fa-eye"></i>
                  </a>
                  <a href="course-form.php?id=<?= $c['courseID'] ?>" class="btn-outline-ems" style="padding:5px 10px;font-size:.78rem;" title="Edit">
                    <i class="fas fa-edit"></i>
                  </a>
                  <button onclick="confirmDeleteCourse(<?= $c['courseID'] ?>,'<?= htmlspecialchars(addslashes($c['title'])) ?>')"
                          class="btn-primary-ems" style="padding:5px 10px;font-size:.78rem;background:var(--danger);" title="Delete">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </section>

    <!-- ANALYTICS SECTION -->
    <section id="section-analytics" style="display:none;">
      <h2 style="font-size:1.4rem;margin-bottom:24px;">
        <i class="fas fa-chart-bar me-2" style="color:var(--primary);margin-right:8px;"></i>Analytics
      </h2>
      <div class="row">
        <div class="col-lg-7 mb-4">
          <div class="chart-card" data-aos="fade-up">
            <div class="chart-title">Monthly Enrollments</div>
            <div class="chart-subtitle">Last 6 months — paid enrollments</div>
            <div style="height:280px;"><canvas id="analyticsMonthly"></canvas></div>
          </div>
        </div>
        <div class="col-lg-5 mb-4">
          <div class="chart-card" data-aos="fade-up" data-aos-delay="100">
            <div class="chart-title">Course Performance</div>
            <div class="chart-subtitle">Enrollments per course</div>
            <div style="height:280px;"><canvas id="analyticsPerCourse"></canvas></div>
          </div>
        </div>
      </div>
    </section>

    <!-- REPORTS SECTION -->
    <section id="section-reports" style="display:none;">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <h2 style="font-size:1.4rem;margin:0;">
          <i class="fas fa-file-alt me-2" style="color:var(--primary);margin-right:8px;"></i>Enrollment Reports
        </h2>
        <a href="analytics.php?provider=<?= $provider_id ?>" class="btn-primary-ems" target="_blank">
          <i class="fas fa-external-link-alt"></i> Full Analytics Page
        </a>
      </div>
      <div class="card-ems p-4">
        <h5 style="margin-bottom:16px;font-size:1rem;">Course Summary Report</h5>
        <div class="table-responsive-ems">
          <table class="table-ems">
            <thead>
              <tr>
                <th>Course</th><th>Fee</th><th>Seats Left</th><th>Status</th>
                <th>Enrollments</th><th>Revenue</th><th>Avg Rating</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($courses as $c): ?>
              <tr>
                <td style="font-weight:600;"><?= htmlspecialchars($c['title']) ?></td>
                <td><?= $c['fee']>0?'RM '.number_format($c['fee'],2):'Free' ?></td>
                <td style="font-weight:700;color:<?= $c['available_seats']>0?'var(--primary)':'var(--danger)' ?>;">
                  <?= (int)$c['available_seats'] ?>
                </td>
                <td>
                  <span class="badge-status <?= $c['status']==='active'?'badge-approved':'badge-pending' ?>">
                    <?= ucfirst($c['status']) ?>
                  </span>
                </td>
                <td><?= $c['enroll_count'] ?></td>
                <td style="font-weight:600;color:var(--success);">RM <?= number_format($c['fee']*$c['enroll_count'],2) ?></td>
                <td><?= number_format($c['avg_rating'],1) ?> / 5.0</td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr style="background:var(--card-bg);font-weight:700;">
                <td>Total</td>
                <td></td>
                <td></td>
                <td></td>
                <td><?= $total_enrollments ?></td>
                <td style="color:var(--success);">RM <?= number_format($total_revenue,2) ?></td>
                <td><?= number_format($avg_rating,1) ?> / 5.0</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </section>

  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade modal-ems" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" style="font-size:1rem;">
          <i class="fas fa-exclamation-triangle" style="color:var(--danger);margin-right:8px;"></i>Delete Course
        </h5>
        <button type="button" class="close" data-dismiss="modal">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="modal-body">
        <p style="font-size:.92rem;margin:0;">
          Are you sure you want to delete <strong id="deleteCourseName"></strong>?
          This action cannot be undone.
        </p>
      </div>
      <div class="modal-footer">
        <button class="btn-outline-ems" style="padding:8px 18px;" data-dismiss="modal">Cancel</button>
        <form method="POST" action="provider-dashboard.php" id="deleteForm" style="display:inline;">
          <input type="hidden" name="action" value="delete_course" />
          <input type="hidden" name="course_id" id="deleteCourseId" />
          <button type="submit" class="btn-primary-ems" style="padding:8px 18px;background:var(--danger);">
            <i class="fas fa-trash"></i> Delete
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
</main>

<script>
function showSection(name, link) {
  document.querySelectorAll('[id^="section-"]').forEach(function(s){ s.style.display='none'; });
  var el = document.getElementById('section-' + name);
  if (el) el.style.display = 'block';
  document.querySelectorAll('.sidebar-nav .nav-link').forEach(function(l){ l.classList.remove('active'); });
  if (link) link.classList.add('active');
  if (name === 'analytics' || name === 'overview') initProviderCharts();
}

function confirmDeleteCourse(id, name) {
  document.getElementById('deleteCourseName').textContent = name;
  document.getElementById('deleteCourseId').value = id;
  $('#deleteModal').modal('show');
}

var chartsInitialized = false;
function initProviderCharts() {
  if (chartsInitialized) return;
  chartsInitialized = true;

  var labels = <?= json_encode($monthly_labels) ?>;
  var counts = <?= json_encode(array_map('intval', $monthly_counts)) ?>;
  var courseLabels = <?= json_encode(array_map(fn($c)=>substr($c['title'],0,20), $courses)) ?>;
  var courseEnrolls = <?= json_encode(array_map(fn($c)=>(int)$c['enroll_count'], $courses)) ?>;

  if (document.getElementById('monthlyChart'))
    initMonthlyEnrollmentChart('monthlyChart', labels, counts);
  if (document.getElementById('analyticsMonthly'))
    initMonthlyEnrollmentChart('analyticsMonthly', labels, counts);
  if (document.getElementById('analyticsPerCourse'))
    initCourseViewsChart('analyticsPerCourse', courseLabels, courseEnrolls, courseEnrolls);
}

$(document).ready(function(){ initProviderCharts(); });
</script>

<?php
// No full footer in dashboard — just scripts and closing tags
?>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="<?= APP_URL ?>/js/Main.JS"></script>
<script src="<?= APP_URL ?>/js/Charts.JS"></script>
</body>
</html>
