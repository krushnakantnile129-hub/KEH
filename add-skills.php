<?php
$page_title = "Manage Skills";
require_once __DIR__ . '/includes/auth-check.php';
require_once __DIR__ . '/includes/functions.php';

$user_id = $current_user['id'];
$error = '';
$success = '';

// Handle Delete Skill Action
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $user_skill_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($user_skill_id) {
        $stmt = $pdo->prepare("DELETE FROM user_skills WHERE id = ? AND user_id = ?");
        $stmt->execute([$user_skill_id, $user_id]);
        $success = "Skill removed from your profile.";
    }
}

// Handle Add Skill Action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $skill_type        = $_POST['skill_type'] ?? 'teach';
    $skill_name        = sanitize($_POST['skill_name'] ?? '');
    $category_id       = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT) ?? 1; // Default to 1 (Programming) or Others
    $proficiency_level = $_POST['proficiency_level'] ?? 'Intermediate';

    if (!in_array($skill_type, ['teach', 'learn'])) $skill_type = 'teach';
    if (!in_array($proficiency_level, ['Beginner', 'Intermediate', 'Advanced', 'Expert'])) $proficiency_level = 'Intermediate';

    if (empty($skill_name)) {
        $error = "Please enter a skill name.";
    } else {
        // Check if skill exists in catalog or create new skill automatically
        $stmt = $pdo->prepare("SELECT id FROM skills WHERE LOWER(skill_name) = LOWER(?)");
        $stmt->execute([$skill_name]);
        $existing = $stmt->fetch();

        if ($existing) {
            $final_skill_id = $existing['id'];
        } else {
            $stmt = $pdo->prepare("INSERT INTO skills (skill_name, category_id) VALUES (?, ?)");
            $stmt->execute([$skill_name, $category_id ?: 11]);
            $final_skill_id = $pdo->lastInsertId();
        }

        // Check if user already added this skill
        $stmt = $pdo->prepare("SELECT id FROM user_skills WHERE user_id = ? AND skill_id = ? AND skill_type = ?");
        $stmt->execute([$user_id, $final_skill_id, $skill_type]);

        if ($stmt->fetch()) {
            $error = "You have already added '{$skill_name}' to your " . ($skill_type === 'teach' ? 'teaching' : 'learning') . " list.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO user_skills (user_id, skill_id, skill_type, proficiency_level) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $final_skill_id, $skill_type, $proficiency_level]);
            $success = "Skill '{$skill_name}' added successfully to your profile!";
        }
    }
}

// Fetch all categories for form
$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();

// Fetch current user skills
$stmt = $pdo->prepare("
    SELECT us.*, s.skill_name, c.category_name 
    FROM user_skills us
    JOIN skills s ON us.skill_id = s.id
    JOIN categories c ON s.category_id = c.id
    WHERE us.user_id = ?
    ORDER BY us.id DESC
");
$stmt->execute([$user_id]);
$current_skills = $stmt->fetchAll();

$teach_skills = array_filter($current_skills, fn($s) => $s['skill_type'] === 'teach');
$learn_skills = array_filter($current_skills, fn($s) => $s['skill_type'] === 'learn');

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-4">
    <div class="row g-4">
        <!-- Add Skill Form Left -->
        <div class="col-lg-5">
            <div class="keh-card p-4 shadow-sm">
                <h4 class="fw-bold mb-3"><i class="fas fa-plus-circle text-primary me-2"></i> Add Skill</h4>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-1"></i> <?= e($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-1"></i> <?= e($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="add-skills.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Skill Type (Select Purpose):</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="skill_type" id="type_teach" value="teach" checked>
                            <label class="btn btn-outline-success py-2 fw-semibold" for="type_teach">
                                <i class="fas fa-chalkboard-teacher me-1"></i> Skill I Can Teach
                            </label>

                            <input type="radio" class="btn-check" name="skill_type" id="type_learn" value="learn">
                            <label class="btn btn-outline-info py-2 fw-semibold" for="type_learn">
                                <i class="fas fa-graduation-cap me-1"></i> Skill I Want to Learn
                            </label>
                        </div>
                        <div class="form-text text-xs text-muted mt-1">
                            Choose <strong>Teach</strong> if you want to mentor others in this skill, or <strong>Learn</strong> if you want to acquire this skill.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="skill_name" class="form-label fw-semibold small">Skill Name</label>
                        <input type="text" class="form-control form-control-lg fs-6" id="skill_name" name="skill_name" required placeholder="e.g. Java, Python, Graphic Design, Web Development..." value="<?= e($_POST['skill_name'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label for="category_id" class="form-label fw-semibold small">Category</label>
                        <select class="form-select" id="category_id" name="category_id">
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= e($cat['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="proficiency_level" class="form-label fw-semibold small">Proficiency Level</label>
                        <select class="form-select" id="proficiency_level" name="proficiency_level">
                            <option value="Beginner">Beginner</option>
                            <option value="Intermediate" selected>Intermediate</option>
                            <option value="Advanced">Advanced</option>
                            <option value="Expert">Expert</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2.5 rounded-pill fw-semibold shadow-sm">
                        <i class="fas fa-plus me-1"></i> Save Skill to My Profile
                    </button>
                </form>
            </div>
        </div>

        <!-- Current Skills List Right -->
        <div class="col-lg-7">
            <!-- 1. Skills I Can Teach Card -->
            <div class="keh-card p-4 shadow-sm mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="fw-bold mb-0 text-success">
                        <i class="fas fa-chalkboard-teacher me-2"></i> Skills I Can Teach <span class="badge bg-success-subtle text-success ms-1"><?= count($teach_skills) ?></span>
                    </h5>
                    <span class="text-muted text-xs">Visible to students seeking mentors</span>
                </div>

                <?php if (empty($teach_skills)): ?>
                    <div class="text-center py-3 bg-light rounded-3">
                        <p class="text-muted small mb-0">No teaching skills added yet. Use the form on the left to add skills you can teach!</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Skill</th>
                                    <th>Category</th>
                                    <th>Level</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teach_skills as $ts): ?>
                                    <tr>
                                        <td class="fw-bold text-dark"><?= e($ts['skill_name']) ?></td>
                                        <td class="small text-muted"><?= e($ts['category_name']) ?></td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success-emphasis rounded-pill px-2.5 py-1"><?= e($ts['proficiency_level']) ?></span>
                                        </td>
                                        <td class="text-end">
                                            <a href="add-skills.php?action=delete&id=<?= $ts['id'] ?>" class="btn btn-sm btn-outline-danger rounded-circle" onclick="return confirm('Are you sure you want to remove this teaching skill?');" title="Remove">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 2. Skills I Want to Learn Card -->
            <div class="keh-card p-4 shadow-sm">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="fw-bold mb-0 text-info">
                        <i class="fas fa-graduation-cap me-2"></i> Skills I Want to Learn <span class="badge bg-info-subtle text-info ms-1"><?= count($learn_skills) ?></span>
                    </h5>
                    <span class="text-muted text-xs">Your learning goals</span>
                </div>

                <?php if (empty($learn_skills)): ?>
                    <div class="text-center py-3 bg-light rounded-3">
                        <p class="text-muted small mb-0">No learning goals added yet. Select 'Skill I Want to Learn' in the form to add one!</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Skill</th>
                                    <th>Category</th>
                                    <th>Target Level</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($learn_skills as $ls): ?>
                                    <tr>
                                        <td class="fw-bold text-dark"><?= e($ls['skill_name']) ?></td>
                                        <td class="small text-muted"><?= e($ls['category_name']) ?></td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info-emphasis rounded-pill px-2.5 py-1"><?= e($ls['proficiency_level']) ?></span>
                                        </td>
                                        <td class="text-end">
                                            <a href="add-skills.php?action=delete&id=<?= $ls['id'] ?>" class="btn btn-sm btn-outline-danger rounded-circle" onclick="return confirm('Are you sure you want to remove this learning goal?');" title="Remove">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
