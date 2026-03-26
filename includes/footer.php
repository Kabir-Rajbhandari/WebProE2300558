<?php
// ---> Reusable footer component 
?>
<!-- FOOTER SECTION -->
<footer class="footer-ems">
  <div class="container">
    <div class="row">

      <!-- Brand & Description -->
      <div class="col-lg-4 col-md-6 mb-4">
        <a href="<?= APP_URL ?>/pages/index.php" class="footer-brand-link" style="display:inline-block;margin-bottom:12px;">
          <img src="<?= APP_URL ?>/assets/images/logo.png" alt="EduSkill Logo"
               style="height:120px;width:auto;object-fit:contain;">
        </a>
        <p class="footer-desc" style="margin-top: -15px;">
          Malaysia's official Ministry of Human Resources platform connecting learners
          with accredited training providers for professional upskilling.
        </p>
        <div class="d-flex mt-3" style="gap:10px;">
          <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
          <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
          <a href="#" class="social-icon"><i class="fab fa-youtube"></i></a>
        </div>
      </div>

      <!-- Platform Links -->
      <div class="col-lg-2 col-md-6 mb-4">
        <h6>Platform</h6>
        <ul>
          <li><a href="<?= APP_URL ?>/pages/index.php">Home</a></li>
          <li><a href="<?= APP_URL ?>/pages/courses.php">Browse Courses</a></li>
          <li><a href="<?= APP_URL ?>/pages/about.php">About Us</a></li>
          <li><a href="<?= APP_URL ?>/pages/contact.php">Contact</a></li>
          <li><a href="<?= APP_URL ?>/pages/faq.php">FAQ</a></li>
        </ul>
      </div>

      <!-- For Users -->
      <div class="col-lg-3 col-md-6 mb-4">
        <h6>For Users</h6>
        <ul>
          <li><a href="<?= APP_URL ?>/pages/signup.php">Learner Registration</a></li>
          <li><a href="<?= APP_URL ?>/pages/signup.php?type=provider">Provider Registration</a></li>
          <li><a href="<?= APP_URL ?>/pages/login.php">Login</a></li>
          <li><a href="<?= APP_URL ?>/pages/learner-dashboard.php">My Dashboard</a></li>
        </ul>
      </div>

      <!-- Contact Info -->
      <div class="col-lg-3 col-md-6 mb-4">
        <h6>Contact</h6>
        <ul>
          <li style="display:flex;gap:10px;align-items:flex-start;margin-bottom:10px;">
            <i class="fas fa-map-marker-alt mt-1" style="color:var(--primary);flex-shrink:0;font-size:.85rem;margin-top:3px;"></i>
            <span style="font-size:.85rem;color:rgba(255,255,255,0.55);">
              Ministry of Human Resources,<br>Kuala Lumpur, Malaysia
            </span>
          </li>
          <li style="display:flex;gap:10px;align-items:center;margin-bottom:8px;">
            <i class="fas fa-envelope" style="color:var(--primary);font-size:.85rem;flex-shrink:0;"></i>
            <a href="mailto:info@eduskill.gov.my" style="font-size:.85rem;">info@eduskill.gov.my</a>
          </li>
          <li style="display:flex;gap:10px;align-items:center;">
            <i class="fas fa-phone" style="color:var(--primary);font-size:.85rem;flex-shrink:0;"></i>
            <span style="font-size:.85rem;color:rgba(255,255,255,0.55);">+60 0123456789</span>
          </li>
        </ul>
      </div>

    </div>

    <!-- Footer Bottom -->
    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> EduSkill Marketplace System. Ministry of Human Resources, Malaysia.</span>
      <span>Designed &amp; Developed by Team Code Trinity.</span>
    </div>
  </div>
</footer>


<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="<?= APP_URL ?>/js/Main.JS"></script>
<script src="<?= APP_URL ?>/js/Charts.JS"></script>

</body>
</html>