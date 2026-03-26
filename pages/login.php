<?php
// ---> User login form for all roles (learner, provider, officer)

require_once '../php/config.php';

// ---> Redirect logged-in users to their dashboard
if (!empty($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'];
    $dashMap = ['learner'=>'/pages/learner-dashboard.php','provider'=>'/pages/provider-dashboard.php','officer'=>'/pages/officer-dashboard.php'];
    redirect(APP_URL . ($dashMap[$role] ?? '/pages/index.php'));
}

$pageTitle = 'Login';
$activeNav = '';
$error = '';

// ---> Process user login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $selectedRole = sanitize($_POST['role'] ?? 'learner');

    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } elseif (!in_array($selectedRole, ['learner', 'provider', 'officer'])) {
        $error = 'Please select a valid role.';
    } elseif ($selectedRole === 'officer') {
        // Officer validation: specific email and password
        if ($email !== 'eduskill@officer.my') {
            $error = 'Officer login requires email: eduskill@officer.my';
        } elseif ($password !== 'officer@123') {
            $error = 'Invalid officer credentials.';
        } else {
            // Officer credentials correct - proceed to database check
            $db = getDbConnection();
            $stmt = $db->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? AND role = 'officer' LIMIT 1");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $db->close();

            if ($user && verifyPassword($password, $user['password'])) {
                // ---> Store user information in session
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_email'] = $user['email'];

                setFlash('success', 'Welcome back, ' . $user['name'] . '!');
                $dashMap = ['learner'=>'/pages/learner-dashboard.php','provider'=>'/pages/provider-dashboard.php','officer'=>'/pages/officer-dashboard.php'];
                $redirect = $_GET['redirect'] ?? ($dashMap[$user['role']] ?? '/pages/index.php');
                redirect(APP_URL . $redirect);
            } else {
                $error = 'Officer account not found. Contact administrator.';
            }
        }
    } elseif ($selectedRole === 'learner' || $selectedRole === 'provider') {
        // Learner/Provider validation: email must be @gmail.com
        if (!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/i', $email)) {
            $error = ucfirst($selectedRole) . ' accounts must use @gmail.com email address.';
        } else {
            $db = getDbConnection();
            $stmt = $db->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $db->close();

            if ($user && verifyPassword($password, $user['password'])) {
                // Check if selected role matches user's actual role
                if ($user['role'] !== $selectedRole) {
                    $error = 'The selected role does not match your account. Your role is: ' . ucfirst($user['role']);
                } else {
                    // ---> Store user information in session
                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_email'] = $user['email'];

                    setFlash('success', 'Welcome back, ' . $user['name'] . '!');
                    $dashMap = ['learner'=>'/pages/learner-dashboard.php','provider'=>'/pages/provider-dashboard.php','officer'=>'/pages/officer-dashboard.php'];
                    $redirect = $_GET['redirect'] ?? ($dashMap[$user['role']] ?? '/pages/index.php');
                    redirect(APP_URL . $redirect);
                }
            } else {
                $error = 'Invalid email or password. Please try again.';
            }
        }
    }
}

include '../includes/header.php';
?>

<main>
<div class="auth-wrapper">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-10 col-lg-8">
        <div class="row no-gutters" style="background:white;border-radius:16px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.1);">

          <!-- Left Panel -->
          <div class="col-md-5 d-none d-md-flex flex-column justify-content-center align-items-center"
               style="background:linear-gradient(160deg,var(--primary) 0%,var(--accent) 100%);padding:50px 36px;color:white;">
            <i class="fas fa-graduation-cap" style="font-size:3.5rem;margin-bottom:20px;opacity:.9;"></i>
            <h3 style="font-weight:800;margin-bottom:12px;text-align:center;">Welcome Back</h3>
            <p style="font-size:.88rem;opacity:.8;text-align:center;margin-bottom:28px;">
              Access your EduSkill dashboard and continue your learning journey.
            </p>
            <ul style="list-style:none;padding:0;margin:0;width:100%;">
              <?php
              $perks = ['Track enrolled courses','Download receipts','Rate &amp; review courses','Manage certifications'];
              foreach ($perks as $perk): ?>
              <li style="display:flex;align-items:center;gap:10px;margin-bottom:10px;font-size:.85rem;opacity:.85;">
                <i class="fas fa-check-circle" style="flex-shrink:0;"></i> <?= $perk ?>
              </li>
              <?php endforeach; ?>
            </ul>
            <p style="margin-top:28px;font-size:.82rem;opacity:.6;text-align:center;">
              Don't have an account?<br>
              <a href="<?= APP_URL ?>/pages/signup.php" style="color:white;font-weight:700;text-decoration:underline;">Sign up free</a>
            </p>
          </div>

          <!-- Right Panel: Form -->
          <div class="col-md-7">
            <div class="auth-card" style="box-shadow:none;border-radius:0;">
              <h2 class="auth-title" style="text-align:center;">Login</h2>
              <p class="auth-subtitle">Enter your credentials to access your account.</p>

              <?php if ($error): ?>
              <div class="alert-ems alert-danger-ems" data-aos="shake">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
              </div>
              <?php endif; ?>

              <form id="loginForm" method="POST" action="login.php" class="form-ems" novalidate>
                <!-- Select Role -->
                <div class="form-group">
                  <label for="loginRole">Select Your Role</label>
                  <select id="loginRole" name="role" class="form-control" required>
                    <option value="">-- Choose Role --</option>
                    <option value="officer" <?= ($_POST['role'] ?? '') === 'officer' ? 'selected' : '' ?>>Officer</option>
                    <option value="learner" <?= ($_POST['role'] ?? '') === 'learner' ? 'selected' : '' ?>>Learner</option>
                    <option value="provider" <?= ($_POST['role'] ?? '') === 'provider' ? 'selected' : '' ?>>Provider</option>
                  </select>
                </div>

                <!-- Email -->
                <div class="form-group">
                  <label for="loginEmail">Email Address</label>
                  <input type="email" id="loginEmail" name="email" class="form-control"
                         placeholder="someone@gmail.com"
                         required autocomplete="off" />
                </div>

                <!-- Password -->
                <div class="form-group">
                  <label for="loginPassword" class="mb-2">Password</label>
                  <div style="position:relative;">
                    <input type="password" id="loginPassword" name="password" class="form-control"
                           required autocomplete="new-password"
                           style="padding-right:44px;" />
                    <button type="button" id="togglePassword"
                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);
                                   background:none;border:none;color:var(--text-muted);cursor:pointer;"
                            onclick="var f=document.getElementById('loginPassword');
                                     f.type=f.type==='password'?'text':'password';
                                     this.innerHTML=f.type==='password'?'<i class=\'fas fa-eye\'></i>':'<i class=\'fas fa-eye-slash\'></i>'">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                </div>

                <button type="submit" class="btn-primary-ems w-100"
                        style="padding:12px;font-size:1rem;justify-content:center;">
                  Login
                </button>
              </form>

              <div class="divider-ems">or</div>

              <div class="text-center" style="font-size:.9rem;color:var(--text-muted);">
                New to EduSkill?
                <a href="<?= APP_URL ?>/pages/signup.php" style="font-weight:700;color:var(--primary);">Create an account</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</main>

<?php include '../includes/footer.php'; ?>
