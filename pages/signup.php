<?php
// ---> User registration form for learners and training providers

require_once '../php/config.php';

if (!empty($_SESSION['user_id'])) {
    redirect(APP_URL . '/index.php');
}

$pageTitle = 'Sign Up';
$activeNav = '';
$errors    = [];
// ---> Account type selection from URL (learner or provider)
$type      = sanitize($_GET['type'] ?? 'learner');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type      = sanitize($_POST['type'] ?? 'learner');
    $name      = sanitize($_POST['name'] ?? '');
    $email     = sanitize($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm'] ?? '';
    $phone     = sanitize($_POST['phone'] ?? '');
    $orgName   = sanitize($_POST['org_name'] ?? '');
    $orgProfile = sanitize($_POST['org_profile'] ?? '');

    // ---> Validate form input
    if (strlen($name) < 3) $errors[] = 'Full name must be at least 3 characters.';
    
    // Email validation based on account type
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    } elseif ($type === 'learner' || $type === 'provider') {
        // Learner and Provider must use @gmail.com
        if (!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/i', $email)) {
            $errors[] = ucfirst($type) . ' accounts must be registered with a @gmail.com email address.';
        }
    }
    if (!preg_match('/^(?=.*[A-Z])(?=.*\d).{8,}$/', $password))
        $errors[] = 'Password must be at least 8 characters with one uppercase and one number.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';
    if (!preg_match('/^[+]?[\d\s\-]{8,15}$/', $phone)) $errors[] = 'Invalid phone number.';

    if ($type === 'provider') {
        if (strlen($orgName) < 3) $errors[] = 'Organisation name is required.';
        if (strlen($orgProfile) < 20) $errors[] = 'Organisation profile must be at least 20 characters.';
    }

    // ---> Check for duplicate email address in database
    if (empty($errors)) {
        $db = getDbConnection();
        $chk = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $chk->bind_param('s', $email);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $errors[] = 'This email address is already registered.';
        }
        $db->close();
    }

    // ---> Handle file upload for provider documentation
    $docPath = null;
    if ($type === 'provider' && empty($errors)) {
        if (empty($_FILES['document']['name'])) {
            $errors[] = 'Supporting document is required for provider registration.';
        } else {
            $file     = $_FILES['document'];
            $fileMime = mime_content_type($file['tmp_name']);
            $fileExt  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if ($file['size'] > MAX_FILE_SIZE) {
                $errors[] = 'Document must be under 5MB.';
            } elseif (!in_array($fileMime, ALLOWED_FILE_TYPES) || !in_array($fileExt, ['pdf','doc','docx'])) {
                $errors[] = 'Only PDF or DOC/DOCX files are accepted.';
            } else {
                $safeFilename = uniqid('doc_', true) . '.' . $fileExt;
                if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
                if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $safeFilename)) {
                    $docPath = 'uploads/documents/' . $safeFilename;
                } else {
                    $errors[] = 'File upload failed. Please try again.';
                }
            }
        }
    }

    // ---> Create new user account in database
    if (empty($errors)) {
        $db   = getDbConnection();
        $hash = hashPassword($password);
        $role = $type === 'provider' ? 'provider' : 'learner';

        $insUser = $db->prepare("INSERT INTO users (name, email, password, role, phone) VALUES (?,?,?,?,?)");
        $insUser->bind_param('sssss', $name, $email, $hash, $role, $phone);
        $insUser->execute();
        $userId = $db->insert_id;

        if ($type === 'provider') {
            $status = 'pending';
            $insProv = $db->prepare("INSERT INTO providers (userID, org_name, org_profile, status, document_path) VALUES (?,?,?,?,?)");
            $insProv->bind_param('issss', $userId, $orgName, $orgProfile, $status, $docPath);
            $insProv->execute();
        }
        $db->close();

        if ($type === 'provider') {
            setFlash('info', 'Registration submitted! Your account is pending Ministry approval. You will be notified by email.');
        } else {
            setFlash('success', 'Account created successfully! Please log in.');
        }
        redirect(APP_URL . '/pages/login.php');
    }
}

include '../includes/header.php';
?>

<main>
<div class="auth-wrapper" style="align-items:flex-start;padding:50px 0;">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8 col-md-10">
        <div class="auth-card" style="max-width:100%;">
          <h2 class="auth-title" style="text-align:center;">Create Account</h2>
          <p class="auth-subtitle">Join EduSkill Marketplace — free for learners, fast approval for providers.</p>

          <!-- Role Toggle -->
          <div style="display:flex;background:var(--card-bg);border-radius:10px;padding:4px;
                      border:1px solid var(--border);margin-bottom:28px;">
            <a href="?type=learner"
               style="flex:1;text-align:center;padding:10px;border-radius:8px;font-weight:600;
                      font-size:.9rem;transition:all .2s;
                      <?= $type === 'learner' ? 'background:var(--primary);color:white;box-shadow:0 3px 10px rgba(37,99,235,.3);' : 'color:var(--text-muted);' ?>">
              <i class="fas fa-user-graduate me-1" style="margin-right:5px;"></i>Learner
            </a>
            <a href="?type=provider"
               style="flex:1;text-align:center;padding:10px;border-radius:8px;font-weight:600;
                      font-size:.9rem;transition:all .2s;
                      <?= $type === 'provider' ? 'background:var(--accent);color:white;box-shadow:0 3px 10px rgba(168,85,247,.3);' : 'color:var(--text-muted);' ?>">
              <i class="fas fa-building me-1" style="margin-right:5px;"></i>Training Provider
            </a>
          </div>

          <?php if (!empty($errors)): ?>
          <div class="alert-ems alert-danger-ems">
            <i class="fas fa-exclamation-circle"></i>
            <ul style="margin:0;padding-left:16px;">
              <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php endif; ?>

          <form id="signupForm" method="POST" action="signup.php" class="form-ems"
                enctype="multipart/form-data" novalidate>
            <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>" />

            <div class="row">
              <!-- Full Name -->
              <div class="col-md-6">
                <div class="form-group">
                  <label for="signupName">Full Name *</label>
                  <input type="text" id="signupName" name="name" class="form-control"
                         placeholder="Your full name"
                         value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required />
                </div>
              </div>

              <!-- Email -->
              <div class="col-md-6">
                <div class="form-group">
                  <label for="signupEmail">Email Address *</label>
                  <input type="email" id="signupEmail" name="email" class="form-control"
                         placeholder="someone@gmail.com"
                         value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required />
                </div>
              </div>

              <!-- Phone -->
              <div class="col-md-6">
                <div class="form-group">
                  <label for="signupPhone">Phone Number *</label>
                  <input type="tel" id="signupPhone" name="phone" class="form-control"
                         placeholder="e.g. 0123456789"
                         value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required />
                </div>
              </div>

              <!-- Password -->
              <div class="col-md-6">
                <div class="form-group">
                  <label for="signupPassword">Password *</label>
                  <input type="password" id="signupPassword" name="password" class="form-control"
                         placeholder="Min 8 chars, 1 uppercase, 1 number" autocomplete="new-password" required />
                  <!-- Strength meter -->
                  <div id="passwordStrength" style="margin-top:6px;">
                    <div style="background:var(--border);border-radius:3px;height:4px;overflow:hidden;">
                      <div class="strength-bar" style="height:100%;width:0;transition:all .3s;"></div>
                    </div>
                    <span class="strength-label" style="font-size:.76rem;"></span>
                  </div>
                </div>
              </div>

              <!-- Confirm Password -->
              <div class="col-md-12">
                <div class="form-group">
                  <label for="signupConfirm">Confirm Password *</label>
                  <input type="password" id="signupConfirm" name="confirm" class="form-control"
                         placeholder="Re-enter your password" autocomplete="new-password" required />
                </div>
              </div>

              <?php if ($type === 'provider'): ?>
              <!-- Provider Fields -->
              <div class="col-12">
                <div style="border-top:1px solid var(--border);padding-top:20px;margin-bottom:20px;">
                  <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;
                              letter-spacing:.8px;color:var(--accent);margin-bottom:16px;">
                    Provider Details
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label for="orgName">Organisation Name *</label>
                  <input type="text" id="orgName" name="org_name" class="form-control"
                         placeholder="Your company/organisation name"
                         value="<?= htmlspecialchars($_POST['org_name'] ?? '') ?>" required />
                </div>
              </div>

              <div class="col-md-12">
                <div class="form-group">
                  <label for="orgProfile">Organisation Profile *</label>
                  <textarea id="orgProfile" name="org_profile" class="form-control" rows="3"
                            placeholder="Brief description of your organisation and services (min 20 characters)"
                            required><?= htmlspecialchars($_POST['org_profile'] ?? '') ?></textarea>
                </div>
              </div>

              <div class="col-md-12">
                <div class="form-group">
                  <label for="providerDocument">Supporting Document (Company Registration / Accreditation) *</label>
                  <input type="file" id="providerDocument" name="document" class="form-control"
                         accept=".pdf,.doc,.docx" required />
                  <small style="color:var(--text-muted);font-size:.8rem;display:block;margin-top:6px;">
                    Accepted: PDF, DOC, DOCX. Max size: 5MB.
                  </small>
                </div>
              </div>

              <div class="col-12">
                <div class="alert-ems alert-info-ems">
                  <span>Provider accounts require Ministry approval before login access is granted. You will be notified by email.</span>
                </div>
              </div>
              <?php endif; ?>
            </div>

            <button type="submit" class="btn-primary-ems w-100 mt-2"
                    style="padding:13px;font-size:1rem;justify-content:center;
                           <?= $type === 'provider' ? 'background:var(--accent);' : '' ?>">
              <?= $type === 'provider' ? 'Submit Registration for Approval' : 'Create Learner Account' ?>
            </button>
          </form>

          <div class="divider-ems" style="margin:20px 0 16px;">or</div>
          <div class="text-center" style="font-size:.9rem;color:var(--text-muted);">
            Already have an account?
            <a href="<?= APP_URL ?>/pages/login.php" style="font-weight:700;color:var(--primary);">Login</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</main>

<?php include '../includes/footer.php'; ?>
