<?php

require_once '../php/config.php';
requireRole('learner');

$enrollId = (int)($_GET['enroll'] ?? 0);
if (!$enrollId) redirect(APP_URL . '/learner-dashboard.php');

$db = getDbConnection();

$stmt = $db->prepare("
    SELECT e.*, u.name AS learner_name, u.email AS learner_email,
           c.title AS course_title, c.fee, c.startDate, c.endDate, c.category,
           p.org_name, p.org_profile
    FROM enrollments e
    JOIN users u ON e.learnerID = u.id
    JOIN courses c ON e.courseID = c.courseID
    JOIN providers p ON c.providerID = p.providerID
    WHERE e.enrollID = ? AND e.learnerID = ?
");
$stmt->bind_param('ii', $enrollId, $_SESSION['user_id']);
$stmt->execute();
$receipt = $stmt->get_result()->fetch_assoc();
$db->close();

if (!$receipt) {
    setFlash('error', 'Receipt not found or access denied.');
    redirect(APP_URL . '/learner-dashboard.php');
}

$receiptNumber = generateReceiptNumber($enrollId);
$pageTitle     = 'Receipt ' . $receiptNumber;
$activeNav     = '';
include '../includes/header.php';
?>

<main>
<section class="section-padding-sm">
  <div class="container">
    <?php renderFlash(); ?>

    <!-- Action Buttons -->
    <div class="d-flex align-items-center justify-content-between mb-4 no-print" data-aos="fade-down">
      <div>
        <a href="<?= APP_URL ?>/pages/learner-dashboard.php" class="btn-outline-ems" style="padding:9px 18px;">
          <i class="fas fa-arrow-left"></i> Dashboard
        </a>
      </div>
      <div style="display:flex;gap:10px;">
        <button onclick="printReceipt()" class="btn-primary-ems">
          <i class="fas fa-print"></i> Print Receipt
        </button>
        <button onclick="window.print()" class="btn-accent-ems">
          <i class="fas fa-download"></i> Save as PDF
        </button>
      </div>
    </div>

    <!-- Receipt Document -->
    <div class="receipt-container" data-aos="fade-up">
      <!-- Receipt Header -->
      <div class="receipt-header">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:16px;">
          <div>
            <div style="font-family:'Sora',sans-serif;font-size:1.3rem;font-weight:800;margin-bottom:4px;">
              <i class="fas fa-graduation-cap me-2" style="margin-right:8px;opacity:.85;"></i>
              EduSkill Marketplace System (EMS)
            </div>
            <div style="opacity:.75;font-size:.85rem;">Ministry of Human Resources, Malaysia</div>
          </div>
          <div style="text-align:right;">
            <div style="font-size:.75rem;opacity:.7;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">
              Official Receipt
            </div>
            <div style="font-size:1.3rem;font-weight:800;font-family:'Sora',sans-serif;">
              <?= $receiptNumber ?>
            </div>
            <div style="font-size:.8rem;opacity:.7;">
              Issued: <?= date('d M Y, h:i A') ?>
            </div>
          </div>
        </div>

        <div style="margin-top:24px;padding-top:20px;border-top:1px solid rgba(255,255,255,0.2);
                    display:flex;gap:24px;flex-wrap:wrap;">
          <div>
            <div style="font-size:.72rem;opacity:.6;text-transform:uppercase;letter-spacing:.8px;">Payment Status</div>
            <div style="font-weight:700;margin-top:3px;display:flex;align-items:center;gap:6px;">
              <i class="fas fa-check-circle" style="color:#6ee7b7;"></i>
              <?= ucfirst($receipt['paymentStatus']) ?>
            </div>
          </div>
          <div>
            <div style="font-size:.72rem;opacity:.6;text-transform:uppercase;letter-spacing:.8px;">Amount Paid</div>
            <div style="font-weight:700;margin-top:3px;font-size:1.1rem;">
              <?= $receipt['fee']>0 ? 'RM '.number_format($receipt['fee'],2) : 'FREE' ?>
            </div>
          </div>
          <div>
            <div style="font-size:.72rem;opacity:.6;text-transform:uppercase;letter-spacing:.8px;">Enrolment Date</div>
            <div style="font-weight:700;margin-top:3px;">
              <?= date('d M Y', strtotime($receipt['enrollDate'])) ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Receipt Body -->
      <div class="receipt-body">
        <div class="row">
          <!-- Learner Info -->
          <div class="col-md-6">
            <h4 style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;
                       color:var(--text-muted);margin-bottom:14px;padding-bottom:8px;border-bottom:1px solid var(--border);">
              <i class="fas fa-user me-2" style="color:var(--primary);margin-right:6px;"></i>Learner Information
            </h4>
            <?php
            $learnerInfo = [
              ['label'=>'Full Name','value'=>$receipt['learner_name']],
              ['label'=>'Email Address','value'=>$receipt['learner_email']],
              ['label'=>'IC Number','value'=>$receipt['ic_number'] ?? 'N/A'],
              ['label'=>'Phone','value'=>$receipt['phone'] ?? 'N/A'],
              ['label'=>'Address','value'=>$receipt['address'] ?? 'N/A'],
              ['label'=>'Employer','value'=>$receipt['employer'] ?? 'N/A'],
            ];
            foreach ($learnerInfo as $info): ?>
            <div class="receipt-row">
              <span class="receipt-label"><?= $info['label'] ?></span>
              <span class="receipt-value"><?= htmlspecialchars($info['value']) ?></span>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Course Info -->
          <div class="col-md-6 mt-4 mt-md-0">
            <h4 style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;
                       color:var(--text-muted);margin-bottom:14px;padding-bottom:8px;border-bottom:1px solid var(--border);">
              <i class="fas fa-book me-2" style="color:var(--accent);margin-right:6px;"></i>Course Information
            </h4>
            <?php
            $courseInfo = [
              ['label'=>'Course Title','value'=>$receipt['course_title']],
              ['label'=>'Category','value'=>$receipt['category'] ?? 'General'],
              ['label'=>'Training Provider','value'=>$receipt['org_name']],
              ['label'=>'Start Date','value'=>$receipt['startDate']?date('d M Y',strtotime($receipt['startDate'])):'TBD'],
              ['label'=>'End Date','value'=>$receipt['endDate']?date('d M Y',strtotime($receipt['endDate'])):'TBD'],
              ['label'=>'Course Fee','value'=>$receipt['fee']>0?'RM '.number_format($receipt['fee'],2):'FREE'],
            ];
            foreach ($courseInfo as $info): ?>
            <div class="receipt-row">
              <span class="receipt-label"><?= $info['label'] ?></span>
              <span class="receipt-value"><?= htmlspecialchars($info['value']) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Total -->
        <div class="receipt-total mt-4">
          <div>
            <div style="font-size:.78rem;color:var(--text-muted);margin-bottom:3px;text-transform:uppercase;letter-spacing:.5px;">Total Amount Paid</div>
            <div style="font-weight:800;font-size:1.4rem;color:var(--primary);">
              <?= $receipt['fee']>0 ? 'RM '.number_format($receipt['fee'],2) : 'FREE' ?>
            </div>
          </div>
          <div style="text-align:right;">
            <div class="badge-status badge-approved" style="font-size:.85rem;padding:8px 16px;">
              <i class="fas fa-check-circle me-2" style="margin-right:6px;"></i>
              Payment Confirmed
            </div>
            <div style="font-size:.75rem;color:var(--text-muted);margin-top:6px;">
              Receipt: <?= $receiptNumber ?>
            </div>
          </div>
        </div>

        <!-- Footer Note -->
        <div style="margin-top:28px;padding-top:20px;border-top:1px solid var(--border);
                    text-align:center;font-size:.78rem;color:var(--text-muted);line-height:1.7;">
          <i class="fas fa-info-circle me-1" style="margin-right:4px;"></i>
          This is an official enrolment receipt issued by EduSkill Marketplace — Ministry of Human Resources, Malaysia.<br>
          For enquiries, contact <a href="mailto:info@eduskill.gov.my">info@eduskill.gov.my</a> or call +60 0123456789.<br>
          <strong>Receipt No: <?= $receiptNumber ?></strong> &bull; Generated: <?= date('d M Y, h:i A') ?>
        </div>
      </div>
    </div>

    <!-- Rate Course Prompt -->
    <?php if ($receipt['paymentStatus'] === 'paid'): ?>
    <div class="alert-ems alert-info-ems mt-4 no-print" data-aos="fade-up">
      <i class="fas fa-star"></i>
      <div>
        <strong>Enjoying the course?</strong> Share your experience and help other learners.
        <a href="<?= APP_URL ?>/pages/review.php?course=<?= $receipt['courseID'] ?>" style="font-weight:700;color:var(--primary);margin-left:8px;">
          Leave a Review <i class="fas fa-arrow-right" style="font-size:.8rem;margin-left:4px;"></i>
        </a>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>
</main>

<?php include '../includes/footer.php'; ?>
