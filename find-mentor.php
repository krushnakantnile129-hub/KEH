<?php
$page_title = "Find Student Mentors";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$selected_category = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT);
$search_query      = trim($_GET['query'] ?? '');
$selected_prof     = trim($_GET['proficiency'] ?? '');
$selected_rating   = filter_input(INPUT_GET, 'min_rating', FILTER_VALIDATE_FLOAT) ?? 0;

// Fetch categories for filter dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();

// Fetch initial mentor list for server-side rendering
$sql = "
    SELECT DISTINCT u.id, u.name, u.email, u.profile_photo, u.college, u.department, u.bio, u.points,
           (SELECT COUNT(id) FROM sessions WHERE (learner_id = u.id OR mentor_id = u.id) AND status = 'completed') as total_sessions,
           (SELECT AVG(rating) FROM reviews WHERE reviewed_user_id = u.id) as avg_rating
    FROM users u
    LEFT JOIN user_skills us ON u.id = us.user_id AND us.skill_type = 'teach'
    LEFT JOIN skills s ON us.skill_id = s.id
    WHERE u.is_blocked = 0
";
$params = [];

if (!empty($search_query)) {
    $sql .= " AND (u.name LIKE ? OR s.skill_name LIKE ? OR u.college LIKE ?)";
    $term = "%{$search_query}%";
    $params[] = $term; $params[] = $term; $params[] = $term;
}

if ($selected_category) {
    $sql .= " AND s.category_id = ?";
    $params[] = $selected_category;
}

if (!empty($selected_prof)) {
    $sql .= " AND us.proficiency_level = ?";
    $params[] = $selected_prof;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$mentors = $stmt->fetchAll();
?>

<div class="container py-4">
    <!-- Search Banner -->
    <div class="keh-card p-4 p-md-5 mb-4 bg-white shadow-sm rounded-4">
        <div class="row justify-content-center text-center mb-4">
            <div class="col-lg-8">
                <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-1 rounded-pill mb-2">Student Mentor Directory</span>
                <h2 class="fw-bold mb-2">Find a Peer Mentor &amp; Learn Free</h2>
                <p class="text-muted small">Search students by skill, college, proficiency, or ratings.</p>
            </div>
        </div>

        <!-- Filter Controls Row -->
        <form action="find-mentor.php" method="GET" class="row g-3">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" class="form-control border-start-0" id="searchInput" name="query" placeholder="Search skill (e.g. Java, Figma, Python)..." value="<?= e($search_query) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="categorySelect" name="category_id">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($selected_category == $cat['id']) ? 'selected' : '' ?>><?= e($cat['category_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="proficiencySelect" name="proficiency">
                    <option value="">All Proficiency Levels</option>
                    <option value="Beginner" <?= ($selected_prof === 'Beginner') ? 'selected' : '' ?>>Beginner</option>
                    <option value="Intermediate" <?= ($selected_prof === 'Intermediate') ? 'selected' : '' ?>>Intermediate</option>
                    <option value="Advanced" <?= ($selected_prof === 'Advanced') ? 'selected' : '' ?>>Advanced</option>
                    <option value="Expert" <?= ($selected_prof === 'Expert') ? 'selected' : '' ?>>Expert</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" id="minRatingSelect" name="min_rating">
                    <option value="0">Any Rating</option>
                    <option value="4.0" <?= ($selected_rating == 4.0) ? 'selected' : '' ?>>4.0+ Stars</option>
                    <option value="4.5" <?= ($selected_rating == 4.5) ? 'selected' : '' ?>>4.5+ Stars</option>
                </select>
            </div>
        </form>
    </div>

    <!-- Mentors Cards Grid -->
    <div class="row" id="mentorsContainer">
        <?php if (empty($mentors)): ?>
            <div class="col-12 text-center py-5">
                <div class="p-4 bg-white rounded-4 shadow-sm d-inline-block" style="max-width: 450px;">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5>No Mentors Found</h5>
                    <p class="text-muted small">No students matched your search criteria. Try adjusting your search query or filters.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($mentors as $m): ?>
                <?php 
                    $rating = $m['avg_rating'] ? round((float)$m['avg_rating'], 1) : 0.0;
                    if ($selected_rating > 0 && $rating < $selected_rating) continue;

                    // Fetch skills taught by user
                    $sk_stmt = $pdo->prepare("SELECT s.skill_name, us.proficiency_level FROM user_skills us JOIN skills s ON us.skill_id = s.id WHERE us.user_id = ? AND us.skill_type = 'teach'");
                    $sk_stmt->execute([$m['id']]);
                    $teach_skills = $sk_stmt->fetchAll();
                ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="keh-card h-100 p-4 d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <img src="/KEH/uploads/profile_photos/<?= e($m['profile_photo']) ?>" 
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($m['name']) ?>&background=6366f1&color=fff';"
                                 alt="<?= e($m['name']) ?>" class="mentor-avatar me-3">
                            <div>
                                <h5 class="fw-bold mb-1"><?= e($m['name']) ?></h5>
                                <p class="text-muted small mb-1"><i class="fas fa-university me-1 text-primary"></i> <?= e($m['college'] ?? 'College') ?></p>
                                <span class="badge bg-purple-subtle rounded-pill"><?= e($m['department'] ?? 'General') ?></span>
                            </div>
                        </div>

                        <p class="text-secondary small mb-3 flex-grow-1"><?= e(mb_strimwidth($m['bio'] ?? '', 0, 110, '...')) ?></p>

                        <div class="mb-3">
                            <label class="form-label small text-muted fw-bold mb-1">Teaches:</label>
                            <div>
                                <?php if (empty($teach_skills)): ?>
                                    <span class="text-muted small">No skills listed</span>
                                <?php else: ?>
                                    <?php foreach ($teach_skills as $ts): ?>
                                        <?php 
                                            $badgeClass = 'badge-prof-intermediate';
                                            if ($ts['proficiency_level'] === 'Beginner') $badgeClass = 'badge-prof-beginner';
                                            if ($ts['proficiency_level'] === 'Advanced') $badgeClass = 'badge-prof-advanced';
                                            if ($ts['proficiency_level'] === 'Expert') $badgeClass = 'badge-prof-expert';
                                        ?>
                                        <span class="skill-chip <?= $badgeClass ?> mb-1 me-1"><?= e($ts['skill_name']) ?> (<?= $ts['proficiency_level'] ?>)</span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between pt-3 border-top mt-auto">
                            <div class="small">
                                <?= render_stars($rating) ?>
                                <span class="fw-bold ms-1"><?= $rating > 0 ? $rating . '/5' : 'New' ?></span>
                                <span class="text-muted ms-1">(<?= (int)$m['total_sessions'] ?> sessions)</span>
                            </div>
                            <div>
                                <a href="mentor-details.php?id=<?= $m['id'] ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">View Profile</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Live Search JavaScript -->
<script src="/KEH/assets/js/live-search.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
