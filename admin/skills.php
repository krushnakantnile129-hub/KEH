<?php
$page_title = "Admin - Manage Skills";
require_once __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../includes/functions.php';

$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';

// Handle Add Skill Action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $skill_name  = sanitize($_POST['skill_name'] ?? '');
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);

    if (empty($skill_name) || !$category_id) {
        $error = "Please enter a skill name and select a category.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO skills (skill_name, category_id) VALUES (?, ?)");
            $stmt->execute([$skill_name, $category_id]);
            $msg = "Skill '{$skill_name}' added to catalog successfully.";
        } catch (Exception $e) {
            $error = "Failed to add skill: " . $e->getMessage();
        }
    }
}

// Handle Delete Skill Action
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $skill_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($skill_id) {
        $stmt = $pdo->prepare("DELETE FROM skills WHERE id = ?");
        $stmt->execute([$skill_id]);
        $msg = "Skill deleted from catalog.";
    }
}

// Fetch categories & skills
$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();
$skills_list = $pdo->query("
    SELECT s.*, c.category_name,
           (SELECT COUNT(id) FROM user_skills WHERE skill_id = s.id) as total_users
    FROM skills s
    JOIN categories c ON s.category_id = c.id
    ORDER BY s.skill_name ASC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">Manage Catalog Skills</h3>
            <p class="text-muted small mb-0">Add new skills for students to teach and learn, or clean up unused skills.</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill"><i class="fas fa-arrow-left me-1"></i> Admin Dashboard</a>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-1"></i> <?= e($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-1"></i> <?= e($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Add Skill Form Left -->
        <div class="col-lg-4">
            <div class="keh-card p-4 shadow-sm">
                <h5 class="fw-bold mb-3"><i class="fas fa-plus-circle text-primary me-2"></i> Add Skill</h5>
                <form action="skills.php" method="POST">
                    <div class="mb-3">
                        <label for="skill_name" class="form-label fw-semibold small">Skill Name</label>
                        <input type="text" class="form-control" id="skill_name" name="skill_name" required placeholder="e.g. Kotlin, Docker, Video Editing">
                    </div>
                    <div class="mb-4">
                        <label for="category_id" class="form-label fw-semibold small">Category</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">-- Choose Category --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= e($cat['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-semibold shadow-sm">
                        <i class="fas fa-check me-1"></i> Save Skill
                    </button>
                </form>
            </div>
        </div>

        <!-- Skills Table Right -->
        <div class="col-lg-8">
            <div class="keh-card p-4 shadow-sm">
                <h5 class="fw-bold mb-3"><i class="fas fa-list text-warning me-2"></i> Catalog Skills (<?= count($skills_list) ?>)</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Skill Name</th>
                                <th>Category</th>
                                <th>Students Using</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($skills_list as $sk): ?>
                                <tr>
                                    <td class="fw-bold"><?= e($sk['skill_name']) ?></td>
                                    <td class="small text-muted"><?= e($sk['category_name']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= (int)$sk['total_users'] ?> users</span></td>
                                    <td class="text-end">
                                        <a href="skills.php?action=delete&id=<?= $sk['id'] ?>" class="btn btn-sm btn-outline-danger rounded-circle" onclick="return confirm('Delete this skill from catalog?');" title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
