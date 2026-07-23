<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$query        = trim($_GET['query'] ?? '');
$category_id  = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT);
$proficiency  = trim($_GET['proficiency'] ?? '');
$min_rating   = filter_input(INPUT_GET, 'min_rating', FILTER_VALIDATE_FLOAT) ?? 0;

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

if (!empty($query)) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.college LIKE ? OR u.department LIKE ? OR s.skill_name LIKE ?)";
    $term = "%{$query}%";
    $params[] = $term; $params[] = $term; $params[] = $term; $params[] = $term; $params[] = $term;
}

if ($category_id) {
    $sql .= " AND s.category_id = ?";
    $params[] = $category_id;
}

if (!empty($proficiency)) {
    $sql .= " AND us.proficiency_level = ?";
    $params[] = $proficiency;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$results = [];
foreach ($users as $user) {
    $rating = $user['avg_rating'] ? round((float)$user['avg_rating'], 1) : 0.0;
    
    if ($min_rating > 0 && $rating < $min_rating) {
        continue;
    }

    // Fetch skills taught by this user
    $skills_stmt = $pdo->prepare("
        SELECT s.skill_name, us.proficiency_level 
        FROM user_skills us 
        JOIN skills s ON us.skill_id = s.id 
        WHERE us.user_id = ? AND us.skill_type = 'teach'
    ");
    $skills_stmt->execute([$user['id']]);
    $teach_skills = $skills_stmt->fetchAll();

    $results[] = [
        'id'             => (int)$user['id'],
        'name'           => e($user['name']),
        'photo'          => e($user['profile_photo']),
        'college'        => e($user['college'] ?? 'College Student'),
        'department'     => e($user['department'] ?? 'General'),
        'bio'            => e($user['bio'] ?? ''),
        'rating'         => $rating,
        'rating_stars'   => render_stars($rating),
        'total_sessions' => (int)$user['total_sessions'],
        'points'         => (int)$user['points'],
        'teach_skills'   => $teach_skills
    ];
}

echo json_encode(['success' => true, 'mentors' => $results]);
?>
