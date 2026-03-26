<?php

require_once '../php/config.php';
requireRole('learner');

// Ensure enrollment session data exists
if (empty($_SESSION['enrollment_data'])) {
    redirect(APP_URL . '/courses.php');
}

$enrData  = $_SESSION['enrollment_data'];
$courseId = (int)$enrData['course_id'];
$userId   = $_SESSION['user_id'];

$db = getDbConnection();

// Get course
$stmt = $db->prepare("SELECT c.*, p.org_name FROM courses c JOIN providers p ON c.providerID=p.providerID WHERE c.courseID=?");
$stmt->bind_param('i', $courseId);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
if (!$course) redirect(APP_URL . '/courses.php');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cardName   = sanitize($_POST['card_name'] ?? '');
    $cardNumber = preg_replace('/\s/', '', $_POST['card_number'] ?? '');
    $expiry     = sanitize($_POST['card_expiry'] ?? '');
    $cvv        = sanitize($_POST['card_cvv'] ?? '');

    if (!$cardName) $errors[] = 'Cardholder name is required.';
    if (!preg_match('/^\d{16}$/', $cardNumber)) $errors[] = 'Invalid card number.';
    if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)) $errors[] = 'Invalid expiry date (MM/YY).';
    if (!preg_match('/^\d{3,4}$/', $cvv)) $errors[] = 'Invalid CVV.';

    if (empty($errors)) {
        // Insert enrollment
        $payStatus = 'paid';
        $ins = $db->prepare("
            INSERT INTO enrollments (learnerID, courseID, paymentStatus, enrollDate, ic_number, address, phone, employer)
            VALUES (?,?,?,NOW(),?,?,?,?)
        ");
        $ins->bind_param(
            'iisssss',
            $userId, $courseId, $payStatus,
            $enrData['ic_number'], $enrData['address'],
            $enrData['phone'], $enrData['employer']
        );
        $ins->execute();
        $enrollId = $db->insert_id;

        // Update analytics
        $db->query("UPDATE analytics SET enrollCount=enrollCount+1 WHERE courseID=$courseId");

        // Decrement available seats (never below 0)
        $db->query("UPDATE courses SET available_seats = GREATEST(0, available_seats - 1) WHERE courseID = $courseId");

        // Clear session
        unset($_SESSION['enrollment_data']);
        $db->close();

        setFlash('success', 'Payment successful! Your enrolment receipt is ready.');
        redirect(APP_URL . '/pages/receipt.php?enroll=' . $enrollId);
    }
}

$db->close();

$pageTitle = 'Payment';
$activeNav = '';
include '../includes/header.php';
?>

<main>
<div class="page-header">
  <div class="container">
    <div class="breadcrumb-ems">
      <a href="<?= APP_URL ?>/pages/index.php">Home</a><span>/</span>
      <a href="<?= APP_URL ?>/pages/courses.php">Courses</a><span>/</span>
      <a href="<?= APP_URL ?>/pages/enrollment.php?course=<?= $courseId ?>">Enrolment</a><span>/</span>
      <span style="color:var(--text);">Payment</span>
    </div>
    <h1>Secure Payment</h1>
    <p style="color:var(--text-muted);margin:0;">Complete your enrolment with a secure mock payment.</p>
  </div>
</div>

<section class="section-padding-sm">
  <div class="container">
    <div class="row">
      <!-- Payment Form -->
      <div class="col-lg-7">
        <div class="payment-card" data-aos="fade-up">
          <div class="payment-header">
            <div class="d-flex align-items-center justify-content-between">
              <h4 style="margin:0;font-size:1rem;font-weight:700;color:var(--primary);">
                <i class="fas fa-credit-card me-2" style="margin-right:8px;"></i>Card Payment
              </h4>
              <div style="display:flex;gap:8px;">
                <?php foreach(['fa-cc-visa','fa-cc-mastercard','fa-cc-amex'] as $icon): ?>
                <i class="fab <?= $icon ?>" style="font-size:1.8rem;color:var(--text-muted);"></i>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <div style="padding:28px;">


            <?php if (!empty($errors)): ?>
            <div class="alert-ems alert-danger-ems">
              <i class="fas fa-times-circle"></i>
              <ul style="margin:0;padding-left:16px;">
                <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
              </ul>
            </div>
            <?php endif; ?>

            <form id="paymentForm" method="POST" action="<?= APP_URL ?>/pages/payment.php" class="form-ems" novalidate>
              <div class="form-group">
                <label for="cardName">
                  <i class="fas fa-user me-1" style="color:var(--primary);margin-right:4px;"></i>Cardholder Name *
                </label>
                <input type="text" id="cardName" name="card_name" class="form-control"
                       placeholder="Name as on card"
                       value="<?= htmlspecialchars($_SESSION['user_name']) ?>" required />
              </div>

              <div class="form-group">
                <label for="cardNumber">
                  <i class="fas fa-credit-card me-1" style="color:var(--primary);margin-right:4px;"></i>Card Number *
                </label>
                <input type="text" id="cardNumber" name="card_number" class="form-control"
                       placeholder="1234 5678 9012 3456" maxlength="19" required />
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="cardExpiry">
                      <i class="fas fa-calendar me-1" style="color:var(--primary);margin-right:4px;"></i>Expiry Date *
                    </label>
                    <input type="text" id="cardExpiry" name="card_expiry" class="form-control"
                           placeholder="MM/YY" maxlength="5" required />
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="cardCvv">
                      <i class="fas fa-lock me-1" style="color:var(--primary);margin-right:4px;"></i>CVV *
                    </label>
                    <div style="position:relative;">
                      <input type="password" id="cardCvv" name="card_cvv" class="form-control"
                             placeholder="3 or 4 digits" maxlength="4" required />
                      <i class="fas fa-question-circle" style="position:absolute;right:12px;top:50%;
                         transform:translateY(-50%);color:var(--text-muted);cursor:help;"
                         title="The 3 or 4 digit security code on the back of your card."
                         data-toggle="tooltip"></i>
                    </div>
                  </div>
                </div>
              </div>



              <div style="display:flex;gap:12px;">
                <a href="<?= APP_URL ?>/pages/enrollment.php?course=<?= $courseId ?>" class="btn-outline-ems" style="padding:11px 20px;">
                  <i class="fas fa-arrow-left"></i> Back
                </a>
                <button type="submit" class="btn-primary-ems flex-fill" style="justify-content:center;padding:12px;font-size:1rem;">
                  <i class="fas fa-lock"></i> Pay <?= $course['fee']>0?'RM '.number_format($course['fee'],2):'Free' ?>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Order Summary -->
      <div class="col-lg-5 mt-4 mt-lg-0">
        <div class="card-ems p-0 overflow-hidden" data-aos="fade-left">
          <div style="background:linear-gradient(135deg,var(--primary),var(--accent));padding:24px;color:white;">
            <h5 style="margin:0;font-weight:700;"><i class="fas fa-receipt me-2" style="margin-right:8px;"></i>Order Summary</h5>
          </div>
          <div style="padding:24px;">
            <div style="font-weight:700;font-size:.95rem;margin-bottom:4px;"><?= htmlspecialchars($course['title']) ?></div>
            <div style="font-size:.82rem;color:var(--text-muted);margin-bottom:20px;">
              <i class="fas fa-building me-1" style="margin-right:3px;"></i><?= htmlspecialchars($course['org_name']) ?>
            </div>

            <div style="display:flex;flex-direction:column;gap:12px;">
              <?php
              $summaryRows = [
                ['label'=>'Learner','value'=>htmlspecialchars($_SESSION['user_name'])],
                ['label'=>'IC Number','value'=>'**** '.substr($enrData['ic_number'],-4)],
                ['label'=>'Course Fee','value'=>$course['fee']>0?'RM '.number_format($course['fee'],2):'FREE'],
              ];
              foreach ($summaryRows as $row): ?>
              <div style="display:flex;justify-content:space-between;font-size:.88rem;
                          padding-bottom:10px;border-bottom:1px solid var(--border);">
                <span style="color:var(--text-muted);"><?= $row['label'] ?></span>
                <span style="font-weight:600;"><?= $row['value'] ?></span>
              </div>
              <?php endforeach; ?>
            </div>

            <div class="receipt-total" style="margin-top:16px;">
              <span style="font-weight:700;font-size:.95rem;">Total</span>
              <span style="font-size:1.5rem;font-weight:900;color:var(--primary);">
                <?= $course['fee']>0?'RM '.number_format($course['fee'],2):'FREE' ?>
              </span>
            </div>

            <div style="margin-top:16px;font-size:.8rem;color:var(--text-muted);text-align:center;">
              <i class="fas fa-shield-alt me-1" style="color:var(--success);margin-right:3px;"></i>
              256-bit SSL encrypted &bull; Secure checkout
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
</main>

<?php include '../includes/footer.php'; ?>
