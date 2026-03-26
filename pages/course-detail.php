<?php


require_once '../php/config.php';

$courseId = (int)($_GET['id'] ?? 0);
if (!$courseId) redirect(APP_URL . '/pages/courses.php');

$db = getDbConnection();

// Course details + provider
$stmt = $db->prepare("
    SELECT c.*, p.org_name, p.org_profile, p.providerID,
           COALESCE(AVG(r.rating), 0) AS avg_rating,
           COUNT(DISTINCT r.reviewID) AS review_count,
           COUNT(DISTINCT e.enrollID) AS enroll_count
    FROM courses c
    JOIN providers p ON c.providerID = p.providerID
    LEFT JOIN reviews r ON c.courseID = r.courseID
    LEFT JOIN enrollments e ON c.courseID = e.courseID AND e.paymentStatus='paid'
    WHERE c.courseID = ?
    GROUP BY c.courseID
");
$stmt->bind_param('i', $courseId);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
if (!$course) {
    redirect(APP_URL . '/pages/error.php?code=404');
}

// Block access to inactive courses for non-providers/non-officers
$userRole = $_SESSION['user_role'] ?? '';
if ($course['status'] === 'inactive' && !in_array($userRole, ['provider','officer'])) {
    setFlash('warning', 'This course is currently unavailable.');
    redirect(APP_URL . '/pages/courses.php');
}

// Update view count in analytics
$viewChk = $db->prepare("SELECT analyticsID FROM analytics WHERE courseID=?");
$viewChk->bind_param('i', $courseId);
$viewChk->execute();
$analytic = $viewChk->get_result()->fetch_assoc();
if ($analytic) {
    $db->query("UPDATE analytics SET views=views+1 WHERE courseID=$courseId");
} else {
    $db->query("INSERT INTO analytics (courseID, views, enrollCount) VALUES ($courseId, 1, 0)");
}

// Reviews
$revStmt = $db->prepare("
    SELECT r.*, u.name AS reviewer_name
    FROM reviews r JOIN users u ON r.learnerID = u.id
    WHERE r.courseID = ?
    ORDER BY r.created_at DESC
    LIMIT 10
");
$revStmt->bind_param('i', $courseId);
$revStmt->execute();
$reviews = $revStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Check if learner already enrolled
$isEnrolled = false;
if (!empty($_SESSION['user_id']) && $_SESSION['user_role'] === 'learner') {
    $enrChk = $db->prepare("SELECT enrollID FROM enrollments WHERE learnerID=? AND courseID=? LIMIT 1");
    $enrChk->bind_param('ii', $_SESSION['user_id'], $courseId);
    $enrChk->execute();
    $isEnrolled = $enrChk->get_result()->num_rows > 0;
}

$db->close();

$pageTitle = htmlspecialchars($course['title']);
$activeNav = 'courses';
include '../includes/header.php';
?>

<main>
<!-- Course Image Banner -->
<?php if (!empty($course['image_path'])): ?>
<div style="width:100%;height:300px;background:linear-gradient(135deg,rgba(99,102,241,0.5),rgba(168,85,247,0.5)),
            url('<?= APP_URL . '/' . htmlspecialchars($course['image_path']) ?>');
            background-size:cover;background-position:center;position:relative;">
</div>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header">
  <div class="container">
    <div class="breadcrumb-ems">
      <a href="<?= APP_URL ?>/pages/index.php">Home</a><span>/</span>
      <a href="<?= APP_URL ?>/pages/courses.php">Courses</a><span>/</span>
      <span style="color:var(--text);"><?= htmlspecialchars(substr($course['title'],0,40)) ?>...</span>
    </div>
    <span class="badge-category mb-2 d-inline-block"><?= htmlspecialchars($course['category'] ?? 'General') ?></span>
    <h1><?= htmlspecialchars($course['title']) ?></h1>
    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin-top:10px;font-size:.88rem;color:var(--text-muted);">
      <span><i class="fas fa-building me-1" style="margin-right:4px;"></i><?= htmlspecialchars($course['org_name']) ?></span>
      <span>
        <span class="star-rating" style="font-size:.85rem;">
          <?php for($s=1;$s<=5;$s++): ?>
          <i class="<?= $s<=round($course['avg_rating'])?'fas':'far' ?> fa-star"></i>
          <?php endfor; ?>
        </span>
        <?= number_format($course['avg_rating'],1) ?> (<?= $course['review_count'] ?> reviews)
      </span>
      <span><i class="fas fa-users me-1" style="margin-right:4px;"></i><?= $course['enroll_count'] ?> enrolled</span>
    </div>
  </div>
</div>

<section class="section-padding-sm">
  <div class="container">
    <div class="row">
      <!-- Left: Details -->
      <div class="col-lg-8">
        <!-- Description -->
        <div class="card-ems p-4 mb-4" data-aos="fade-up">
          <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:14px;">
            <i class="fas fa-info-circle me-2" style="color:var(--primary);margin-right:8px;"></i>About This Course
          </h3>
          <p style="color:var(--text-muted);line-height:1.8;white-space:pre-line;">
            <?= nl2br(htmlspecialchars($course['description'])) ?>
          </p>
        </div>

        <!-- Course Info Grid -->
        <div class="card-ems p-4 mb-4" data-aos="fade-up" data-aos-delay="100">
          <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:16px;">
            <i class="fas fa-list-ul me-2" style="color:var(--primary);margin-right:8px;"></i>Course Details
          </h3>
          <div class="row">
            <?php
            $details = [
              ['icon'=>'fa-calendar','label'=>'Start Date','value'=>$course['startDate']?date('d M Y',strtotime($course['startDate'])):'Not specified'],
              ['icon'=>'fa-calendar-check','label'=>'End Date','value'=>$course['endDate']?date('d M Y',strtotime($course['endDate'])):'Not specified'],
              ['icon'=>'fa-wallet','label'=>'Course Fee','value'=>$course['fee']>0?'RM '.number_format($course['fee'],2):'Free'],
              ['icon'=>'fa-building','label'=>'Provider','value'=>$course['org_name']],
            ];
            foreach ($details as $d): ?>
            <div class="col-md-6 mb-3">
              <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:36px;height:36px;background:var(--primary-light);border-radius:8px;
                            display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                  <i class="fas <?= $d['icon'] ?>" style="color:var(--primary);font-size:.85rem;"></i>
                </div>
                <div>
                  <div style="font-size:.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;"><?= $d['label'] ?></div>
                  <div style="font-weight:600;font-size:.9rem;"><?= htmlspecialchars($d['value']) ?></div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Reviews -->
        <div data-aos="fade-up" data-aos-delay="200">
          <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:16px;">
            <i class="fas fa-star me-2" style="color:var(--warning);margin-right:8px;"></i>
            Learner Reviews (<?= $course['review_count'] ?>)
          </h3>
          <?php if (empty($reviews)): ?>
          <div style="background:white;border:1px solid var(--border);border-radius:var(--radius);padding:32px;text-align:center;">
            <i class="fas fa-comment-slash" style="font-size:2rem;color:var(--border);display:block;margin-bottom:12px;"></i>
            <p style="color:var(--text-muted);margin:0;">No reviews yet. Be the first to enrol and review!</p>
          </div>
          <?php else: ?>
          <?php foreach ($reviews as $rev): ?>
          <div class="card-ems p-4 mb-3">
            <div class="d-flex align-items-start justify-content-between mb-2">
              <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,var(--primary-light),#ede9fe);
                            display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--primary);font-size:.9rem;">
                  <?= strtoupper(substr($rev['reviewer_name'],0,1)) ?>
                </div>
                <div>
                  <div style="font-weight:600;font-size:.9rem;"><?= htmlspecialchars($rev['reviewer_name']) ?></div>
                  <div style="font-size:.75rem;color:var(--text-muted);"><?= date('d M Y',strtotime($rev['created_at'])) ?></div>
                </div>
              </div>
              <div class="star-rating" style="font-size:.85rem;">
                <?php for($s=1;$s<=5;$s++): ?>
                <i class="<?= $s<=$rev['rating']?'fas':'far' ?> fa-star"></i>
                <?php endfor; ?>
              </div>
            </div>
            <p style="font-size:.88rem;color:var(--text-muted);margin:0;font-style:italic;">
              "<?= htmlspecialchars($rev['feedback']) ?>"
            </p>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Right: Enrol Card -->
      <div class="col-lg-4 mt-4 mt-lg-0">
        <div style="position:sticky;top:90px;">
          <div class="card-ems p-0 overflow-hidden" data-aos="fade-left">
            <div style="background:linear-gradient(135deg,var(--primary-light),#ede9fe);padding:28px 24px;text-align:center;">
              <div style="font-size:2.2rem;font-weight:900;color:var(--primary);">
                <?= $course['fee']>0?'RM '.number_format($course['fee'],2):'<span style="color:var(--success);">FREE</span>' ?>
              </div>
              <?php if ($course['fee']>0): ?>
              <div style="font-size:.82rem;color:var(--text-muted);">One-time payment</div>
              <?php endif; ?>
            </div>
            <div style="padding:24px;">

              <!-- Seats availability indicator -->
              <?php if ((int)$course['available_seats'] <= 0): ?>
              <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:10px 14px;
                          text-align:center;margin-bottom:14px;font-size:.85rem;color:#dc2626;font-weight:600;">
                <i class="fas fa-ban" style="margin-right:6px;"></i>No seats available — Course is fully booked.
              </div>
              <?php elseif ((int)$course['available_seats'] <= 5): ?>
              <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:10px 14px;
                          text-align:center;margin-bottom:14px;font-size:.85rem;color:#d97706;font-weight:600;">
                <i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i>Only <strong><?= (int)$course['available_seats'] ?></strong> seat(s) left!
              </div>
              <?php else: ?>
              <div style="text-align:center;margin-bottom:14px;font-size:.84rem;color:var(--text-muted);">
                <i class="fas fa-chair" style="color:var(--success);margin-right:5px;"></i>
                <strong style="color:var(--success);"><?= (int)$course['available_seats'] ?></strong> seats available
              </div>
              <?php endif; ?>

              <?php if (empty($_SESSION['user_id'])): ?>
              <a href="<?= APP_URL ?>/pages/login.php?redirect=<?= urlencode(APP_URL . '/pages/course-detail.php?id=' . $courseId) ?>"
                 class="btn-primary-ems w-100" style="padding:13px;justify-content:center;font-size:1rem;">
                <i class="fas fa-sign-in-alt"></i> Login to Enrol
              </a>
              <?php elseif ($_SESSION['user_role'] === 'learner' && !$isEnrolled): ?>
                <?php if ((int)$course['available_seats'] > 0): ?>
                <a href="<?= APP_URL ?>/pages/enrollment.php?course=<?= $courseId ?>"
                   class="btn-primary-ems w-100" style="padding:13px;justify-content:center;font-size:1rem;">
                  <i class="fas fa-graduation-cap"></i> Enrol Now
                </a>
                <?php else: ?>
                <button class="btn-primary-ems w-100"
                        style="padding:13px;justify-content:center;font-size:1rem;opacity:.55;cursor:not-allowed;"
                        disabled>
                  <i class="fas fa-ban"></i> Course Full
                </button>
                <?php endif; ?>
              <?php elseif ($isEnrolled): ?>
              <div class="alert-ems alert-success-ems" style="justify-content:center;margin:0;">
                <i class="fas fa-check-circle"></i> Already Enrolled
              </div>
              <?php else: ?>
              <div class="alert-ems alert-info-ems" style="margin:0;">
                <i class="fas fa-info-circle"></i> Login as a Learner to enrol
              </div>
              <?php endif; ?>

              <!-- Course highlights -->
              <ul style="list-style:none;padding:0;margin:20px 0 0;">
                <?php
                $highlights = [
                  'fa-infinity'=>'Full course access',
                  'fa-certificate'=>'Certificate on completion',
                  'fa-receipt'=>'Official receipt provided',
                  'fa-star'=>'Rate & review after completion',
                ];
                foreach ($highlights as $icon=>$text): ?>
                <li style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border);font-size:.85rem;">
                  <i class="fas <?= $icon ?>" style="color:var(--success);flex-shrink:0;width:16px;text-align:center;"></i>
                  <?= $text ?>
                </li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>

          <!-- Provider Info -->
          <div class="card-ems p-4 mt-3" data-aos="fade-left" data-aos-delay="100">
            <h5 style="font-size:.9rem;font-weight:700;margin-bottom:12px;">
              <i class="fas fa-building me-2" style="color:var(--primary);margin-right:6px;"></i>About the Provider
            </h5>
            <div style="font-weight:700;margin-bottom:4px;"><?= htmlspecialchars($course['org_name']) ?></div>
            <p style="font-size:.82rem;color:var(--text-muted);margin:0;
                      display:-webkit-box;-webkit-line-clamp:4;-webkit-box-orient:vertical;overflow:hidden;">
              <?= htmlspecialchars($course['org_profile'] ?? 'Ministry-approved training provider.') ?>
            </p>
            <span class="badge-status badge-approved mt-2 d-inline-block">
              <i class="fas fa-shield-alt me-1" style="margin-right:3px;"></i>Ministry Verified
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
</main>

<?php include '../includes/footer.php'; ?>
