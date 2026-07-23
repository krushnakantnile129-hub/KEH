<?php
$page_title = "Admin - Manage Categories";
require_once __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../includes/functions.php';

$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';

// Handle Add Category Action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_name = sanitize($_POST['category_name'] ?? '');

    if (empty($category_name)) {
        $error = "Category name cannot be empty.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (category_name) VALUES (?)");
            $stmt->execute([$category_name]);
            $msg = "Category '{$category_name}' created successfully.";
        } catch (Exception $e) {
            $error = "Failed to add category. " . $e->getMessage();
        }
    }
}

// Handle Delete Category Action
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $cat_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($cat_id) {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$cat_id]);
        $msg = "Category deleted.";
    }
}

// Fetch categories with skill counts
$categories = $pdo->query("
    SELECT c.*, 
           (SELECT COUNT(id) FROM skills WHERE category_id = c.id) as total_skills
    FROM categories c
    ORDER BY c.category_name ASC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">Manage Skill Categories</h3>
            <p class="text-muted small mb-0">Organize skills into logical groups for student search and navigation.</p>
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
        <!-- Add Category Form Left -->
        <div class="col-lg-4">
            <div class="keh-card p-4 shadow-sm">
                <h5 class="fw-bold mb-3"><i class="fas fa-plus-circle text-info me-2"></i> Add Category</h5>
                <form action="categories.php" method="POST">
                    <div class="mb-4">
                        <label for="category_name" class="form-label fw-semibold small">Category Name</label>
                        <input type="text" class="form-control" id="category_name" name="category_name" required placeholder="e.g. AI &amp; Machine Learning">
                    </div>
                    <button type="submit" class="btn btn-info text-white w-100 rounded-pill py-2 fw-semibold shadow-sm">
                        <i class="fas fa-check me-1"></i> Save Category
                    </button>
                </form>
            </div>
        </div>

        <!-- Categories Table Right -->
        <div class="col-lg-8">
            <div class="keh-card p-4 shadow-sm">
                <h5 class="fw-bold mb-3"><i class="fas fa-layer-group text-primary me-2"></i> Existing Categories (<?= count($categories) ?>)</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Category Name</th>
                                <th>Associated Skills</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td class="fw-bold"><?= e($cat['category_name']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= (int)$cat['total_skills'] ?> skills</span></td>
                                    <td class="text-end">
                                        <a href="categories.php?action=delete&id=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-danger rounded-circle" onclick="return confirm('Delete this category and its associated skills?');" title="Delete">
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
