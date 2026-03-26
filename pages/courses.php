<?php

require_once '../php/config.php';

$pageTitle = 'Browse Courses';
$activeNav = 'courses';

// Filters
$search   = sanitize($_GET['search'] ?? '');
$category = sanitize($_GET['category'] ?? '');
$sort     = sanitize($_GET['sort'] ?? 'newest');
$minFee   = (float)($_GET['min_fee'] ?? 0);
$maxFee   = (float)($_GET['max_fee'] ?? 99999);

$db = getDbConnection();

// Build query
$where  = ['1=1'];
$params = [];
$types  = '';

if ($search) {
    $where[] = '(c.title LIKE ? OR c.description LIKE ? OR p.org_name LIKE ?)';
    $like = "%$search%";
    array_push($params, $like, $like, $like);
    $types .= 'sss';
}
if ($category) {
    $where[] = 'c.category = ?';
    $params[] = $category;
    $types   .= 's';
}
$where[] = 'c.fee >= ? AND c.fee <= ?';
array_push($params, $minFee, $maxFee);
$types .= 'dd';

$orderMap = [
    'newest'    => 'c.courseID DESC',
    'oldest'    => 'c.courseID ASC',
    'fee_asc'   => 'c.fee ASC',
    'fee_desc'  => 'c.fee DESC',
    'rating'    => 'avg_rating DESC',
    'popular'   => 'enroll_count DESC',
];
$order = $orderMap[$sort] ?? 'c.courseID DESC';

$sql = "
    SELECT c.courseID, c.title, c.description, c.fee, c.startDate, c.category, c.image_path,
           p.org_name,
           COALESCE(AVG(r.rating), 0) AS avg_rating,
           COUNT(DISTINCT e.enrollID) AS enroll_count
    FROM courses c
    JOIN providers p ON c.providerID = p.providerID AND p.status='approved'
    LEFT JOIN reviews r ON c.courseID = r.courseID
    LEFT JOIN enrollments e ON c.courseID = e.courseID AND e.paymentStatus='paid'
    WHERE c.status = 'active' AND " . implode(' AND ', $where) . "
    GROUP BY c.courseID
    ORDER BY $order
";

$stmt = $db->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Categories for filter
$categories = $db->query("SELECT DISTINCT category FROM courses WHERE category IS NOT NULL AND category != '' AND status='active' ORDER BY category")->fetch_all(MYSQLI_ASSOC);
$categories = array_column($categories, 'category');

$db->close();
include '../includes/header.php';
?>

<main>
<!-- Page Header -->
<div class="page-header">
  <div class="container">
    <span class="section-tag mb-2 d-inline-block">Browse</span>
    <h1>All Courses</h1>
    <p style="color:var(--text-muted);margin:0;">
      Discover <?= count($courses) ?> professional development programmes from verified training providers.
    </p>
  </div>
</div>

<section class="section-padding-sm">
  <div class="container">
    <!-- Search & Filter Bar -->
    <div class="card-ems p-4 mb-4" data-aos="fade-down">
      <form method="GET" action="courses.php" id="filterForm">
        <div class="row align-items-end" style="gap:0;">
          <!-- Search -->
          <div class="col-lg-4 col-md-6 mb-3 mb-lg-0">
            <label style="font-size:.8rem;font-weight:600;color:var(--text-muted);display:block;margin-bottom:6px;">
              <i class="fas fa-search me-1" style="margin-right:4px;"></i>Search
            </label>
            <input type="text" name="search" id="courseSearch" class="form-control"
                   placeholder="Course title, keyword..."
                   value="<?= htmlspecialchars($search) ?>"
                   style="border:1.5px solid var(--border);border-radius:8px;padding:9px 14px;" />
          </div>

          <!-- Category -->
          <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
            <label style="font-size:.8rem;font-weight:600;color:var(--text-muted);display:block;margin-bottom:6px;">
              <i class="fas fa-tag me-1" style="margin-right:4px;"></i>Category
            </label>
            <select name="category" id="categoryFilter"
                    style="border:1.5px solid var(--border);border-radius:8px;padding:9px 14px;
                           width:100%;font-size:.92rem;background:white;color:var(--text);">
              <option value="">All Categories</option>
              <?php foreach ($categories as $cat): ?>
              <option value="<?= htmlspecialchars($cat) ?>" <?= $category===$cat?'selected':'' ?>>
                <?= htmlspecialchars($cat) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Sort -->
          <div class="col-lg-3 col-md-6 mb-3 mb-md-0">
            <label style="font-size:.8rem;font-weight:600;color:var(--text-muted);display:block;margin-bottom:6px;">
              <i class="fas fa-sort me-1" style="margin-right:4px;"></i>Sort By
            </label>
            <select name="sort"
                    style="border:1.5px solid var(--border);border-radius:8px;padding:9px 14px;
                           width:100%;font-size:.92rem;background:white;color:var(--text);"
                    onchange="this.form.submit()">
              <option value="newest" <?= $sort==='newest'?'selected':'' ?>>Newest First</option>
              <option value="popular" <?= $sort==='popular'?'selected':'' ?>>Most Popular</option>
              <option value="rating" <?= $sort==='rating'?'selected':'' ?>>Highest Rated</option>
              <option value="fee_asc" <?= $sort==='fee_asc'?'selected':'' ?>>Price: Low to High</option>
              <option value="fee_desc" <?= $sort==='fee_desc'?'selected':'' ?>>Price: High to Low</option>
            </select>
          </div>

          <!-- Submit -->
          <div class="col-lg-2 col-md-6">
            <button type="submit" class="btn-primary-ems w-100" style="padding:10px;justify-content:center;">
              <i class="fas fa-filter"></i> Filter
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- Results Summary -->
    <div class="d-flex align-items-center justify-content-between mb-4">
      <p style="color:var(--text-muted);margin:0;font-size:.9rem;">
        Showing <strong><?= count($courses) ?></strong> course<?= count($courses)!=1?'s':'' ?>
        <?= $search ? ' for <strong>"'.htmlspecialchars($search).'"</strong>' : '' ?>
      </p>
      <?php if ($search || $category): ?>
      <a href="<?= APP_URL ?>/pages/courses.php" style="font-size:.85rem;color:var(--danger);">
        <i class="fas fa-times me-1" style="margin-right:4px;"></i>Clear filters
      </a>
      <?php endif; ?>
    </div>

    <!-- Course Grid -->
    <div class="row" id="courseGrid">
      <?php if (empty($courses)): ?>
      <div class="col-12" id="noCoursesMsg">
        <div style="text-align:center;padding:60px 20px;background:white;
                    border-radius:var(--radius);border:1px solid var(--border);">
          <i class="fas fa-search" style="font-size:3rem;color:var(--border);display:block;margin-bottom:16px;"></i>
          <h4 style="margin-bottom:8px;">No courses found</h4>
          <p style="color:var(--text-muted);margin-bottom:20px;">
            Try adjusting your search or filter criteria.
          </p>
          <a href="<?= APP_URL ?>/pages/courses.php" class="btn-outline-ems">Clear All Filters</a>
        </div>
      </div>
      <?php else: ?>
      <div id="noCoursesMsg" style="display:none;"></div>
      <?php foreach ($courses as $i => $course): ?>
      <div class="col-lg-4 col-md-6 mb-4 course-card-item"
           data-title="<?= htmlspecialchars($course['title']) ?>"
           data-category="<?= htmlspecialchars($course['category'] ?? '') ?>"
           data-aos="fade-up" data-aos-delay="<?= min($i * 60, 400) ?>">
        <article class="card-course">
          <!-- Thumbnail -->
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
            <?php if ($course['fee'] == 0): ?>
            <span style="position:absolute;top:14px;right:14px;z-index:2;background:var(--success);
                         color:white;padding:3px 10px;border-radius:20px;font-size:.73rem;font-weight:700;">
              FREE
            </span>
            <?php endif; ?>
          </div>

          <!-- Body -->
          <div class="course-body">
            <div class="course-meta">
              <i class="fas fa-building" style="margin-right:4px;"></i>
              <?= htmlspecialchars($course['org_name']) ?>
            </div>
            <h3 class="course-title">
              <a href="<?= APP_URL ?>/pages/course-detail.php?id=<?= $course['courseID'] ?>" style="color:var(--text);">
                <?= htmlspecialchars($course['title']) ?>
              </a>
            </h3>
            <p style="font-size:.83rem;color:var(--text-muted);margin-bottom:0;
                      display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
              <?= htmlspecialchars(substr($course['description'], 0, 120)) ?>
            </p>

            <?php if ($course['startDate']): ?>
            <div style="font-size:.78rem;color:var(--text-muted);margin-top:8px;">
              <i class="fas fa-calendar me-1" style="margin-right:3px;"></i>
              Starts <?= date('d M Y', strtotime($course['startDate'])) ?>
            </div>
            <?php endif; ?>

            <div class="course-footer">
              <div>
                <div class="star-rating">
                  <?php for($s=1;$s<=5;$s++): ?>
                  <i class="<?= $s<=round($course['avg_rating'])?'fas':'far' ?> fa-star"></i>
                  <?php endfor; ?>
                </div>
                <span style="font-size:.75rem;color:var(--text-muted);">
                  <?= number_format($course['avg_rating'],1) ?> &bull; <?= $course['enroll_count'] ?> learners
                </span>
              </div>
              <div style="text-align:right;">
                <div style="font-weight:800;color:var(--primary);font-size:1.05rem;">
                  <?= $course['fee']>0 ? 'RM '.number_format($course['fee'],2) : '<span style="color:var(--success);">Free</span>' ?>
                </div>
                <a href="<?= APP_URL ?>/pages/course-detail.php?id=<?= $course['courseID'] ?>"
                   class="btn-primary-ems mt-2" style="padding:6px 14px;font-size:.78rem;">
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
  </div>
</section>
</main>

<?php include '../includes/footer.php'; ?>
