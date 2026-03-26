<?php

require_once '../php/config.php';
$pageTitle = 'Contact Us';
$activeNav = 'contact';
include '../includes/header.php';
?>

<main>
<div class="page-header">
  <div class="container">
    <span class="section-tag mb-2 d-inline-block">Get In Touch</span>
    <h1>Contact Us</h1>
    <p style="color:var(--text-muted);margin:0;">Have questions? We're here to help. Reach out through any channel below.</p>
  </div>
</div>

<section class="section-padding-sm">
  <div class="container">
    <div class="row">
      <!-- Contact Info -->
      <div class="col-lg-4 mb-4 mb-lg-0" data-aos="fade-right">
        <h3 style="font-size:1.2rem;font-weight:700;margin-bottom:24px;">Reach Us Directly</h3>
        <?php
        $contacts = [
          ['icon'=>'fa-phone','color'=>'purple','title'=>'Phone',
           'text'=>'+60 0123456789<br>Mon–Fri, 8:00 AM – 5:00 PM'],
          ['icon'=>'fa-envelope','color'=>'green','title'=>'Email',
           'text'=>'info@eduskill.gov.my<br>support@eduskill.gov.my'],
          ['icon'=>'fa-clock','color'=>'orange','title'=>'Office Hours',
           'text'=>'Monday – Friday<br>8:00 AM – 5:00 PM'],
        ];
        foreach ($contacts as $i => $c): ?>
        <div class="d-flex align-items-start mb-4" style="gap:14px;" data-aos="fade-up" data-aos-delay="<?= $i*80 ?>">
          <div style="width:44px;height:44px;border-radius:10px;flex-shrink:0;
                      display:flex;align-items:center;justify-content:center;
                      <?= $c['color']==='blue'?'background:var(--primary-light);color:var(--primary);':
                         ($c['color']==='purple'?'background:#fdf4ff;color:var(--accent);':
                         ($c['color']==='green'?'background:#ecfdf5;color:var(--success);':
                          'background:#fffbeb;color:var(--warning);')) ?>">
            <i class="fas <?= $c['icon'] ?>"></i>
          </div>
          <div>
            <div style="font-weight:700;margin-bottom:4px;font-size:.9rem;"><?= $c['title'] ?></div>
            <div style="font-size:.85rem;color:var(--text-muted);line-height:1.6;"><?= $c['text'] ?></div>
          </div>
        </div>
        <?php endforeach; ?>

        <!-- Social Links -->
        <div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--border);">
          <div style="font-weight:700;margin-bottom:12px;font-size:.9rem;">Follow Us</div>
          <div style="display:flex;gap:10px;">
            <?php
            $socials = ['fa-facebook-f','fa-twitter','fa-linkedin-in','fa-youtube'];
            foreach ($socials as $icon): ?>
            <a href="#" style="width:38px;height:38px;border:1.5px solid var(--border);border-radius:8px;
               display:flex;align-items:center;justify-content:center;color:var(--text-muted);
               transition:all .3s;font-size:.85rem;"
               onmouseover="this.style.background='var(--primary)';this.style.color='white';this.style.borderColor='var(--primary)'"
               onmouseout="this.style.background='transparent';this.style.color='var(--text-muted)';this.style.borderColor='var(--border)'">
              <i class="fab <?= $icon ?>"></i>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Contact Form -->
      <div class="col-lg-8" data-aos="fade-left">
        <div class="card-ems p-4 p-md-5">
          <h3 style="font-size:1.2rem;font-weight:700;margin-bottom:6px;">Send Us a Message</h3>
          <p style="color:var(--text-muted);font-size:.88rem;margin-bottom:24px;">
            We'll respond within 1–2 business days.
          </p>

          <div id="contactSuccess" style="display:none;" class="alert-ems alert-success-ems">
            <i class="fas fa-check-circle"></i>
            <span>Thank you! Your message has been sent. We'll be in touch soon.</span>
          </div>

          <form id="contactForm" class="form-ems" novalidate>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="contactName">
                    <i class="fas fa-user me-1" style="color:var(--primary);margin-right:4px;"></i>Full Name *
                  </label>
                  <input type="text" id="contactName" class="form-control" placeholder="Your full name" required />
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="contactEmail">
                    <i class="fas fa-envelope me-1" style="color:var(--primary);margin-right:4px;"></i>Email Address *
                  </label>
                  <input type="email" id="contactEmail" class="form-control" placeholder="you@example.com" required />
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="contactPhone">
                    <i class="fas fa-phone me-1" style="color:var(--primary);margin-right:4px;"></i>Phone Number
                  </label>
                  <input type="tel" id="contactPhone" class="form-control" placeholder="Optional" />
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="contactSubject">
                    <i class="fas fa-tag me-1" style="color:var(--primary);margin-right:4px;"></i>Subject
                  </label>
                  <select id="contactSubject" class="form-control">
                    <option value="">Select a topic</option>
                    <option>General Enquiry</option>
                    <option>Course Information</option>
                    <option>Provider Registration</option>
                    <option>Technical Support</option>
                    <option>Complaint / Feedback</option>
                    <option>Other</option>
                  </select>
                </div>
              </div>
              <div class="col-12">
                <div class="form-group">
                  <label for="contactMessage">
                    <i class="fas fa-comment me-1" style="color:var(--primary);margin-right:4px;"></i>Message *
                  </label>
                  <textarea id="contactMessage" class="form-control" rows="5"
                            placeholder="Describe your enquiry in detail (min 20 characters)" required></textarea>
                </div>
              </div>
            </div>

            <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;">
              <input type="checkbox" id="agreePrivacy" style="width:16px;height:16px;" required />
              <label for="agreePrivacy" style="font-size:.85rem;color:var(--text-muted);cursor:pointer;margin:0;">
                I agree to the <a href="#" style="color:var(--primary);">Privacy Policy</a> and consent to being contacted.
              </label>
            </div>

            <button type="submit" class="btn-primary-ems" style="padding:12px 32px;font-size:1rem;">
              <i class="fas fa-paper-plane"></i> Send Message
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
</main>

<?php include '../includes/footer.php'; ?>
