<?php

require_once '../php/config.php';
$pageTitle = 'FAQ';
$activeNav = 'faq';
include '../includes/header.php';
?>

<main>

<div class="page-header" style="padding:70px 0 60px;">
  <div class="container text-center">
    <span class="section-tag mb-2 d-inline-block">Help Centre</span>
    <h1 style="font-size:clamp(1.8rem,3vw,2.8rem);">Frequently Asked Questions</h1>
    <p style="color:var(--text-muted);max-width:580px;margin:16px auto 0;font-size:1rem;">
      Everything you need to know about EduSkill Marketplace — from provider registration
      to learner enrolment, payments, and reporting.
    </p>
    <!-- Search bar -->
    <div style="max-width:480px;margin:28px auto 0;position:relative;" data-aos="fade-up" data-aos-delay="100">
      <input type="text" id="faqSearch" placeholder="Search questions..."
             style="width:100%;padding:13px 48px 13px 18px;border:1.5px solid var(--border);
                    border-radius:50px;font-size:.95rem;outline:none;background:white;color:var(--text);
                    box-shadow:0 4px 16px rgba(37,99,235,.08);transition:border-color .2s;"
             onfocus="this.style.borderColor='var(--primary)'"
             onblur="this.style.borderColor='var(--border)'" />
      <i class="fas fa-search" style="position:absolute;right:18px;top:50%;transform:translateY(-50%);
         color:var(--text-muted);pointer-events:none;"></i>
    </div>
  </div>
</div>

<section class="section-padding">
  <div class="container">

    <!-- Category Tabs -->
    <div style="display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin-bottom:44px;" data-aos="fade-up">
      <?php
      $cats = ['All','Learners','Training Providers','Payments','Platform & Technical'];
      foreach ($cats as $i => $cat): ?>
      <button class="faq-cat-btn <?= $i===0?'active':'' ?>" data-cat="<?= $i===0?'all':strtolower(str_replace([' ','&'],['_','and'],$cat)) ?>">
        <?= $cat ?>
      </button>
      <?php endforeach; ?>
    </div>

    <?php
    $faqs = [
      /* ---- LEARNERS ---- */
      [
        'cat'  => 'learners',
        'icon' => 'fa-user-graduate',
        'color'=> 'blue',
        'q'    => 'How do I register as a learner?',
        'a'    => 'Click <strong>Sign Up</strong> on the top navigation and select <em>Learner</em> as your account type. Fill in your name, email, and password, then verify your email address. Once verified you can browse and enrol in any available course immediately.'
      ],
      [
        'cat'  => 'learners',
        'icon' => 'fa-book-open',
        'color'=> 'blue',
        'q'    => 'What is the process for enrolling in a course?',
        'a'    => 'Browse courses from the <strong>Courses</strong> page, click on a course to view details, then click <strong>Enrol Now</strong>. You will be directed to the payment page. Once payment is confirmed, the course appears in your Learner Dashboard under <em>My Enrolments</em>.'
      ],
      [
        'cat'  => 'learners',
        'icon' => 'fa-star',
        'color'=> 'blue',
        'q'    => 'How do course ratings and reviews work?',
        'a'    => 'After enrolling and completing a course, you can leave a star rating (1–5) and a written review from your <strong>Learner Dashboard</strong>. Reviews are visible to all users on the course detail page and help future learners make informed decisions.'
      ],
      [
        'cat'  => 'learners',
        'icon' => 'fa-certificate',
        'color'=> 'blue',
        'q'    => 'Will I receive a certificate after completing a course?',
        'a'    => 'Certificates are issued by the training provider upon successful completion. Contact your provider directly or check the course details page for specific completion criteria and certificate information.'
      ],
      /* ---- PROVIDERS ---- */
      [
        'cat'  => 'training_providers',
        'icon' => 'fa-building',
        'color'=> 'purple',
        'q'    => 'How do I register as a training provider?',
        'a'    => 'Select <em>Training Provider</em> during Sign Up and complete your organisation profile, including your Ministry of Human Resources accreditation details. Your application will be reviewed by an EMS Officer. You will receive an email notification once your account is approved — this typically takes 1–3 business days.'
      ],
      [
        'cat'  => 'training_providers',
        'icon' => 'fa-chart-bar',
        'color'=> 'purple',
        'q'    => 'Can providers track their course performance?',
        'a'    => 'Yes. Your <strong>Provider Dashboard</strong> shows real-time enrolment counts, learner ratings, and revenue figures per course. The <strong>Analytics</strong> page displays monthly and yearly enrolment trends, rating distribution charts, and a top-courses comparison by views vs enrolments.'
      ],
      [
        'cat'  => 'training_providers',
        'icon' => 'fa-plus-circle',
        'color'=> 'purple',
        'q'    => 'How do I add or edit a course?',
        'a'    => 'From your Provider Dashboard, click <strong>Add New Course</strong>. Fill in the course title, description, category, duration, fees, and upload a course image. Existing courses can be edited or set to inactive from the same dashboard at any time.'
      ],
      [
        'cat'  => 'training_providers',
        'icon' => 'fa-users',
        'color'=> 'purple',
        'q'    => 'How do I view who has enrolled in my courses?',
        'a'    => 'Navigate to your <strong>Provider Dashboard</strong> and click on any course listing. The enrolled learners section shows learner names, enrolment dates, and payment status for each participant.'
      ],
      /* ---- PAYMENTS ---- */
      [
        'cat'  => 'payments',
        'icon' => 'fa-receipt',
        'color'=> 'green',
        'q'    => 'Will I receive a receipt after making a payment?',
        'a'    => 'Yes. A payment receipt is automatically generated after every successful transaction. You can view and download it from your <strong>Learner Dashboard → My Enrolments → View Receipt</strong>. The receipt includes your name, course title, provider, amount paid, and transaction reference.'
      ],
      [
        'cat'  => 'payments',
        'icon' => 'fa-credit-card',
        'color'=> 'green',
        'q'    => 'What payment methods are accepted?',
        'a'    => 'EduSkill Marketplace currently supports online bank transfer and major credit/debit cards processed through our secure payment gateway. All transactions are encrypted and processed in Malaysian Ringgit (MYR).'
      ],
      [
        'cat'  => 'payments',
        'icon' => 'fa-undo',
        'color'=> 'green',
        'q'    => 'What is the refund policy?',
        'a'    => 'Refund policies are set by individual training providers. Please review the refund terms on the course detail page before enrolling. For disputes, contact the provider directly or reach out to EMS support at <a href="mailto:support@eduskill.gov.my" style="color:var(--primary);">support@eduskill.gov.my</a>.'
      ],
      /* ---- PLATFORM ---- */
      [
        'cat'  => 'platform_and_technical',
        'icon' => 'fa-lock',
        'color'=> 'orange',
        'q'    => 'Is my personal data secure on EduSkill Marketplace?',
        'a'    => 'Yes. EduSkill Marketplace complies with Malaysia\'s Personal Data Protection Act (PDPA). All user data is encrypted in transit and at rest. We do not share personal information with third parties without your consent.'
      ],
      [
        'cat'  => 'platform_and_technical',
        'icon' => 'fa-mobile-alt',
        'color'=> 'orange',
        'q'    => 'Is the platform accessible on mobile devices?',
        'a'    => 'Absolutely. EduSkill Marketplace is fully responsive and works on smartphones, tablets, and desktop browsers. No app installation is required — simply visit the site from any modern browser.'
      ],
      [
        'cat'  => 'platform_and_technical',
        'icon' => 'fa-headset',
        'color'=> 'orange',
        'q'    => 'How do I contact technical support?',
        'a'    => 'Email us at <a href="mailto:support@eduskill.gov.my" style="color:var(--primary);">support@eduskill.gov.my</a> or use the <a href="' . APP_URL . '/pages/contact.php" style="color:var(--primary);">Contact Us</a> form. Our technical team responds within 1–2 business days, Monday to Friday 8:00 AM – 5:00 PM.'
      ],
    ];

    // Group by category
    $grouped = [];
    foreach ($faqs as $faq) {
        $grouped[$faq['cat']][] = $faq;
    }
    ?>

    <!-- All FAQs (default view) -->
    <div id="faqContainer">
      <?php $idx = 0; foreach ($faqs as $faq): $idx++; ?>
      <div class="faq-item" data-cat="<?= $faq['cat'] ?>" data-aos="fade-up" data-aos-delay="<?= min($idx * 40, 300) ?>">
        <button class="faq-question" onclick="toggleFaq(this)" aria-expanded="false">
          <span style="display:flex;align-items:center;gap:12px;">
            <span class="faq-icon-wrap faq-color-<?= $faq['color'] ?>">
              <i class="fas <?= $faq['icon'] ?>"></i>
            </span>
            <span><?= $faq['q'] ?></span>
          </span>
          <i class="fas fa-chevron-down faq-chevron"></i>
        </button>
        <div class="faq-answer">
          <div class="faq-answer-inner">
            <?= $faq['a'] ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- No results message -->
    <div id="faqNoResults" style="display:none;text-align:center;padding:60px 20px;color:var(--text-muted);" data-aos="fade-up">
      <i class="fas fa-search" style="font-size:3rem;color:var(--border);display:block;margin-bottom:16px;"></i>
      <p style="font-size:1rem;margin:0;">No questions matched your search. Try different keywords.</p>
    </div>

  </div>
</section>

<section class="section-padding" style="background:var(--bg-alt);">
  <div class="container">
    <div class="row align-items-center">

      <!-- Left: Stats -->
      <div class="col-lg-5 mb-5 mb-lg-0" data-aos="fade-right">
        <span class="section-tag mb-2 d-inline-block">Why EduSkill?</span>
        <h2 style="margin-bottom:16px;">Trusted by Thousands of Malaysians</h2>
        <p style="color:var(--text-muted);line-height:1.8;margin-bottom:28px;">
          EduSkill Marketplace is the Ministry of Human Resources' official platform for
          professional upskilling — connecting learners with accredited providers nationwide.
        </p>
        <div class="row">
          <?php
          $stats = [
            ['icon'=>'fa-user-graduate','color'=>'blue', 'num'=>'10,000+','label'=>'Learners Enrolled'],
            ['icon'=>'fa-building',     'color'=>'purple','num'=>'50+',    'label'=>'Verified Providers'],
            ['icon'=>'fa-book',         'color'=>'green', 'num'=>'500+',   'label'=>'Courses Available'],
            ['icon'=>'fa-star',         'color'=>'orange','num'=>'4.5/5',  'label'=>'Avg Course Rating'],
          ];
          foreach ($stats as $i => $s): ?>
          <div class="col-6 mb-3" data-aos="fade-up" data-aos-delay="<?= $i*60 ?>">
            <div class="feature-card" style="padding:18px 16px;display:flex;align-items:center;gap:12px;">
              <div class="feature-icon" style="width:40px;height:40px;flex-shrink:0;
                <?= $s['color']==='purple'?'background:#fdf4ff;color:var(--accent);':
                   ($s['color']==='green' ?'background:#ecfdf5;color:var(--success);':
                   ($s['color']==='orange'?'background:#fffbeb;color:var(--warning);':'')) ?>">
                <i class="fas <?= $s['icon'] ?>" style="font-size:.9rem;"></i>
              </div>
              <div>
                <div style="font-size:1.2rem;font-weight:800;color:var(--primary);line-height:1;"><?= $s['num'] ?></div>
                <div style="font-size:.75rem;color:var(--text-muted);margin-top:2px;"><?= $s['label'] ?></div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Right: Contact card -->
      <div class="col-lg-6 offset-lg-1" data-aos="fade-left">
        <div class="card-ems p-4 p-md-5">
          <div style="text-align:center;margin-bottom:28px;">
            <div style="width:64px;height:64px;border-radius:16px;background:var(--primary-light);
                        color:var(--primary);display:flex;align-items:center;justify-content:center;
                        font-size:1.6rem;margin:0 auto 16px;">
              <i class="fas fa-comments"></i>
            </div>
            <h3 style="font-size:1.2rem;font-weight:700;margin-bottom:6px;">Still have questions?</h3>
            <p style="color:var(--text-muted);font-size:.88rem;margin:0;">
              Our support team is ready to help you — Monday to Friday, 8:00 AM – 5:00 PM.
            </p>
          </div>

          <div style="display:flex;flex-direction:column;gap:12px;">
            <a href="<?= APP_URL ?>/pages/contact.php" class="btn-primary-ems"
               style="display:flex;align-items:center;justify-content:center;gap:10px;padding:14px;font-size:.95rem;text-decoration:none;">
              <i class="fas fa-paper-plane"></i> Send Us a Message
            </a>
            <a href="mailto:support@eduskill.gov.my"
               style="display:flex;align-items:center;justify-content:center;gap:10px;padding:13px;
                      border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:.9rem;
                      color:var(--text);font-weight:600;text-decoration:none;transition:all .2s;"
               onmouseover="this.style.borderColor='var(--primary)';this.style.color='var(--primary)'"
               onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text)'">
              <i class="fas fa-envelope" style="color:var(--primary);"></i> support@eduskill.gov.my
            </a>
            <a href="tel:+60380000000"
               style="display:flex;align-items:center;justify-content:center;gap:10px;padding:13px;
                      border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:.9rem;
                      color:var(--text);font-weight:600;text-decoration:none;transition:all .2s;"
               onmouseover="this.style.borderColor='var(--primary)';this.style.color='var(--primary)'"
               onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text)'">
              <i class="fas fa-phone" style="color:var(--primary);"></i> +60 0123456789
            </a>
          </div>

          <div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--border);
                      display:flex;align-items:center;gap:10px;justify-content:center;">
            <i class="fas fa-shield-alt" style="color:var(--success);font-size:.85rem;"></i>
            <span style="font-size:.8rem;color:var(--text-muted);">Your data is protected under Malaysia's PDPA</span>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

</main>

<?php include '../includes/footer.php'; ?>

<style>
/* ---- FAQ Category Buttons ---- */
.faq-cat-btn {
  padding: 8px 20px;
  border: 1.5px solid var(--border);
  border-radius: 50px;
  background: white;
  color: var(--text-muted);
  font-size: .85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all .2s;
  font-family: inherit;
}
.faq-cat-btn:hover {
  border-color: var(--primary);
  color: var(--primary);
}
.faq-cat-btn.active {
  background: var(--primary);
  border-color: var(--primary);
  color: white;
}

/* ---- FAQ Accordion ---- */
.faq-item {
  border: 1.5px solid var(--border);
  border-radius: var(--radius-sm);
  margin-bottom: 10px;
  background: white;
  overflow: hidden;
  transition: box-shadow .2s, border-color .2s;
}
.faq-item:hover {
  border-color: var(--primary);
  box-shadow: 0 4px 20px rgba(37,99,235,.08);
}
.faq-item.open {
  border-color: var(--primary);
  box-shadow: 0 4px 20px rgba(37,99,235,.10);
}

.faq-question {
  width: 100%;
  padding: 18px 20px;
  background: none;
  border: none;
  border-bottom: none;
  outline: none;
  box-shadow: none;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  cursor: pointer;
  font-size: .95rem;
  font-weight: 600;
  color: var(--text);
  text-align: left;
  font-family: inherit;
  transition: color .2s;
  -webkit-appearance: none;
  appearance: none;
}
.faq-question:focus {
  outline: none;
  box-shadow: none;
  border: none;
}
.faq-item.open .faq-question {
  color: var(--primary);
}

.faq-icon-wrap {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: .8rem;
}
.faq-color-blue   { background: var(--primary-light); color: var(--primary); }
.faq-color-purple { background: #fdf4ff; color: var(--accent); }
.faq-color-green  { background: #ecfdf5; color: var(--success); }
.faq-color-orange { background: #fffbeb; color: var(--warning); }

.faq-chevron {
  font-size: .75rem;
  color: var(--text-muted);
  flex-shrink: 0;
  transition: transform .3s ease;
}
.faq-item.open .faq-chevron {
  transform: rotate(180deg);
  color: var(--primary);
}

.faq-answer {
  max-height: 0;
  overflow: hidden;
  transition: max-height .35s ease;
}
.faq-item.open .faq-answer {
  max-height: 400px;
}
.faq-answer-inner {
  padding: 4px 20px 18px 66px;
  font-size: .88rem;
  color: var(--text-muted);
  line-height: 1.8;
}

/* Hide filtered items */
.faq-item.hidden { display: none; }
</style>

<script>
// ---> Toggle FAQ item open/closed state
function toggleFaq(btn) {
  var item = btn.closest('.faq-item');
  var isOpen = item.classList.contains('open');

  // ---> Close all other open FAQ items
  document.querySelectorAll('.faq-item.open').forEach(function(el) {
    el.classList.remove('open');
    el.querySelector('.faq-question').setAttribute('aria-expanded', 'false');
  });

  // ---> Open selected FAQ item if not already open
  if (!isOpen) {
    item.classList.add('open');
    btn.setAttribute('aria-expanded', 'true');
  }
}

// ---> Handle FAQ category filter button clicks
document.querySelectorAll('.faq-cat-btn').forEach(function(btn) {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.faq-cat-btn').forEach(function(b) { b.classList.remove('active'); });
    this.classList.add('active');

    var cat = this.dataset.cat;
    var items = document.querySelectorAll('.faq-item');
    var visible = 0;

    items.forEach(function(item) {
      var match = cat === 'all' || item.dataset.cat === cat;
      item.classList.toggle('hidden', !match);
      if (match) visible++;
    });

    document.getElementById('faqNoResults').style.display = visible === 0 ? 'block' : 'none';
    // ---> Clear search input when switching categories
    document.getElementById('faqSearch').value = '';
  });
});

// ---> Handle FAQ search functionality
document.getElementById('faqSearch').addEventListener('input', function() {
  var q = this.value.toLowerCase().trim();
  var items = document.querySelectorAll('.faq-item');
  var visible = 0;

  // ---> Reset all category filter buttons
  document.querySelectorAll('.faq-cat-btn').forEach(function(b) { b.classList.remove('active'); });
  document.querySelector('.faq-cat-btn[data-cat="all"]').classList.add('active');

  items.forEach(function(item) {
    var text = item.innerText.toLowerCase();
    var match = !q || text.indexOf(q) !== -1;
    item.classList.toggle('hidden', !match);
    if (match) visible++;
  });

  document.getElementById('faqNoResults').style.display = visible === 0 ? 'block' : 'none';
});
</script>