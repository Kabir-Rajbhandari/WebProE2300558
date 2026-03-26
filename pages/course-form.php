<?php

require_once '../php/config.php';
requireRole('provider');

$userId = $_SESSION['user_id'];
$db     = getDbConnection();

// Get provider
$ps = $db->prepare("SELECT * FROM providers WHERE userID=? AND status='approved' LIMIT 1");
$ps->bind_param('i', $userId);
$ps->execute();
$provider = $ps->get_result()->fetch_assoc();
if (!$provider) redirect(APP_URL . '/pages/provider-dashboard.php');
$provId = $provider['providerID'];

// Edit mode?
$courseId = (int)($_GET['id'] ?? 0);
$course   = null;
$isEdit   = false;

if ($courseId) {
    $cs = $db->prepare("SELECT * FROM courses WHERE courseID=? AND providerID=?");
    $cs->bind_param('ii', $courseId, $provId);
    $cs->execute();
    $course = $cs->get_result()->fetch_assoc();
    if ($course) $isEdit = true;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title      = sanitize($_POST['courseTitle'] ?? '');
    $desc       = sanitize($_POST['courseDesc'] ?? '');
    $fee        = (float)($_POST['courseFee'] ?? 0);
    $category   = sanitize($_POST['courseCategory'] ?? '');
    $startDate  = sanitize($_POST['courseStart'] ?? '');
    $endDate    = sanitize($_POST['courseEnd'] ?? '');
    $seats      = max(1, (int)($_POST['availableSeats'] ?? 30));
    $status     = in_array($_POST['courseStatus'] ?? '', ['active','inactive']) ? $_POST['courseStatus'] : 'active';
    $editId     = (int)($_POST['editId'] ?? 0);
    $imagePath  = $course['image_path'] ?? null; // Keep existing image if editing

    if (strlen($title) < 5)   $errors[] = 'Course title must be at least 5 characters.';
    if (strlen($desc) < 30)   $errors[] = 'Description must be at least 30 characters.';
    if ($fee < 0)              $errors[] = 'Fee cannot be negative.';
    if ($startDate && $endDate && strtotime($endDate) <= strtotime($startDate))
        $errors[] = 'End date must be after start date.';

    // Handle image upload
    if (!empty($_FILES['courseImage']['name'])) {
        $file = $_FILES['courseImage'];
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowed)) {
            $errors[] = 'Course image must be JPEG, PNG, GIF, or WebP format.';
        } elseif ($file['size'] > $maxSize) {
            $errors[] = 'Course image must be smaller than 5MB.';
        } elseif ($file['error'] === UPLOAD_ERR_OK) {
            $uploadsDir = __DIR__ . '/../uploads/courses/';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }
            $fileName = 'course_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            $filePath = $uploadsDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // Delete old image if updating
                if ($editId && $imagePath && file_exists(__DIR__ . '/../' . $imagePath)) {
                    unlink(__DIR__ . '/../' . $imagePath);
                }
                $imagePath = 'uploads/courses/' . $fileName;
            } else {
                $errors[] = 'Failed to upload course image. Please try again.';
            }
        }
    }

    if (empty($errors)) {
        if ($editId) {
            $upd = $db->prepare("UPDATE courses SET title=?,description=?,fee=?,category=?,startDate=?,endDate=?,available_seats=?,image_path=?,status=? WHERE courseID=? AND providerID=?");
            $upd->bind_param('ssdsssissii', $title, $desc, $fee, $category, $startDate, $endDate, $seats, $imagePath, $status, $editId, $provId);
            $upd->execute();
            setFlash('success', 'Course updated successfully.');
        } else {
            $ins = $db->prepare("INSERT INTO courses (providerID,title,description,fee,category,startDate,endDate,available_seats,image_path,status) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $ins->bind_param('issdsssiss', $provId, $title, $desc, $fee, $category, $startDate, $endDate, $seats, $imagePath, $status);
            $ins->execute();
            $newId = $db->insert_id;
            $db->query("INSERT INTO analytics (courseID,views,enrollCount) VALUES ($newId,0,0)");
            setFlash('success', 'Course published successfully!');
        }
        $db->close();
        redirect(APP_URL . '/pages/provider-dashboard.php');
    }
}

$db->close();

$pageTitle = $isEdit ? 'Edit Course' : 'Add New Course';
$activeNav = '';
include '../includes/header.php';
?>

<main>
<div class="page-header">
  <div class="container">
    <div class="breadcrumb-ems">
      <a href="<?= APP_URL ?>/pages/provider-dashboard.php">Dashboard</a><span>/</span>
      <span style="color:var(--text);"><?= $isEdit ? 'Edit Course' : 'Add Course' ?></span>
    </div>
    <h1><?= $isEdit ? 'Edit Course' : 'Add New Course' ?></h1>
    <p style="color:var(--text-muted);margin:0;">
      <?= $isEdit ? 'Update the details of your course.' : 'Fill in the details to publish a new course on EduSkill.' ?>
    </p>
  </div>
</div>

<section class="section-padding-sm">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="card-ems p-4" data-aos="fade-up">
          <h3 style="font-size:1.05rem;font-weight:700;margin-bottom:20px;">
            <i class="fas fa-book-open me-2" style="color:var(--accent);margin-right:8px;"></i>
            Course Information
          </h3>

          <?php if (!empty($errors)): ?>
          <div class="alert-ems alert-danger-ems">
            <i class="fas fa-exclamation-circle"></i>
            <ul style="margin:0;padding-left:16px;">
              <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
            </ul>
          </div>
          <?php endif; ?>

          <form id="courseForm" method="POST" action="course-form.php<?= $courseId?"?id=$courseId":'' ?>" class="form-ems" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="editId" value="<?= $isEdit ? $courseId : 0 ?>" />

            <!-- Title -->
            <div class="form-group">
              <label for="courseTitle">
                <i class="fas fa-heading me-1" style="color:var(--accent);margin-right:4px;"></i>Course Title *
              </label>
              <input type="text" id="courseTitle" name="courseTitle" class="form-control"
                     placeholder="e.g. Advanced Data Analytics with Python"
                     value="<?= htmlspecialchars($course['title'] ?? $_POST['courseTitle'] ?? '') ?>"
                     required />
            </div>

            <!-- Course Image -->
            <div class="form-group">
              <label for="courseImage">
                <i class="fas fa-image me-1" style="color:var(--accent);margin-right:4px;"></i>Course Image <?= !$isEdit ? '*' : '(Optional - leave blank to keep current)' ?>
              </label>
              <div style="position:relative;border:2px dashed var(--border);border-radius:var(--radius);padding:20px;
                          text-align:center;cursor:pointer;transition:all .3s;" id="imageDropZone">
                <input type="file" id="courseImage" name="courseImage" class="form-control" accept="image/*"
                       style="display:none;" <?= !$isEdit ? 'required' : '' ?> />
                <div id="imagePreviewArea">
                  <?php if ($isEdit && !empty($course['image_path'])): ?>
                  <img src="<?= APP_URL . '/' . htmlspecialchars($course['image_path']) ?>" alt="Course" style="max-height:150px;margin-bottom:12px;" />
                  <p style="font-size:.85rem;color:var(--text-muted);margin:0;">
                    <i class="fas fa-check-circle" style="color:var(--success);"></i> Current image
                  </p>
                  <?php else: ?>
                  <i class="fas fa-cloud-upload-alt" style="font-size:2.5rem;color:var(--border);margin-bottom:8px;display:block;"></i>
                  <p style="font-weight:600;margin-bottom:4px;">Click to upload or drag and drop</p>
                  <p style="font-size:.82rem;color:var(--text-muted);margin:0;">JPEG, PNG, GIF, or WebP (Max 5MB)</p>
                  <?php endif; ?>
                </div>
              </div>
              <small style="color:var(--text-muted);font-size:.78rem;">High-quality images help attract learners. Recommended size: 400x300px</small>
            </div>

            <!-- Category + Fee -->
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="courseCategory">
                    <i class="fas fa-tag me-1" style="color:var(--accent);margin-right:4px;"></i>Category
                  </label>
                  <select id="courseCategory" name="courseCategory" class="form-control">
                    <?php
                    $cats = ['Technology','Business','Healthcare','Engineering','Arts & Design',
                             'Finance','Language','Leadership','Project Management','Other'];
                    $selected = $course['category'] ?? $_POST['courseCategory'] ?? '';
                    foreach ($cats as $cat): ?>
                    <option value="<?= $cat ?>" <?= $selected===$cat?'selected':'' ?>><?= $cat ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="courseFee">
                    <i class="fas fa-wallet me-1" style="color:var(--accent);margin-right:4px;"></i>Course Fee (RM)
                  </label>
                  <div style="position:relative;">
                    <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);
                                 color:var(--text-muted);font-weight:600;font-size:.9rem;">RM</span>
                    <input type="number" id="courseFee" name="courseFee" class="form-control"
                           placeholder="0.00" step="0.01" min="0"
                           value="<?= htmlspecialchars($course['fee'] ?? $_POST['courseFee'] ?? '0') ?>"
                           style="padding-left:40px;" />
                  </div>
                  <small style="color:var(--text-muted);font-size:.78rem;">Enter 0 for a free course.</small>
                </div>
              </div>
            </div>

            <!-- Dates -->
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="courseStart">
                    <i class="fas fa-calendar me-1" style="color:var(--accent);margin-right:4px;"></i>Start Date
                  </label>
                  <input type="date" id="courseStart" name="courseStart" class="form-control"
                         value="<?= htmlspecialchars($course['startDate'] ?? $_POST['courseStart'] ?? '') ?>" />
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="courseEnd">
                    <i class="fas fa-calendar-check me-1" style="color:var(--accent);margin-right:4px;"></i>End Date
                  </label>
                  <input type="date" id="courseEnd" name="courseEnd" class="form-control"
                         value="<?= htmlspecialchars($course['endDate'] ?? $_POST['courseEnd'] ?? '') ?>" />
                </div>
              </div>
            </div>

            <!-- Available Seats + Status -->
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="availableSeats">
                    <i class="fas fa-chair me-1" style="color:var(--accent);margin-right:4px;"></i>Available Seats *
                  </label>
                  <input type="number" id="availableSeats" name="availableSeats" class="form-control"
                         placeholder="e.g. 20" min="1" max="9999"
                         value="<?= htmlspecialchars($course['available_seats'] ?? $_POST['availableSeats'] ?? '30') ?>"
                         required />
                  <small style="color:var(--text-muted);font-size:.78rem;">
                    Seats decrease automatically when learners enrol.
                  </small>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="courseStatus">
                    <i class="fas fa-toggle-on me-1" style="color:var(--accent);margin-right:4px;"></i>Status *
                  </label>
                  <?php
                  $curStatus = $course['status'] ?? $_POST['courseStatus'] ?? 'active';
                  ?>
                  <select id="courseStatus" name="courseStatus" class="form-control">
                    <option value="active" <?= $curStatus==='active'?'selected':'' ?>>Active (visible to learners)</option>
                    <option value="inactive" <?= $curStatus==='inactive'?'selected':'' ?>>Inactive (hidden from learners)</option>
                  </select>
                  <small style="color:var(--text-muted);font-size:.78rem;">
                    Inactive courses are hidden from the public course listing.
                  </small>
                </div>
              </div>
            </div>

            <!-- Description -->
            <div class="form-group">
              <label for="courseDesc">
                <i class="fas fa-align-left me-1" style="color:var(--accent);margin-right:4px;"></i>
                Course Description *
              </label>
              <textarea id="courseDesc" name="courseDesc" class="form-control" rows="6"
                        placeholder="Describe the course content, learning objectives, target audience, and what learners will achieve. (min 30 characters)"
                        required><?= htmlspecialchars($course['description'] ?? $_POST['courseDesc'] ?? '') ?></textarea>
              <div style="text-align:right;font-size:.76rem;color:var(--text-muted);margin-top:4px;">
                <span id="descCharCount">0</span> characters
              </div>
            </div>

            <div class="d-flex" style="gap:12px;">
              <a href="<?= APP_URL ?>/pages/provider-dashboard.php" class="btn-outline-ems" style="padding:11px 22px;">
                <i class="fas fa-times"></i> Cancel
              </a>
              <button type="submit" class="btn-accent-ems flex-fill" style="justify-content:center;padding:12px;font-size:1rem;">
                <i class="fas <?= $isEdit ? 'fa-save' : 'fa-paper-plane' ?>"></i>
                <?= $isEdit ? 'Update Course' : 'Save Course' ?>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
</main>

<script>
var desc = document.getElementById('courseDesc');
if (desc) {
  document.getElementById('descCharCount').textContent = desc.value.length;
  desc.addEventListener('input', function(){
    document.getElementById('descCharCount').textContent = this.value.length;
  });
}

// Image upload functionality
var dropZone = document.getElementById('imageDropZone');
var fileInput = document.getElementById('courseImage');
var previewArea = document.getElementById('imagePreviewArea');

if (dropZone && fileInput) {
  // Click to upload
  dropZone.addEventListener('click', function() { fileInput.click(); });

  // File input change
  fileInput.addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
      previewImage(this.files[0]);
    }
  });

  // Drag and drop
  ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, preventDefaults, false);
  });

  function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
  }

  ['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, () => {
      dropZone.style.borderColor = 'var(--primary)';
      dropZone.style.backgroundColor = 'rgba(99, 102, 241, 0.05)';
    }, false);
  });

  ['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, () => {
      dropZone.style.borderColor = 'var(--border)';
      dropZone.style.backgroundColor = 'transparent';
    }, false);
  });

  dropZone.addEventListener('drop', function(e) {
    var dt = e.dataTransfer;
    var files = dt.files;
    if (files && files[0]) {
      fileInput.files = files;
      previewImage(files[0]);
    }
  }, false);

  function previewImage(file) {
    var reader = new FileReader();
    reader.addEventListener('load', function() {
      previewArea.innerHTML = '<img src="' + reader.result + '" alt="Preview" style="max-height:150px;margin-bottom:12px;" />' +
                             '<p style="font-size:.85rem;color:var(--text-muted);margin:0;">' +
                             '<i class="fas fa-check-circle" style="color:var(--success);"></i> ' + file.name + '</p>';
    });
    reader.readAsDataURL(file);
  }
}
</script>

<?php
// Minimal closing: no big footer on provider form pages
?>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script src="<?= APP_URL ?>/js/Main.JS"></script>
</body>
</html>
