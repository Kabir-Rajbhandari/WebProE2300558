<?php
// ---> User dashboard for learners to view courses, receipts, and reviews

require_once '../php/config.php';
requireRole('learner');

$pageTitle = 'My Dashboard';
$activeNav = '';
$userId    = $_SESSION['user_id'];

$db = getDbConnection();

// Get all courses the learner is enrolled in
$enroll_stmt = $db->prepare("
    SELECT e.enrollID, e.paymentStatus, e.enrollDate, e.receiptPath,
           c.courseID, c.title, c.description, c.fee, c.startDate, c.endDate, c.image_path,
           p.org_name,
           COALESCE(AVG(r.rating), 0) AS avg_rating,
           (SELECT COUNT(*) FROM reviews rev WHERE rev.learnerID = e.learnerID AND rev.courseID = c.courseID) AS has_reviewed
    FROM enrollments e
    JOIN courses c ON e.courseID = c.courseID
    JOIN providers p ON c.providerID = p.providerID
    LEFT JOIN reviews r ON c.courseID = r.courseID
    WHERE e.learnerID = ?
    GROUP BY e.enrollID
    ORDER BY e.enrollDate DESC
");
$enroll_stmt->bind_param('i', $userId);
$enroll_stmt->execute();
$enrollments = $enroll_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate learner statistics
$total_enrolled = count($enrollments);
$total_paid = count(array_filter($enrollments, fn($e) => $e['paymentStatus'] === 'paid'));
$total_reviewed = count(array_filter($enrollments, fn($e) => $e['has_reviewed'] > 0));
$total_spent = array_sum(array_column(array_filter($enrollments, fn($e) => $e['paymentStatus'] === 'paid'), 'fee'));

// Get reviews written by learner
$review_stmt = $db->prepare("
    SELECT r.reviewID, r.rating, r.feedback, r.created_at, c.title
    FROM reviews r
    JOIN courses c ON r.courseID = c.courseID
    WHERE r.learnerID = ?
    ORDER BY r.created_at DESC
    LIMIT 5
");
$review_stmt->bind_param('i', $userId);
$review_stmt->execute();
$my_reviews = $review_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$db->close();

include '../includes/header.php';
?>

<main>
<div class="dashboard-wrapper">

  <!-- SIDEBAR -->
  <aside class="dashboard-sidebar" role="navigation" aria-label="Learner Dashboard Navigation">
    <div class="sidebar-user">
      <div class="sidebar-avatar" style="font-size: 1.5rem;">
        <i class="fas fa-user" style="color: white;"></i>
      </div>
      <div class="sidebar-user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
      <div class="sidebar-user-role">Learner</div>
    </div>

    <nav class="sidebar-nav">
      <div class="sidebar-section-title">Main</div>
      <div class="nav-item">
        <a href="#overview" class="nav-link active" onclick="showSection('overview',this)">
          Overview
        </a>
      </div>
      <div class="nav-item">
        <a href="#courses" class="nav-link" onclick="showSection('courses',this)">
          My Courses
          <?php if ($total_enrolled): ?><span style="background:var(--primary);color:white;border-radius:20px;padding:1px 8px;font-size:.72rem;margin-left:auto;"><?= $total_enrolled ?></span><?php endif; ?>
        </a>
      </div>
      <div class="nav-item">
        <a href="#reviews" class="nav-link" onclick="showSection('reviews',this)">
          My Reviews
        </a>
      </div>

      <div class="sidebar-section-title">Actions</div>
      <div class="nav-item">
        <a href="<?= APP_URL ?>/pages/courses.php" class="nav-link">
          Browse Courses
        </a>
      </div>
      <div class="nav-item">
        <a href="<?= APP_URL ?>/php/logout.php" class="nav-link" style="color:rgba(239,68,68,.8) !important;">
          Logout
        </a>
      </div>
    </nav>
  </aside>

  <!-- main content section -->
  <div class="dashboard-main">
    <?php renderFlash(); ?>

    <!-- ---- OVERVIEW SECTION ---- -->
    <section id="section-overview">
      <div class="d-flex align-items-center justify-content-between mb-4" data-aos="fade-down">
        <div>
          <h1 style="font-size:1.5rem;margin-bottom:4px;">
            Welcome back, <?= htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]) ?>
          </h1>
          <p style="color:var(--text-muted);margin:0;font-size:.9rem;">
            <?= date('l, d F Y') ?> &mdash; Track your learning progress here.
          </p>
        </div>
        <a href="<?= APP_URL ?>/pages/courses.php" class="btn-primary-ems d-none d-md-flex">
          Enrol in a Course
        </a>
      </div>

      <!-- Stat Cards -->
      <div class="row mb-4">
        <?php
        $stat_cards = [
          ['color'=>'blue','value'=>$total_enrolled,'label'=>'Courses Enrolled'],
          ['color'=>'green','value'=>$total_paid,'label'=>'Payments Completed'],
          ['color'=>'orange','value'=>$total_reviewed,'label'=>'Reviews Submitted'],
          ['color'=>'purple','value'=>'RM '.number_format($total_spent,2),'label'=>'Total Spent'],
        ];
        foreach ($stat_cards as $i => $sc):
        ?>
        <div class="col-6 col-xl-3 mb-3" data-aos="fade-up" data-aos-delay="<?= $i * 80 ?>">
          <div class="dash-stat-card">
            <div class="dash-stat-icon <?= $sc['color'] ?>">
              <?php
              $icon_map = [
                'Courses Enrolled' => 'fa-book',
                'Payments Completed' => 'fa-check-circle',
                'Reviews Submitted' => 'fa-star',
                'Total Spent' => 'fa-wallet'
              ];
              $icon = $icon_map[$sc['label']] ?? 'fa-circle';
              ?>
              <i class="fas <?= $icon ?>" style="font-size:1.6rem;"></i>
            </div>
            <div>
              <div class="dash-stat-value"><?= $sc['value'] ?></div>
              <div class="dash-stat-label"><?= $sc['label'] ?></div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Recent Enrollments (Overview) -->
      <div class="card-ems p-4" data-aos="fade-up" data-aos-delay="200">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 style="margin:0;font-size:1rem;font-weight:700;">
            Recent Enrollments
          </h5>
          <button class="btn-outline-ems" style="padding:6px 14px;font-size:.82rem;"
                  onclick="showSection('courses',null)">View All</button>
        </div>
        <?php if (empty($enrollments)): ?>
        <div class="text-center py-4">
          <p style="color:var(--text-muted);margin-bottom:16px;">No enrollments yet. Start exploring courses!</p>
          <a href="<?= APP_URL ?>/pages/courses.php" class="btn-primary-ems">Browse Courses</a>
        </div>
        <?php else: ?>
        <div class="table-responsive-ems">
          <table class="table-ems">
            <thead>
              <tr>
                <th>Course</th>
                <th>Provider</th>
                <th>Date</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (array_slice($enrollments, 0, 5) as $enroll): ?>
              <tr>
                <td>
                  <a href="<?= APP_URL ?>/pages/course-detail.php?id=<?= $enroll['courseID'] ?>" style="font-weight:600;color:var(--text);">
                    <?= htmlspecialchars(substr($enroll['title'], 0, 40)) ?><?= strlen($enroll['title']) > 40 ? '...' : '' ?>
                  </a>
                </td>
                <td style="color:var(--text-muted);font-size:.85rem;"><?= htmlspecialchars($enroll['org_name']) ?></td>
                <td style="color:var(--text-muted);font-size:.85rem;"><?= date('d M Y', strtotime($enroll['enrollDate'])) ?></td>
                <td>
                  <span class="badge-status <?= $enroll['paymentStatus'] === 'paid' ? 'badge-approved' : 'badge-pending' ?>">
                    <?= ucfirst($enroll['paymentStatus']) ?>
                  </span>
                </td>
                <td>
                  <div style="display:flex;gap:6px;flex-wrap:wrap;">
                    <?php if ($enroll['paymentStatus'] === 'paid'): ?>
                    <a href="<?= APP_URL ?>/pages/receipt.php?enroll=<?= $enroll['enrollID'] ?>" class="btn-outline-ems" style="padding:4px 10px;font-size:.78rem;">
                      Receipt
                    </a>
                    <?php if (!$enroll['has_reviewed']): ?>
                    <a href="<?= APP_URL ?>/pages/review.php?course=<?= $enroll['courseID'] ?>" class="btn-accent-ems" style="padding:4px 10px;font-size:.78rem;">
                      Review
                    </a>
                    <?php else: ?>
                    <span class="badge-status badge-approved" style="padding:4px 10px;">
                      Reviewed
                    </span>
                    <?php endif; ?>
                    <?php endif; ?>
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

    <!-- MY COURSES SECTION -->
    <section id="section-courses" style="display:none;">
      <div class="d-flex align-items-center justify-content-between mb-4" data-aos="fade-down">
        <h2 style="font-size:1.4rem;margin:0;">My Enrolled Courses</h2>
        <a href="<?= APP_URL ?>/pages/courses.php" class="btn-primary-ems">Browse More</a>
      </div>

      <?php if (empty($enrollments)): ?>
      <div class="text-center py-5" style="background:white;border-radius:var(--radius);border:1px solid var(--border);">
        <h4 style="margin-bottom:8px;">No enrollments yet</h4>
        <p style="color:var(--text-muted);margin-bottom:20px;">Start your learning journey by enrolling in a course.</p>
        <a href="<?= APP_URL ?>/pages/courses.php" class="btn-primary-ems">Browse Courses</a>
      </div>
      <?php else: ?>
      <div class="row">
        <?php foreach ($enrollments as $i => $enroll): ?>
        <div class="col-md-6 col-xl-4 mb-4" data-aos="fade-up" data-aos-delay="<?= $i * 60 ?>">
          <div class="card-ems" style="height:100%;display:flex;flex-direction:column;">
            <div style="<?php if (!empty($enroll['image_path'])): ?>
                        background:linear-gradient(135deg,rgba(99,102,241,0.4),rgba(168,85,247,0.4)),url('<?= APP_URL . '/' . htmlspecialchars($enroll['image_path']) ?>');
                        background-size:cover;background-position:center;
                        <?php else: ?>
                        background:linear-gradient(135deg,var(--primary-light),#ede9fe);
                        <?php endif; ?>
                        padding:24px;display:flex;align-items:center;gap:14px;min-height:100px;">
              <?php if (empty($enroll['image_path'])): ?>
              <div style="width:48px;height:48px;background:white;border-radius:10px;
                          display:flex;align-items:center;justify-content:center;flex-shrink:0;
                          box-shadow:0 3px 10px rgba(0,0,0,.1);">
                <i class="fas fa-book" style="color:var(--primary);"></i>
              </div>
              <?php endif; ?>
              <div>
                <span class="badge-status <?= $enroll['paymentStatus'] === 'paid' ? 'badge-approved' : 'badge-pending' ?>">
                  <?= ucfirst($enroll['paymentStatus']) ?>
                </span>
              </div>
            </div>
            <div style="padding:20px;flex:1;display:flex;flex-direction:column;">
              <h5 style="font-size:.95rem;font-weight:700;margin-bottom:6px;">
                <a href="<?= APP_URL ?>/pages/course-detail.php?id=<?= $enroll['courseID'] ?>" style="color:var(--text);">
                  <?= htmlspecialchars($enroll['title']) ?>
                </a>
              </h5>
              <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:12px;">
                <i class="fas fa-building me-1" style="margin-right:4px;"></i><?= htmlspecialchars($enroll['org_name']) ?>
              </p>
              <div style="font-size:.82rem;color:var(--text-muted);margin-bottom:auto;">
                <i class="fas fa-calendar me-1" style="margin-right:4px;"></i>
                Enrolled: <?= date('d M Y', strtotime($enroll['enrollDate'])) ?>
              </div>
              <div style="display:flex;gap:8px;margin-top:16px;flex-wrap:wrap;">
                <?php if ($enroll['paymentStatus'] === 'paid'): ?>
                <a href="<?= APP_URL ?>/pages/receipt.php?enroll=<?= $enroll['enrollID'] ?>" class="btn-outline-ems" style="padding:6px 12px;font-size:.8rem;">
                  <i class="fas fa-receipt"></i> Receipt
                </a>
                <?php if (!$enroll['has_reviewed']): ?>
                <a href="<?= APP_URL ?>/pages/review.php?course=<?= $enroll['courseID'] ?>" class="btn-accent-ems" style="padding:6px 12px;font-size:.8rem;">
                  <i class="fas fa-star"></i> Review
                </a>
                <?php else: ?>
                <span class="badge-status badge-approved" style="font-size:.78rem;padding:6px 12px;display:flex;align-items:center;gap:4px;">
                  <i class="fas fa-check-circle"></i> Reviewed
                </span>
                <?php endif; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </section>

    <!-- ---- MY REVIEWS SECTION ---- -->
    <section id="section-reviews" style="display:none;">
      <div class="mb-4" data-aos="fade-down">
        <h2 style="font-size:1.4rem;margin:0;">
          <i class="fas fa-star me-2" style="color:var(--warning);margin-right:8px;"></i>My Reviews
        </h2>
        <p style="color:var(--text-muted);margin-top:4px;margin-bottom:0;font-size:.9rem;">
          Reviews you've submitted for completed courses.
        </p>
      </div>

      <?php if (empty($my_reviews)): ?>
      <div class="text-center py-5" style="background:white;border-radius:var(--radius);border:1px solid var(--border);">
        <i class="fas fa-star" style="font-size:2.5rem;color:var(--border);display:block;margin-bottom:14px;"></i>
        <p style="color:var(--text-muted);">You haven't submitted any reviews yet.</p>
      </div>
      <?php else: ?>
      <div class="row">
        <?php foreach ($my_reviews as $i => $rev): ?>
        <div class="col-md-6 mb-4" data-aos="fade-up" data-aos-delay="<?= $i * 80 ?>">
          <div class="card-ems p-4">
            <div class="d-flex align-items-start justify-content-between mb-3">
              <h5 style="font-size:.95rem;font-weight:700;margin:0;">
                <?= htmlspecialchars($rev['title']) ?>
              </h5>
              <div class="star-rating" style="flex-shrink:0;margin-left:12px;">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                  <i class="<?= $s <= $rev['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                <?php endfor; ?>
              </div>
            </div>
            <p style="font-size:.88rem;color:var(--text-muted);margin-bottom:10px;font-style:italic;">
              "<?= htmlspecialchars($rev['feedback']) ?>"
            </p>
            <div style="font-size:.78rem;color:var(--text-light);">
              <i class="fas fa-clock me-1" style="margin-right:3px;"></i>
              <?= date('d M Y', strtotime($rev['created_at'])) ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </section>
  </div>
</div>
</main>

<script>
/**
 * Shows a dashboard section and hides others.
 * Updates sidebar active state.
 */
function showSection(sectionName, clickedLink) {
  // Hide all sections
  document.querySelectorAll('[id^="section-"]').forEach(function(s) {
    s.style.display = 'none';
  });
  // Show target
  var target = document.getElementById('section-' + sectionName);
  if (target) target.style.display = 'block';
  // Update sidebar active
  document.querySelectorAll('.sidebar-nav .nav-link').forEach(function(l) {
    l.classList.remove('active');
  });
  if (clickedLink) clickedLink.classList.add('active');
  return false;
}
</script>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="<?= APP_URL ?>/js/Main.JS"></script>
<script src="<?= APP_URL ?>/js/Charts.JS"></script>
</body>
</html>
