<?php

require_once '../php/config.php';
requireRole('learner');

$courseId = (int)($_GET['course'] ?? 0);
if (!$courseId) redirect(APP_URL . '/pages/courses.php');

$db = getDbConnection();

// Get course (only active courses can be enrolled)
$stmt = $db->prepare("
    SELECT c.*, p.org_name FROM courses c
    JOIN providers p ON c.providerID = p.providerID AND p.status='approved'
    WHERE c.courseID = ? AND c.status = 'active'
");
$stmt->bind_param('i', $courseId);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
if (!$course) redirect(APP_URL . '/pages/courses.php');

// Check course is not full
if ($course['available_seats'] <= 0) {
    setFlash('warning', 'Sorry, this course is fully booked. No seats are available.');
    redirect(APP_URL . '/pages/course-detail.php?id=' . $courseId);
}

// Check already enrolled
$chk = $db->prepare("SELECT enrollID FROM enrollments WHERE learnerID=? AND courseID=? LIMIT 1");
$chk->bind_param('ii', $_SESSION['user_id'], $courseId);
$chk->execute();
if ($chk->get_result()->num_rows > 0) {
    setFlash('info', 'You are already enrolled in this course.');
    redirect(APP_URL . '/pages/learner-dashboard.php');
}

// Get learner info prefill
$userStmt = $db->prepare("SELECT * FROM users WHERE id=? LIMIT 1");
$userStmt->bind_param('i', $_SESSION['user_id']);
$userStmt->execute();
$learner = $userStmt->get_result()->fetch_assoc();

$db->close();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $icNumber = sanitize($_POST['ic_number'] ?? '');
    $address  = sanitize($_POST['address'] ?? '');
    $phone    = sanitize($_POST['phone'] ?? '');
    $employer = sanitize($_POST['employer'] ?? '');

    if (!preg_match('/^\d{12}$/', $icNumber)) $errors[] = 'Please enter a valid 12-digit IC number.';
    if (strlen($address) < 10) $errors[] = 'Please enter your full address.';
    if (!preg_match('/^[+]?[\d\s\-]{8,15}$/', $phone)) $errors[] = 'Invalid phone number.';

    if (empty($errors)) {
        // Store in session for payment page
        $_SESSION['enrollment_data'] = [
            'course_id'  => $courseId,
            'ic_number'  => $icNumber,
            'address'    => $address,
            'phone'      => $phone,
            'employer'   => $employer,
        ];
        redirect(APP_URL . '/pages/payment.php');
    }
}

$pageTitle = 'Enrol: ' . htmlspecialchars($course['title']);
$activeNav = 'courses';
include '../includes/header.php';
?>

<main>
<div class="page-header">
  <div class="container">
    <div class="breadcrumb-ems">
      <a href="<?= APP_URL ?>/pages/index.php">Home</a><span>/</span>
      <a href="<?= APP_URL ?>/pages/courses.php">Courses</a><span>/</span>
      <a href="<?= APP_URL ?>/pages/course-detail.php?id=<?= $courseId ?>">Course</a><span>/</span>
      <span style="color:var(--text);">Enrol</span>
    </div>
    <h1>Enrolment Form</h1>
    <p style="color:var(--text-muted);margin:0;">
      Complete your details to enrol in <strong><?= htmlspecialchars($course['title']) ?></strong>
    </p>
  </div>
</div>

<section class="section-padding-sm">
  <div class="container">
    <div class="row">
      <!-- Form -->
      <div class="col-lg-8">
        <div class="card-ems p-4" data-aos="fade-up">
          <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:20px;">
            <i class="fas fa-user-edit me-2" style="color:var(--primary);margin-right:8px;"></i>Your Details
          </h3>

          <?php if (!empty($errors)): ?>
          <div class="alert-ems alert-danger-ems">
            <i class="fas fa-exclamation-circle"></i>
            <ul style="margin:0;padding-left:16px;">
              <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
            </ul>
          </div>
          <?php endif; ?>

          <form id="enrollmentForm" method="POST" action="enrollment.php?course=<?= $courseId ?>" class="form-ems" novalidate>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Full Name</label>
                  <input type="text" class="form-control" value="<?= htmlspecialchars($learner['name']) ?>" disabled
                         style="background:var(--card-bg);color:var(--text-muted);" />
                  <small style="color:var(--text-muted);font-size:.78rem;">From your account profile</small>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Email Address</label>
                  <input type="email" class="form-control" value="<?= htmlspecialchars($learner['email']) ?>" disabled
                         style="background:var(--card-bg);color:var(--text-muted);" />
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="icNumber">
                    <i class="fas fa-id-card me-1" style="color:var(--primary);margin-right:4px;"></i>
                    IC / MyKad Number *
                  </label>
                  <input type="text" id="icNumber" name="ic_number" class="form-control"
                         placeholder="e.g. 990101011234 (12 digits, no dashes)"
                         value="<?= htmlspecialchars($_POST['ic_number'] ?? '') ?>"
                         maxlength="12" required />
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="enrollPhone">
                    <i class="fas fa-phone me-1" style="color:var(--primary);margin-right:4px;"></i>
                    Contact Number *
                  </label>
                  <input type="tel" id="enrollPhone" name="phone" class="form-control"
                         placeholder="e.g. 0123456789"
                         value="<?= htmlspecialchars($_POST['phone'] ?? $learner['phone'] ?? '') ?>"
                         required />
                </div>
              </div>
              <div class="col-md-12">
                <div class="form-group">
                  <label for="address">
                    <i class="fas fa-map-marker-alt me-1" style="color:var(--primary);margin-right:4px;"></i>
                    Full Address *
                  </label>
                  <textarea id="address" name="address" class="form-control" rows="3"
                            placeholder="Enter your full residential address" required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                </div>
              </div>
              <div class="col-md-12">
                <div class="form-group">
                  <label for="employer">
                    <i class="fas fa-briefcase me-1" style="color:var(--primary);margin-right:4px;"></i>
                    Current Employer / Organisation (Optional)
                  </label>
                  <input type="text" id="employer" name="employer" class="form-control"
                         placeholder="Company or organisation name"
                         value="<?= htmlspecialchars($_POST['employer'] ?? '') ?>" />
                </div>
              </div>
            </div>

            <div class="alert-ems alert-info-ems">
              <i class="fas fa-info-circle"></i>
              <span>You will be redirected to the payment page after submitting this form.</span>
            </div>

            <div class="d-flex" style="gap:12px;">
              <a href="<?= APP_URL ?>/pages/course-detail.php?id=<?= $courseId ?>" class="btn-outline-ems" style="padding:11px 22px;">
                <i class="fas fa-arrow-left"></i> Back
              </a>
              <button type="submit" class="btn-primary-ems flex-fill" style="justify-content:center;padding:12px;">
                <i class="fas fa-arrow-right"></i> Proceed to Payment
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Course Summary -->
      <div class="col-lg-4 mt-4 mt-lg-0">
        <div class="card-ems p-0 overflow-hidden" data-aos="fade-left">
          <div style="background:linear-gradient(135deg,var(--primary-light),#ede9fe);padding:20px;">
            <h5 style="font-size:.9rem;font-weight:700;margin-bottom:3px;color:var(--primary);">
              <i class="fas fa-graduation-cap me-2" style="margin-right:6px;"></i>Enrolment Summary
            </h5>
          </div>
          <div style="padding:20px;">
            <div style="font-weight:700;margin-bottom:4px;font-size:.95rem;"><?= htmlspecialchars($course['title']) ?></div>
            <div style="font-size:.82rem;color:var(--text-muted);margin-bottom:16px;">
              <i class="fas fa-building me-1" style="margin-right:3px;"></i><?= htmlspecialchars($course['org_name']) ?>
            </div>
            <div class="receipt-row">
              <span class="receipt-label">Course Fee</span>
              <span class="receipt-value">
                <?= $course['fee']>0?'RM '.number_format($course['fee'],2):'Free' ?>
              </span>
            </div>
            <?php if ($course['startDate']): ?>
            <div class="receipt-row">
              <span class="receipt-label">Start Date</span>
              <span class="receipt-value"><?= date('d M Y',strtotime($course['startDate'])) ?></span>
            </div>
            <?php endif; ?>
            <div class="receipt-total" style="margin-top:14px;">
              <span style="font-weight:700;">Total Payable</span>
              <span style="font-size:1.3rem;font-weight:900;color:var(--primary);">
                <?= $course['fee']>0?'RM '.number_format($course['fee'],2):'FREE' ?>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
</main>

<?php include '../includes/footer.php'; ?>
