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

<style>
    /* ---- Scroll reveal ---- */
    .reveal {
        opacity: 0;
        transform: translateY(24px);
        transition: opacity 0.6s ease, transform 0.6s ease;
    }
    .reveal.is-visible {
        opacity: 1;
        transform: translateY(0);
    }

    /* ---- Hero polish ---- */
    .hero-badge-pulse {
        position: relative;
    }
    .hero-badge-pulse::before {
        content: "";
        position: absolute;
        left: 10px;
        top: 50%;
        width: 6px;
        height: 6px;
        margin-top: -3px;
        border-radius: 50%;
        background: currentColor;
        animation: pulse-dot 1.6s ease-in-out infinite;
    }
    .hero-badge-pulse {
        padding-left: 28px !important;
    }
    @keyframes pulse-dot {
        0%   { box-shadow: 0 0 0 0 rgba(99,102,241,0.55); }
        70%  { box-shadow: 0 0 0 8px rgba(99,102,241,0); }
        100% { box-shadow: 0 0 0 0 rgba(99,102,241,0); }
    }

    .hero-stat-box {
        transition: transform 0.25s ease, background-color 0.25s ease;
    }
    .hero-stat-box:hover {
        transform: translateY(-4px);
    }

    /* ---- Card hover lift ---- */
    .keh-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
    }
    .keh-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 1rem 2rem rgba(0,0,0,0.08);
    }

    /* ---- Step number pop on hover ---- */
    .brand-icon {
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .keh-card:hover .brand-icon {
        transform: scale(1.12) rotate(-6deg);
    }

    /* ---- Mentor avatar ---- */
    .mentor-avatar {
        transition: transform 0.35s ease;
    }
    .keh-card:hover .mentor-avatar {
        transform: scale(1.06);
    }

    /* ---- Category tiles ---- */
    .hover-border-primary {
        transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
        border: 1px solid transparent;
    }
    .hover-border-primary:hover {
        border-color: var(--bs-primary, #6366f1);
        transform: translateY(-4px);
    }
    .hover-border-primary:hover i {
        transform: scale(1.15);
        transition: transform 0.25s ease;
    }

    /* ---- Skill chip subtle shimmer on load ---- */
    .skill-chip {
        transition: transform 0.2s ease;
    }
    .skill-chip:hover {
        transform: translateY(-2px);
    }

    /* ---- Rating shine ---- */
    .rating-star {
        display: inline-block;
        animation: star-glow 2.4s ease-in-out infinite;
    }
    @keyframes star-glow {
        0%, 100% { filter: drop-shadow(0 0 0 rgba(255,193,7,0)); }
        50% { filter: drop-shadow(0 0 4px rgba(255,193,7,0.6)); }
    }

    @media (prefers-reduced-motion: reduce) {
        .reveal, .keh-card, .brand-icon, .mentor-avatar, .hover-border-primary,
        .skill-chip, .hero-stat-box, .rating-star, .hero-badge-pulse::before {
            animation: none !important;
            transition: none !important;
        }
    }
</style>

<!-- Hero Section -->
<div class="hero-section mb-5">
    <div class="container text-center text-lg-start">
        <div class="row align-items-center">
            <div class="col-lg-7 mb-4 mb-lg-0 reveal">
                <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 rounded-pill mb-3 hero-badge-pulse">
                    <i class="fas fa-sparkles me-1"></i> Peer-to-Peer Skill Exchange for Students
                </span>
                <h1 class="display-4 fw-extrabold mb-3">Teach What You Know.<br><span class="text-primary-gradient">Learn What You Love.</span></h1>
                <p class="lead text-light-emphasis mb-4 pe-lg-5">
                    Trade skills with fellow students — Programming, Design, Video Editing, Languages, and more. No fees, just knowledge changing hands.
                </p>
                <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                    <a href="/KEH/find-mentor.php" class="btn btn-primary btn-lg px-4 rounded-pill shadow-sm">
                        <i class="fas fa-search me-2"></i> Find a Mentor
                    </a>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a href="/KEH/register.php" class="btn btn-outline-light btn-lg px-4 rounded-pill">
                            Join Free in 30 Seconds
                        </a>
                    <?php else: ?>
                        <a href="/KEH/dashboard.php" class="btn btn-outline-light btn-lg px-4 rounded-pill">
                            Go to Dashboard
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-5 text-center reveal">
                <div class="p-4 bg-white bg-opacity-10 rounded-4 border border-light border-opacity-25 shadow-lg backdrop-blur">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-3 bg-dark bg-opacity-50 rounded-3 border border-secondary border-opacity-25 hero-stat-box">
                                <h2 class="fw-bold text-warning mb-0 stat-count" data-target="<?= (int)$total_users ?>">0+</h2>
                                <span class="small text-light">Student Mentors</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-dark bg-opacity-50 rounded-3 border border-secondary border-opacity-25 hero-stat-box">
                                <h2 class="fw-bold text-info mb-0 stat-count" data-target="<?= (int)$total_skills ?>">0+</h2>
                                <span class="small text-light">Skills Offered</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-3 bg-dark bg-opacity-50 rounded-3 border border-secondary border-opacity-25 hero-stat-box">
                                <h2 class="fw-bold text-success mb-0 stat-count" data-target="<?= (int)$completed_sessions ?>">0+</h2>
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
    <div class="text-center mb-5 reveal">
        <h6 class="text-primary fw-bold text-uppercase">Simple Process</h6>
        <h2 class="fw-bold">How Knowledge Exchange Hub Works</h2>
    </div>
    
    <div class="row g-4 mb-5">
        <div class="col-md-3 reveal">
            <div class="keh-card text-center p-4 h-100">
                <div class="brand-icon mx-auto mb-3 bg-primary text-white fs-4 rounded-circle" style="width:54px; height:54px;">1</div>
                <h5 class="fw-bold">Create Profile</h5>
                <p class="text-muted small">List the skills you can teach and the topics you want to learn.</p>
            </div>
        </div>
        <div class="col-md-3 reveal">
            <div class="keh-card text-center p-4 h-100">
                <div class="brand-icon mx-auto mb-3 bg-info text-white fs-4 rounded-circle" style="width:54px; height:54px;">2</div>
                <h5 class="fw-bold">Find Mentor</h5>
                <p class="text-muted small">Search students by skill, rating, or proficiency level and send a request.</p>
            </div>
        </div>
        <div class="col-md-3 reveal">
            <div class="keh-card text-center p-4 h-100">
                <div class="brand-icon mx-auto mb-3 bg-purple text-white fs-4 rounded-circle" style="width:54px; height:54px;">3</div>
                <h5 class="fw-bold">Chat &amp; Video Call</h5>
                <p class="text-muted small">Chat with your mentor and join 1-on-1 WebRTC video learning sessions.</p>
            </div>
        </div>
        <div class="col-md-3 reveal">
            <div class="keh-card text-center p-4 h-100">
                <div class="brand-icon mx-auto mb-3 bg-success text-white fs-4 rounded-circle" style="width:54px; height:54px;">4</div>
                <h5 class="fw-bold">Review &amp; Earn</h5>
                <p class="text-muted small">Confirm completion, leave reviews, and climb the student leaderboard!</p>
            </div>
        </div>
    </div>

    <!-- Featured Mentors -->
    <div class="d-flex justify-content-between align-items-center mb-4 reveal">
        <div>
            <h3 class="fw-bold mb-1">Top Rated Student Mentors</h3>
            <p class="text-muted small mb-0">Learn from high-ranking student tutors across colleges.</p>
        </div>
        <a href="/KEH/find-mentor.php" class="btn btn-outline-primary rounded-pill">View All Mentors <i class="fas fa-arrow-right ms-1"></i></a>
    </div>

    <div class="row g-4 mb-5">
        <?php if (empty($featured_mentors)): ?>
            <div class="col-12 reveal">
                <div class="keh-card p-5 text-center">
                    <i class="fas fa-user-graduate fs-1 text-primary mb-3"></i>
                    <h5 class="fw-bold mb-1">No mentors yet — be the first!</h5>
                    <p class="text-muted small mb-0">Create your profile and start teaching what you know.</p>
                </div>
            </div>
        <?php endif; ?>
        <?php foreach ($featured_mentors as $m): ?>
            <?php 
                $rating = $m['avg_rating'] ? round((float)$m['avg_rating'], 1) : 5.0;
                // Fetch taught skills
                $sk_stmt = $pdo->prepare("SELECT s.skill_name, us.proficiency_level FROM user_skills us JOIN skills s ON us.skill_id = s.id WHERE us.user_id = ? AND us.skill_type = 'teach'");
                $sk_stmt->execute([$m['id']]);
                $tsk = $sk_stmt->fetchAll();
            ?>
            <div class="col-md-4 reveal">
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
                        <span class="small font-weight-bold text-warning"><i class="fas fa-star rating-star me-1"></i> <?= $rating ?> / 5</span>
                        <a href="/KEH/mentor-details.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-primary rounded-pill px-3">Connect</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Skill Categories Grid -->
    <div class="text-center mb-4 reveal">
        <h3 class="fw-bold mb-1">Popular Skill Categories</h3>
        <p class="text-muted small">Explore topics available for peer tutoring.</p>
    </div>
    
    <div class="row g-3">
        <?php foreach ($categories as $cat): ?>
            <div class="col-6 col-md-3 reveal">
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Scroll-reveal
    const revealEls = document.querySelectorAll('.reveal');
    const io = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                io.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12 });
    revealEls.forEach(el => io.observe(el));

    // Animated stat count-up
    const counters = document.querySelectorAll('.stat-count');
    const countIo = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const el = entry.target;
            const target = parseInt(el.getAttribute('data-target'), 10) || 0;
            const duration = 900;
            const start = performance.now();
            function step(now) {
                const progress = Math.min((now - start) / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                el.textContent = Math.floor(eased * target) + '+';
                if (progress < 1) requestAnimationFrame(step);
                else el.textContent = target + '+';
            }
            requestAnimationFrame(step);
            countIo.unobserve(el);
        });
    }, { threshold: 0.4 });
    counters.forEach(el => countIo.observe(el));
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
 