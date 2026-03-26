<?php
// ---> Main landing page displaying featured courses, providers, and platform statistics

require_once '../php/config.php';

$pageTitle = 'Home';
$activeNav = 'home';

// ---> Fetch latest 6 courses from database
$db = getDbConnection();
$coursesStmt = $db->prepare("
    SELECT c.courseID, c.title, c.description, c.fee, c.startDate, c.category, c.image_path,
           p.org_name,
           COALESCE(AVG(r.rating), 0) AS avg_rating,
           COUNT(DISTINCT e.enrollID) AS enroll_count
    FROM courses c
    JOIN providers p ON c.providerID = p.providerID
    LEFT JOIN reviews r ON c.courseID = r.courseID
    LEFT JOIN enrollments e ON c.courseID = e.courseID
    GROUP BY c.courseID
    ORDER BY c.courseID DESC
    LIMIT 6
");
$coursesStmt->execute();
$courses = $coursesStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ---> Get featured providers with most courses and approvals
$providersStmt = $db->prepare("
    SELECT p.providerID, p.org_name, COUNT(c.courseID) AS course_count
    FROM providers p
    LEFT JOIN courses c ON p.providerID = c.providerID
    WHERE p.status = 'approved'
    GROUP BY p.providerID
    ORDER BY course_count DESC
    LIMIT 4
");
$providersStmt->execute();
$providers = $providersStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ---> Calculate and display platform statistics
$stats = [];
$statsQuery = $db->query("SELECT
    (SELECT COUNT(*) FROM users WHERE role='learner') AS total_learners,
    (SELECT COUNT(*) FROM courses) AS total_courses,
    (SELECT COUNT(*) FROM providers WHERE status='approved') AS total_providers,
    (SELECT COUNT(*) FROM enrollments WHERE paymentStatus='paid') AS total_enrollments
");
$stats = $statsQuery->fetch_assoc();
$db->close();

include '../includes/header.php';
?>

<!-- Hero Section-->
<section class="hero-section" role="banner">
  <div class="container">
    <div class="row align-items-center">
      <!-- Left: Copy -->
      <div class="col-lg-6 mb-5 mb-lg-0">
        <div data-aos="fade-right" data-aos-duration="700">
          <span class="section-tag">
            <i class="fas fa-star me-1" style="margin-right:5px;"></i>Ministry of Human Resources
          </span>
          <h1 class="hero-title mt-2">
            Upskill &amp; Grow<br>
            With <span class="highlight">EduSkill</span><br>
            Marketplace
          </h1>
          <p class="hero-subtitle">
            Discover accredited short courses, workshops and certification programmes
            from Malaysia's trusted training providers. Learn at your own pace.
          </p>
          <div class="d-flex flex-wrap" style="gap:12px;">
            <a href="<?= APP_URL ?>/pages/courses.php" class="btn-primary-ems" style="padding:13px 28px;font-size:1rem;">
              <i class="fas fa-search"></i> Browse Courses
            </a>
            <a href="<?= APP_URL ?>/pages/signup.php" class="btn-outline-ems" style="padding:13px 28px;font-size:1rem;">
              <i class="fas fa-user-plus"></i> Get Started Free
            </a>
          </div>

          <!-- Stats -->
          <div class="hero-stats" data-aos="fade-up" data-aos-delay="300">
            <div class="hero-stat-item">
              <span class="stat-num"><?= number_format($stats['total_courses']) ?>+</span>
              <span class="stat-label">Courses</span>
            </div>
            <div class="hero-stat-item">
              <span class="stat-num"><?= number_format($stats['total_learners']) ?>+</span>
              <span class="stat-label">Learners</span>
            </div>
            <div class="hero-stat-item">
              <span class="stat-num"><?= number_format($stats['total_providers']) ?>+</span>
              <span class="stat-label">Providers</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Visual -->
      <div class="col-lg-6" data-aos="fade-left" data-aos-duration="700" data-aos-delay="150">
        <div class="hero-visual" style="min-height:380px;">
          <!-- Main illustration card -->
          <div style="background:white;border-radius:20px;box-shadow:0 20px 60px rgba(37,99,235,0.15);
                      padding:32px;width:100%;max-width:380px;position:relative;margin:0 auto;">
            <div style="background:linear-gradient(135deg,#EFF6FF,#F5F3FF);border-radius:12px;
                        padding:28px;text-align:center;margin-bottom:20px;">
              <i class="fas fa-graduation-cap" style="font-size:4rem;color:var(--primary);"></i>
            </div>
            <div style="display:flex;flex-direction:column;gap:10px;">
              <?php foreach(array_slice($courses, 0, 3) as $c): ?>
              <div style="display:flex;align-items:center;gap:12px;padding:10px;
                          background:#F9FAFB;border-radius:8px;border:1px solid #E5E7EB;">
                <div style="width:36px;height:36px;background:var(--primary-light);border-radius:8px;
                            display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                  <i class="fas fa-book" style="color:var(--primary);font-size:.8rem;"></i>
                </div>
                <div style="flex:1;min-width:0;">
                  <div style="font-size:.82rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    <?= htmlspecialchars($c['title']) ?>
                  </div>
                  <div style="font-size:.74rem;color:var(--text-muted);">
                    <?= htmlspecialchars($c['org_name']) ?>
                  </div>
                </div>
                <div style="font-size:.82rem;font-weight:700;color:var(--primary);flex-shrink:0;">
                  <?= $c['fee'] > 0 ? 'RM'.number_format($c['fee'],0) : 'Free' ?>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Floating cards -->
          <div class="hero-card-float left" style="z-index:10;padding:12px 16px;min-width:130px;">
            <div style="font-size:.7rem;color:var(--text-muted);margin-bottom:2px;">Enrolled Today</div>
            <div style="font-size:1.3rem;font-weight:800;color:var(--primary);">
              <?= number_format($stats['total_enrollments']) ?>
            </div>
            <div style="font-size:.72rem;color:var(--success);">
              <i class="fas fa-arrow-up me-1" style="margin-right:3px;"></i>Learners
            </div>
          </div>

          <div class="hero-card-float right" style="z-index:10;padding:12px 16px;min-width:140px;">
            <div style="font-size:.7rem;color:var(--text-muted);margin-bottom:2px;">Top Rating</div>
            <div style="color:#F59E0B;font-size:1rem;margin-bottom:2px;">
              <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
              <i class="fas fa-star"></i><i class="fas fa-star"></i>
            </div>
            <div style="font-size:.72rem;color:var(--text-muted);">Verified Reviews</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Section -->
<section class="stats-section" aria-label="Platform statistics">
  <div class="container">
    <div class="row">
      <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="0">
        <div class="stat-card">
          <span class="stat-number" data-target="<?= $stats['total_courses'] ?>">0</span>
          <span class="stat-label"><i class="fas fa-book-open me-1" style="margin-right:5px;"></i>Active Courses</span>
        </div>
      </div>
      <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="100">
        <div class="stat-card">
          <span class="stat-number" data-target="<?= $stats['total_learners'] ?>">0</span>
          <span class="stat-label"><i class="fas fa-user-graduate me-1" style="margin-right:5px;"></i>Registered Learners</span>
        </div>
      </div>
      <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="200">
        <div class="stat-card">
          <span class="stat-number" data-target="<?= $stats['total_providers'] ?>">0</span>
          <span class="stat-label"><i class="fas fa-building me-1" style="margin-right:5px;"></i>Training Providers</span>
        </div>
      </div>
      <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="300">
        <div class="stat-card">
          <span class="stat-number" data-target="<?= $stats['total_enrollments'] ?>">0</span>
          <span class="stat-label"><i class="fas fa-certificate me-1" style="margin-right:5px;"></i>Enrollments Completed</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Latest Section -->
<section class="section-padding" aria-label="Latest courses">
  <div class="container">
    <div class="section-heading" data-aos="fade-up">
      <span class="section-tag"><i class="fas fa-fire me-1" style="margin-right:4px;"></i>Latest</span>
      <h2>Explore Recent Courses</h2>
      <p>Browse the newest professional development courses from verified training providers.</p>
    </div>

    <div class="row">
      <?php if (empty($courses)): ?>
        <div class="col-12 text-center py-5">
          <i class="fas fa-book-open" style="font-size:3rem;color:var(--border);margin-bottom:16px;display:block;"></i>
          <p style="color:var(--text-muted);">No courses available yet. Check back soon!</p>
        </div>
      <?php else: ?>
        <?php foreach ($courses as $i => $course): ?>
        <div class="col-lg-4 col-md-6 mb-4 course-card-item"
             data-title="<?= htmlspecialchars($course['title']) ?>"
             data-category="<?= htmlspecialchars($course['category'] ?? '') ?>"
             data-aos="fade-up" data-aos-delay="<?= $i * 80 ?>">
          <article class="card-course">
            <div class="course-thumb">
              <?php if (!empty($course['image_path'])): ?>
              <img src="<?= APP_URL . '/' . htmlspecialchars($course['image_path']) ?>" alt="<?= htmlspecialchars($course['title']) ?>" 
                   style="width:100%;height:100%;object-fit:cover;position:absolute;z-index:0;" />
              <div style="position:absolute;inset:0;background:linear-gradient(135deg,rgba(99,102,241,0.3),rgba(168,85,247,0.3));z-index:1;"></div>
              <?php else: ?>
              <i class="fas fa-book-open" style="position:relative;z-index:1;"></i>
              <?php endif; ?>
              <?php if (!empty($course['category'])): ?>
              <span class="badge-category" style="position:absolute;top:14px;left:14px;z-index:2;">
                <?= htmlspecialchars($course['category']) ?>
              </span>
              <?php endif; ?>
            </div>
            <div class="course-body">
              <div class="course-meta">
                <i class="fas fa-building me-1" style="margin-right:4px;color:var(--text-muted);"></i>
                <?= htmlspecialchars($course['org_name']) ?>
              </div>
              <h3 class="course-title">
                <a href="<?= APP_URL ?>/pages/course-detail.php?id=<?= $course['courseID'] ?>" style="color:var(--text);">
                  <?= htmlspecialchars($course['title']) ?>
                </a>
              </h3>
              <p style="font-size:.83rem;color:var(--text-muted);margin-bottom:0;
                        display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                <?= htmlspecialchars(substr($course['description'], 0, 100)) ?>...
              </p>

              <div class="course-footer">
                <div>
                  <!-- Star Rating -->
                  <div class="star-rating">
                    <?php
                    $rating = round($course['avg_rating']);
                    for ($s = 1; $s <= 5; $s++):
                    ?>
                      <i class="<?= $s <= $rating ? 'fas' : 'far' ?> fa-star"></i>
                    <?php endfor; ?>
                    <span style="font-size:.78rem;color:var(--text-muted);margin-left:4px;">
                      (<?= $course['enroll_count'] ?>)
                    </span>
                  </div>
                </div>
                <div style="text-align:right;">
                  <div style="font-weight:800;color:var(--primary);font-size:1rem;">
                    <?= $course['fee'] > 0 ? 'RM '.number_format($course['fee'], 2) : '<span style="color:var(--success);">Free</span>' ?>
                  </div>
                  <a href="<?= APP_URL ?>/pages/course-detail.php?id=<?= $course['courseID'] ?>"
                     class="btn-primary-ems mt-2" style="padding:6px 14px;font-size:.8rem;">
                    View <i class="fas fa-arrow-right"></i>
                  </a>
                </div>
              </div>
            </div>
          </article>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="text-center mt-3" data-aos="fade-up">
      <a href="<?= APP_URL ?>/pages/courses.php" class="btn-outline-ems" style="padding:12px 32px;font-size:1rem;">
        <i class="fas fa-th-large me-2" style="margin-right:6px;"></i>View All Courses
      </a>
    </div>
  </div>
</section>

<!-- works section-->
<section class="section-padding bg-light-custom" aria-label="How it works">
  <div class="container">
    <div class="section-heading" data-aos="fade-up">
      <span class="section-tag">Simple Process</span>
      <h2>How EduSkill Works</h2>
      <p>Three simple steps to start your learning journey.</p>
    </div>

    <div class="row">
      <?php
      $steps = [
        ['icon'=>'fa-user-plus','title'=>'Create Account','desc'=>'Register as a learner for free. Instant access to all available courses.','color'=>'blue','num'=>'01'],
        ['icon'=>'fa-search','title'=>'Find Your Course','desc'=>'Browse our catalogue of accredited courses. Filter by category, fee, or provider.','color'=>'purple','num'=>'02'],
        ['icon'=>'fa-graduation-cap','title'=>'Enrol & Earn','desc'=>'Complete payment, get your receipt and start learning. Earn certificates upon completion.','color'=>'green','num'=>'03'],
      ];
      foreach ($steps as $i => $step):
      ?>
      <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="<?= $i * 120 ?>">
        <div class="feature-card" style="position:relative;overflow:hidden;">
          <div style="position:absolute;top:16px;right:20px;font-size:3.5rem;font-weight:900;
                      color:var(--border);line-height:1;opacity:.6;"><?= $step['num'] ?></div>
          <div class="feature-icon" style="<?= $step['color']==='purple' ? 'background:#fdf4ff;color:var(--accent);' : ($step['color']==='green' ? 'background:#ecfdf5;color:var(--success);' : '') ?>">
            <i class="fas <?= $step['icon'] ?>"></i>
          </div>
          <h4 style="font-size:1.1rem;font-weight:700;margin-bottom:10px;"><?= $step['title'] ?></h4>
          <p style="font-size:.88rem;color:var(--text-muted);margin:0;"><?= $step['desc'] ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if (!empty($providers)): ?>
<section class="section-padding" aria-label="Featured providers">
  <div class="container">
    <div class="section-heading" data-aos="fade-up">
      <span class="section-tag"><i class="fas fa-award me-1" style="margin-right:4px;"></i>Verified</span>
      <h2>Featured Training Providers</h2>
      <p>Ministry-approved providers delivering quality professional development programmes.</p>
    </div>

    <div class="row">
      <?php foreach ($providers as $i => $prov): ?>
      <div class="col-lg-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="<?= $i * 80 ?>">
        <div class="provider-card text-center">
          <div class="provider-logo mx-auto">
            <i class="fas fa-building"></i>
          </div>
          <h5 style="font-size:.95rem;font-weight:700;margin-bottom:6px;">
            <?= htmlspecialchars($prov['org_name']) ?>
          </h5>
          <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:12px;">
            <i class="fas fa-book me-1" style="margin-right:4px;"></i>
            <?= $prov['course_count'] ?> Course<?= $prov['course_count'] != 1 ? 's' : '' ?>
          </p>
          <span class="badge-status badge-approved">
            <i class="fas fa-check-circle me-1" style="margin-right:3px;"></i>Verified
          </span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- CTA banner section-->
<section style="background:linear-gradient(135deg,var(--primary),var(--accent));padding:70px 0;"
         data-aos="fade-up" aria-label="Call to action">
  <div class="container text-center text-white">
    <h2 style="font-size:clamp(1.6rem,3vw,2.4rem);font-weight:800;margin-bottom:14px;">
      Ready to Start Your Learning Journey?
    </h2>
    <p style="opacity:.85;max-width:520px;margin:0 auto 32px;font-size:1rem;">
      Join thousands of professionals upgrading their skills through EduSkill Marketplace.
    </p>
    <div class="d-flex justify-content-center flex-wrap" style="gap:14px;">
      <a href="<?= APP_URL ?>/pages/signup.php" style="background:white;color:var(--primary);padding:13px 32px;
         border-radius:8px;font-weight:700;font-size:1rem;transition:all .3s ease;
         display:inline-flex;align-items:center;gap:8px;"
         onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,0.2)'"
         onmouseout="this.style.transform='none';this.style.boxShadow='none'">
        <i class="fas fa-user-plus"></i> Join as Learner
      </a>
      <a href="<?= APP_URL ?>/pages/signup.php?type=provider" style="background:rgba(255,255,255,0.15);color:white;
         padding:13px 32px;border-radius:8px;font-weight:700;font-size:1rem;
         border:2px solid rgba(255,255,255,0.5);transition:all .3s ease;
         display:inline-flex;align-items:center;gap:8px;"
         onmouseover="this.style.background='rgba(255,255,255,0.25)';this.style.transform='translateY(-2px)'"
         onmouseout="this.style.background='rgba(255,255,255,0.15)';this.style.transform='none'">
        <i class="fas fa-building"></i> Register as Provider
      </a>
    </div>
  </div>
</section>

<?php include '../includes/footer.php'; ?>
