<?php
// Knowledge Exchange Hub - Core Helper Functions

/**
 * XSS prevention escaping wrapper
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Clean & sanitize user input string
 */
function sanitize($input) {
    return trim(strip_tags($input ?? ''));
}

/**
 * Award points to a user and log transaction
 */
function award_points($pdo, $user_id, $points, $reason) {
    try {
        // Insert point log entry
        $stmt = $pdo->prepare("INSERT INTO points (user_id, points, reason) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $points, $reason]);

        // Update user total points
        $stmt = $pdo->prepare("UPDATE users SET points = points + ? WHERE id = ?");
        $stmt->execute([$points, $user_id]);
        return true;
    } catch (Exception $e) {
        error_log("Error awarding points: " . $e->getMessage());
        return false;
    }
}

/**
 * Get average rating and total review count for a user
 */
function get_user_rating($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(id) as total_reviews FROM reviews WHERE reviewed_user_id = ?");
    $stmt->execute([$user_id]);
    $res = $stmt->fetch();
    return [
        'avg'   => $res['avg_rating'] ? round((float)$res['avg_rating'], 1) : 0,
        'count' => (int)$res['total_reviews']
    ];
}

/**
 * Get unread messages count for a user
 */
function get_unread_message_count($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT COUNT(id) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    return (int)$stmt->fetchColumn();
}

/**
 * Get pending requests count for mentor
 */
function get_pending_request_count($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT COUNT(id) FROM learning_requests WHERE mentor_id = ? AND status = 'pending'");
    $stmt->execute([$user_id]);
    return (int)$stmt->fetchColumn();
}

/**
 * Render star HTML based on rating (1-5)
 */
function render_stars($rating) {
    $html = '<div class="star-rating text-warning d-inline-block">';
    $rounded = round($rating * 2) / 2; // round to nearest 0.5
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rounded) {
            $html .= '<i class="fas fa-star"></i>';
        } elseif ($i - 0.5 == $rounded) {
            $html .= '<i class="fas fa-star-half-alt"></i>';
        } else {
            $html .= '<i class="far fa-star text-muted"></i>';
        }
    }
    $html .= '</div>';
    return $html;
}

/**
 * Format relative time or standard date
 */
function format_time($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return "just now";
    if ($diff < 3600) return floor($diff / 60) . "m ago";
    if ($diff < 86400) return floor($diff / 3600) . "h ago";
    if ($diff < 604800) return floor($diff / 86400) . "d ago";
    
    return date("M j, Y g:i A", $time);
}

/**
 * Calculate user profile completion percentage (0% - 100%)
 */
function get_profile_completion_percentage($pdo, $user) {
    if (!$user) return 25;
    $score = 0;

    if (!empty($user['name'])) $score += 20;
    if (!empty($user['college']) && strtolower($user['college']) !== 'student') $score += 10;
    if (!empty($user['department']) && strtolower($user['department']) !== 'general') $score += 10;
    if (!empty($user['profile_photo']) && $user['profile_photo'] !== 'default_avatar.png') $score += 15;
    if (!empty($user['bio']) && strlen(trim($user['bio'])) > 5) $score += 15;
    if (!empty($user['github_url']) || !empty($user['linkedin_url'])) $score += 15;

    if (isset($user['id']) && $pdo) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_skills WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            if ($stmt->fetchColumn() > 0) $score += 15;
        } catch (Exception $e) {
            // Ignore exception
        }
    }

    return min(100, max(25, $score));
}
?>
