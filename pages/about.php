<?php

require_once '../php/config.php';
$pageTitle = 'About Us';
$activeNav = 'about';

// Fetch platform statistics from database
$db = getDbConnection();
$statsQuery = $db->query("SELECT
    (SELECT COUNT(*) FROM courses) AS total_courses,
    (SELECT COUNT(*) FROM providers WHERE status='approved') AS total_providers,
    (SELECT COUNT(*) FROM users WHERE role='learner') AS total_learners,
    ROUND(COALESCE(AVG(r.rating), 4.5), 1) AS avg_rating
    FROM reviews r
");
$stats = $statsQuery->fetch_assoc();
$db->close();

include '../includes/header.php';
?>

<main>
<!-- Hero -->
<div class="page-header" style="padding:70px 0 60px;">
  <div class="container text-center">
    <span class="section-tag mb-2 d-inline-block">Who We Are</span>
    <h1 style="font-size:clamp(1.8rem,3vw,2.8rem);">About EduSkill Marketplace</h1>
    <p style="color:var(--text-muted);max-width:580px;margin:0 auto;font-size:1rem;">
      An official Ministry of Human Resources initiative connecting Malaysian learners with
      accredited training providers for professional development.
    </p>
  </div>
</div>

<section class="section-padding">
  <div class="container">
    <!-- Mission & Vision -->
    <div class="row align-items-center mb-5">
      <div class="col-lg-6 mb-4 mb-lg-0" data-aos="fade-right">
        <span class="section-tag mb-2 d-inline-block">Our Purpose</span>
        <h2 style="margin-bottom:16px;">Empowering Malaysia's Workforce</h2>
        <p style="color:var(--text-muted);line-height:1.8;">
          EduSkill Marketplace System(EMS) was established by the Ministry of Human Resources to bridge the
          gap between skilled training providers and motivated learners across Malaysia. We believe in
          lifelong learning as the foundation of national competitiveness.
        </p>
        <p style="color:var(--text-muted);line-height:1.8;">
          Through rigorous vetting of training providers and streamlined enrolment processes, we ensure
          quality professional development is accessible to every Malaysian, regardless of background.
        </p>
        <div class="row mt-4">
          <?php
          $vms = [
            ['icon'=>'fa-bullseye','color'=>'blue','title'=>'Our Mission','text'=>'Provide accessible, quality-assured professional training to all Malaysians.'],
            ['icon'=>'fa-eye','color'=>'purple','title'=>'Our Vision','text'=>'A fully-skilled Malaysian workforce ready for the digital economy.'],
          ];
          foreach ($vms as $vm): ?>
          <div class="col-md-6 mb-3">
            <div class="feature-card" style="padding:20px;">
              <div class="feature-icon <?= $vm['color']==='purple'?'style="background:#fdf4ff;color:var(--accent);"':'' ?>">
                <i class="fas <?= $vm['icon'] ?>"></i>
              </div>
              <h5 style="font-size:.95rem;font-weight:700;margin-bottom:6px;"><?= $vm['title'] ?></h5>
              <p style="font-size:.85rem;color:var(--text-muted);margin:0;"><?= $vm['text'] ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="col-lg-6" data-aos="fade-left">
        <div style="background:linear-gradient(135deg,var(--primary-light),#ede9fe);
                    border-radius:20px;padding:50px;text-align:center;">
<<<<<<< HEAD
        
<img src="../assets/images/edu.png" alt="EduSkill Logo" 
     style="width:170px;display:block;margin:0 auto;" />

          <div class="row" style="margin-top:15px;">
=======
          <img src="../assets/images/edu.png" alt="EduSkill Logo" 
     style="width:120px;margin-bottom:20px;" />
          <div class="row">
>>>>>>> backend
            <?php
            $aStats = [
              ['num'=>number_format($stats['total_courses']),'label'=>'Courses Listed'],
              ['num'=>number_format($stats['total_providers']),'label'=>'Verified Providers'],
              ['num'=>number_format($stats['total_learners']),'label'=>'Learners Enrolled'],
              ['num'=>$stats['avg_rating'].'/5','label'=>'Avg Satisfaction'],
            ];
            foreach ($aStats as $as): ?>
            <div class="col-6 mb-3">
              <div style="background:white;border-radius:10px;padding:16px;box-shadow:0 4px 12px rgba(0,0,0,.08);">
                <div style="font-size:1.5rem;font-weight:800;color:var(--primary);"><?= $as['num'] ?></div>
                <div style="font-size:.78rem;color:var(--text-muted);"><?= $as['label'] ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Core Values -->
    <div class="section-heading" data-aos="fade-up">
      <span class="section-tag">Our Values</span>
      <h2>What We Stand For</h2>
    </div>
    <div class="row mb-5">
      <?php
      $values = [
        ['icon'=>'fa-shield-alt','title'=>'Integrity','desc'=>'All providers undergo rigorous Ministry vetting before listing courses on our platform.'],
        ['icon'=>'fa-star','title'=>'Excellence','desc'=>'We maintain high standards for course quality through learner reviews and periodic audits.'],
        ['icon'=>'fa-universal-access','title'=>'Accessibility','desc'=>'Making quality professional training affordable and reachable for every Malaysian.'],
        ['icon'=>'fa-handshake','title'=>'Partnership','desc'=>'Collaborating with industry, academia, and government to serve our learners better.'],
      ];
      foreach ($values as $i => $v): ?>
      <div class="col-md-6 col-lg-3 mb-4" data-aos="fade-up" data-aos-delay="<?= $i*80 ?>">
        <div class="feature-card text-center" style="padding:32px 24px;">
          <div class="feature-icon mx-auto" style="<?= $i%2===1?'background:#fdf4ff;color:var(--accent);':'' ?>">
            <i class="fas <?= $v['icon'] ?>"></i>
          </div>
          <h4 style="font-size:1rem;font-weight:700;margin-bottom:8px;"><?= $v['title'] ?></h4>
          <p style="font-size:.85rem;color:var(--text-muted);margin:0;"><?= $v['desc'] ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Team -->
    <div class="section-heading" data-aos="fade-up">
      <span class="section-tag">Our Team</span>
      <h2>Meet the EduSkill Team</h2>
      <p>Dedicated professionals committed to transforming Malaysia's learning landscape.</p>
    </div>
    <div class="row">
      <?php
      $team = [
        ['name'=>'Sri Ahmad Doe','role'=>'Director General','dept'=>'Ministry of Human Resources','icon'=>'fa-user-tie'],
        ['name'=>'Dr. Faridah Carter','role'=>'Head of Quality Assurance','dept'=>'Provider Standards Division','icon'=>'fa-user-shield'],
        ['name'=>'Mr. Tan Lee','role'=>'Platform Director','dept'=>'Digital Transformation Unit','icon'=>'fa-user-cog'],
        ['name'=>'Puan Martinez','role'=>'Learner Relations Manager','dept'=>'Stakeholder Engagement','icon'=>'fa-user-friends'],
      ];
      foreach ($team as $i => $member): ?>
      <div class="col-md-6 col-lg-3 mb-4" data-aos="fade-up" data-aos-delay="<?= $i*80 ?>">
        <div class="provider-card text-center">
          <div style="width:72px;height:72px;border-radius:50%;margin:0 auto 16px;
                      background:linear-gradient(135deg,var(--primary-light),#ede9fe);
                      display:flex;align-items:center;justify-content:center;font-size:1.8rem;color:var(--primary);">
            <i class="fas <?= $member['icon'] ?>"></i>
          </div>
          <h5 style="font-size:.9rem;font-weight:700;margin-bottom:3px;"><?= $member['name'] ?></h5>
          <div style="font-size:.82rem;color:var(--primary);font-weight:600;margin-bottom:2px;"><?= $member['role'] ?></div>
          <div style="font-size:.78rem;color:var(--text-muted);"><?= $member['dept'] ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
</main>

<?php include '../includes/footer.php'; ?>
