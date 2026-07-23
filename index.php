<?php
$page_title = "Welcome";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Fetch quick stats
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0 AND is_blocked = 0")->fetchColumn();
$total_skills = $pdo->query("SELECT COUNT(*) FROM skills")->fetchColumn();
$completed_sessions = $pdo->query("SELECT COUNT(*) FROM sessions WHERE status = 'completed'")->fetchColumn();

// Fetch 3 featured mentors
$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.profile_photo, u.college, u.department, u.bio, u.points,
           (SELECT AVG(rating) FROM reviews WHERE reviewed_user_id = u.id) as avg_rating
    FROM users u 
    WHERE u.is_admin = 0 AND u.is_blocked = 0 
    ORDER BY u.points DESC LIMIT 3
");
$stmt->execute();
$featured_mentors = $stmt->fetchAll();

// Fetch categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC LIMIT 8")->fetchAll();
?>

<!-- Hero Section -->
<div class="hero-section mb-5">
    <div class="container text-center text-lg-start">
        <div class="row align-items-center">
            <div class="col-lg-7 mb-4 mb-lg-0">
                <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 rounded-pill mb-3">
                    <i class="fas fa-sparkles me-1"></i> Peer-to-Peer Peer Skill Platform
                </span>
                <h1 class="display-4 fw-extrabold mb-3">Teach What You Know.<br><span class="text-primary-gradient">Learn What You Love.</span></h1>
                <p class="lead text-light-emphasis mb-4 pe-lg-5">
                    Connect with fellow college students to exchange valuable skills like Programming, Graphic Design, Video Editing, and Languages — 100% free.
                </p>
                <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                    <a href="/KEH/find-mentor.php" class="btn btn-primary btn-lg px-4 rounded-pill shadow-sm">
                        <i class="fas fa-search me-2"></i> Find a Mentor
                    </a>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a href="/KEH/register.php" class="btn btn-outline-light btn-lg px-4 rounded-pill">
                            Join Platform Free
                        </a>
                    <?php else: ?>
                        <a href="/KEH/dashboard.php" class="btn btn-outline-light btn-lg px-4 rounded-pill">
                            Go to Dashboard
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-5 text-center">
                <div class="p-4 bg-white bg-opacity-10 rounded-4 border border-light border-opacity-25 shadow-lg backdrop-blur">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-3 bg-dark bg-opacity-50 rounded-3 border border-secondary border-opacity-25">
                                <h2 class="fw-bold text-warning mb-0"><?=$total_users?>+</h2>
                                <span class="small text-light">Student Mentors</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-dark bg-opacity-50 rounded-3 border border-secondary border-opacity-25">
                                <h2 class="fw-bold text-info mb-0"><?=$total_skills?>+</h2>
                                <span class="small text-light">Skills Offered</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-3 bg-dark bg-opacity-50 rounded-3 border border-secondary border-opacity-25">
                                <h2 class="fw-bold text-success mb-0"><?=$completed_sessions?>+</h2>
                                <span class="small text-light">Sessions Completed</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5">
    <!-- How It Works Section -->
    <div class="text-center mb-5">
        <h6 class="text-primary fw-bold text-uppercase">Simple Process</h6>
        <h2 class="fw-bold">How Knowledge Exchange Hub Works</h2>
    </div>
    
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="keh-card text-center p-4 h-100">
                <div class="brand-icon mx-auto mb-3 bg-primary text-white fs-4 rounded-circle" style="width:54px; height:54px;">1</div>
                <h5 class="fw-bold">Create Profile</h5>
                <p class="text-muted small">List the skills you can teach and the topics you want to learn.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="keh-card text-center p-4 h-100">
                <div class="brand-icon mx-auto mb-3 bg-info text-white fs-4 rounded-circle" style="width:54px; height:54px;">2</div>
                <h5 class="fw-bold">Find Mentor</h5>
                <p class="text-muted small">Search students by skill, rating, or proficiency level and send a request.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="keh-card text-center p-4 h-100">
                <div class="brand-icon mx-auto mb-3 bg-purple text-white fs-4 rounded-circle" style="width:54px; height:54px;">3</div>
                <h5 class="fw-bold">Chat &amp; Video Call</h5>
                <p class="text-muted small">Chat with your mentor and join 1-on-1 WebRTC video learning sessions.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="keh-card text-center p-4 h-100">
                <div class="brand-icon mx-auto mb-3 bg-success text-white fs-4 rounded-circle" style="width:54px; height:54px;">4</div>
                <h5 class="fw-bold">Review &amp; Earn</h5>
                <p class="text-muted small">Confirm completion, leave reviews, and climb the student leaderboard!</p>
            </div>
        </div>
    </div>

    <!-- Featured Mentors -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">Top Rated Student Mentors</h3>
            <p class="text-muted small mb-0">Learn from high-ranking student tutors across colleges.</p>
        </div>
        <a href="/KEH/find-mentor.php" class="btn btn-outline-primary rounded-pill">View All Mentors <i class="fas fa-arrow-right ms-1"></i></a>
    </div>

    <div class="row g-4 mb-5">
        <?php foreach ($featured_mentors as $m): ?>
            <?php 
                $rating = $m['avg_rating'] ? round((float)$m['avg_rating'], 1) : 5.0;
                // Fetch taught skills
                $sk_stmt = $pdo->prepare("SELECT s.skill_name, us.proficiency_level FROM user_skills us JOIN skills s ON us.skill_id = s.id WHERE us.user_id = ? AND us.skill_type = 'teach'");
                $sk_stmt->execute([$m['id']]);
                $tsk = $sk_stmt->fetchAll();
            ?>
            <div class="col-md-4">
                <div class="keh-card p-4 h-100 d-flex flex-column">
                    <div class="d-flex align-items-center mb-3">
                        <img src="/KEH/uploads/profile_photos/<?= e($m['profile_photo']) ?>" 
                             onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($m['name']) ?>&background=6366f1&color=fff';"
                             alt="<?= e($m['name']) ?>" class="mentor-avatar me-3">
                        <div>
                            <h5 class="fw-bold mb-1"><?= e($m['name']) ?></h5>
                            <p class="text-muted small mb-0"><i class="fas fa-university text-primary me-1"></i> <?= e($m['college']) ?></p>
                        </div>
                    </div>
                    <p class="text-secondary small mb-3 flex-grow-1"><?= e(mb_strimwidth($m['bio'] ?? '', 0, 110, '...')) ?></p>
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold mb-1">Teaches:</label>
                        <div>
                            <?php foreach ($tsk as $sk): ?>
                                <span class="skill-chip badge-prof-advanced me-1 mb-1"><?= e($sk['skill_name']) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-3 border-top mt-auto">
                        <span class="small font-weight-bold text-warning"><i class="fas fa-star me-1"></i> <?= $rating ?> / 5</span>
                        <a href="/KEH/mentor-details.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-primary rounded-pill px-3">Connect</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Skill Categories Grid -->
    <div class="text-center mb-4">
        <h3 class="fw-bold mb-1">Popular Skill Categories</h3>
        <p class="text-muted small">Explore topics available for peer tutoring.</p>
    </div>
    
    <div class="row g-3">
        <?php foreach ($categories as $cat): ?>
            <div class="col-6 col-md-3">
                <a href="/KEH/find-mentor.php?category_id=<?= $cat['id'] ?>" class="text-decoration-none">
                    <div class="keh-card p-3 text-center h-100 hover-border-primary">
                        <div class="text-primary mb-2 fs-4"><i class="fas fa-layer-group"></i></div>
                        <h6 class="fw-bold text-dark mb-0"><?= e($cat['category_name']) ?></h6>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
