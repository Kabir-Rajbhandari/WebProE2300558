<?php

require_once '../php/config.php';
requireRole('learner');

$courseId = (int)($_GET['course'] ?? 0);
if (!$courseId) redirect(APP_URL . '/learner-dashboard.php');

$userId = $_SESSION['user_id'];
$db     = getDbConnection();

// Check enrolled & paid
$chk = $db->prepare("SELECT enrollID FROM enrollments WHERE learnerID=? AND courseID=? AND paymentStatus='paid' LIMIT 1");
$chk->bind_param('ii', $userId, $courseId);
$chk->execute();
if ($chk->get_result()->num_rows === 0) {
    setFlash('warning', 'You must be enrolled in this course to leave a review.');
    redirect(APP_URL . '/learner-dashboard.php');
}

// Check already reviewed
$revChk = $db->prepare("SELECT reviewID FROM reviews WHERE learnerID=? AND courseID=? LIMIT 1");
$revChk->bind_param('ii', $userId, $courseId);
$revChk->execute();
if ($revChk->get_result()->num_rows > 0) {
    setFlash('info', 'You have already submitted a review for this course.');
    redirect(APP_URL . '/learner-dashboard.php');
}

// Get course
$cs = $db->prepare("SELECT c.*, p.org_name FROM courses c JOIN providers p ON c.providerID=p.providerID WHERE c.courseID=?");
$cs->bind_param('i', $courseId);
$cs->execute();
$course = $cs->get_result()->fetch_assoc();
if (!$course) redirect(APP_URL . '/courses.php');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating   = (int)($_POST['rating'] ?? 0);
    $feedback = sanitize($_POST['feedback'] ?? '');

    if ($rating < 1 || $rating > 5) $errors[] = 'Please select a star rating.';
    if (strlen($feedback) < 10)       $errors[] = 'Feedback must be at least 10 characters.';

    if (empty($errors)) {
        $ins = $db->prepare("INSERT INTO reviews (learnerID, courseID, rating, feedback, created_at) VALUES (?,?,?,?,NOW())");
        $ins->bind_param('iiis', $userId, $courseId, $rating, $feedback);
        $ins->execute();
        $db->close();
        setFlash('success', 'Thank you! Your review has been submitted successfully.');
        redirect(APP_URL . '/learner-dashboard.php');
    }
}

$db->close();

$pageTitle = 'Review: ' . htmlspecialchars($course['title']);
$activeNav = '';
include '../includes/header.php';
?>

<main>
<div class="page-header">
  <div class="container">
    <div class="breadcrumb-ems">
      <a href="<?= APP_URL ?>/pages/learner-dashboard.php">Dashboard</a><span>/</span>
      <span style="color:var(--text);">Submit Review</span>
    </div>
    <h1>Rate &amp; Review Course</h1>
    <p style="color:var(--text-muted);margin:0;">Your feedback helps other learners make informed decisions.</p>
  </div>
</div>

<section class="section-padding-sm">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-7">
        <div class="card-ems p-4" data-aos="fade-up">
          <!-- Course Info -->
          <div style="background:linear-gradient(135deg,var(--primary-light),#ede9fe);
                      border-radius:10px;padding:20px;margin-bottom:24px;display:flex;align-items:center;gap:16px;">
            <div style="width:52px;height:52px;background:white;border-radius:10px;
                        display:flex;align-items:center;justify-content:center;flex-shrink:0;
                        box-shadow:0 4px 12px rgba(0,0,0,.1);">
              <i class="fas fa-book" style="color:var(--primary);font-size:1.2rem;"></i>
            </div>
            <div>
              <div style="font-weight:700;font-size:.95rem;margin-bottom:2px;"><?= htmlspecialchars($course['title']) ?></div>
              <div style="font-size:.82rem;color:var(--text-muted);">
                <i class="fas fa-building me-1" style="margin-right:3px;"></i><?= htmlspecialchars($course['org_name']) ?>
              </div>
            </div>
          </div>

          <?php if (!empty($errors)): ?>
          <div class="alert-ems alert-danger-ems">
            <i class="fas fa-exclamation-circle"></i>
            <ul style="margin:0;padding-left:16px;">
              <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
            </ul>
          </div>
          <?php endif; ?>

          <form id="reviewForm" method="POST" action="review.php?course=<?= $courseId ?>" class="form-ems" novalidate>

            <!-- Star Rating -->
            <div class="form-group">
              <label style="display:block;margin-bottom:12px;">
                <i class="fas fa-star me-1" style="color:var(--warning);margin-right:5px;"></i>
                Your Rating *
              </label>
              <div style="display:flex;align-items:center;gap:8px;">
                <div class="star-input" id="starInput">
                  <?php for ($s = 5; $s >= 1; $s--): ?>
                  <input type="radio" name="rating" id="star<?= $s ?>" value="<?= $s ?>"
                         <?= (isset($_POST['rating']) && (int)$_POST['rating']===$s)?'checked':'' ?> />
                  <label for="star<?= $s ?>" title="<?= $s ?> star<?= $s>1?'s':'' ?>">
                    <i class="fas fa-star"></i>
                  </label>
                  <?php endfor; ?>
                </div>
                <span id="ratingLabel" style="font-size:.88rem;color:var(--text-muted);margin-left:10px;"></span>
              </div>
            </div>

            <!-- Feedback -->
            <div class="form-group">
              <label for="reviewFeedback">
                <i class="fas fa-comment me-1" style="color:var(--primary);margin-right:5px;"></i>
                Written Feedback *
              </label>
              <textarea id="reviewFeedback" name="feedback" class="form-control" rows="5"
                        placeholder="Share your experience with this course. What did you learn? Would you recommend it? (min 10 characters)"
                        required><?= htmlspecialchars($_POST['feedback'] ?? '') ?></textarea>
              <div style="text-align:right;font-size:.76rem;color:var(--text-muted);margin-top:4px;">
                <span id="charCount">0</span> characters
              </div>
            </div>

            <div class="d-flex" style="gap:12px;">
              <a href="<?= APP_URL ?>/pages/learner-dashboard.php" class="btn-outline-ems" style="padding:11px 22px;">
                <i class="fas fa-times"></i> Cancel
              </a>
              <button type="submit" class="btn-accent-ems flex-fill" style="justify-content:center;padding:12px;font-size:1rem;">
                <i class="fas fa-paper-plane"></i> Submit Review
              </button>
            </div>
          </form>
        </div>

        <!-- Guidelines -->
        <div class="card-ems p-4 mt-3" data-aos="fade-up" data-aos-delay="100">
          <h5 style="font-size:.9rem;font-weight:700;margin-bottom:12px;">
            <i class="fas fa-lightbulb me-2" style="color:var(--warning);margin-right:6px;"></i>Review Guidelines
          </h5>
          <ul style="list-style:none;padding:0;margin:0;font-size:.85rem;color:var(--text-muted);">
            <?php
            $icons = ['fa-check','fa-check','fa-check','fa-times','fa-times'];
            $colors = ['var(--success)','var(--success)','var(--success)','var(--danger)','var(--danger)'];
            $texts = [
              'Be honest and constructive in your feedback.',
              'Describe what you liked and what could be improved.',
              'Mention specific topics, skills or assignments.',
              'Avoid personal attacks or inappropriate language.',
              'Do not include contact info or external links.',
            ];
            foreach ($icons as $i => $icon): ?>
            <li style="display:flex;gap:10px;align-items:flex-start;margin-bottom:8px;">
              <i class="fas <?= $icons[$i] ?>" style="color:<?= $colors[$i] ?>;flex-shrink:0;margin-top:2px;"></i>
              <?= $texts[$i] ?>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>
</main>

<script>
// Star rating labels
var ratingLabels = { 1:'Poor', 2:'Fair', 3:'Good', 4:'Very Good', 5:'Excellent' };
$('input[name="rating"]').on('change', function(){
  $('#ratingLabel').text(ratingLabels[$(this).val()] || '');
});

// Character counter
$('#reviewFeedback').on('input', function(){
  $('#charCount').text($(this).val().length);
});
</script>

<?php include '../includes/footer.php'; ?>
