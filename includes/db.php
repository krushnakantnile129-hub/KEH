<?php
// Knowledge Exchange Hub - Database Connection (PDO)

$host     = 'localhost';
$dbname   = 'keh_db';
$username = 'root';
$password = ''; // Default XAMPP MySQL password is empty

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // Self-healing schema update for manual reviews (ensures session_id allows NULL)
    try {
        $pdo->exec("ALTER TABLE `reviews` MODIFY `session_id` INT NULL DEFAULT NULL");
    } catch (Exception $ex) {
        // Silently ignore if already nullable or constraint locked
    }
} catch (PDOException $e) {
    // Graceful error display if database isn't imported or connected yet
    die("<div style='font-family:sans-serif; padding:40px; text-align:center;'>
            <h2 style='color:#e63946;'>Database Connection Error</h2>
            <p>Could not connect to MySQL database <strong>{$dbname}</strong> on <strong>{$host}</strong>.</p>
            <p style='background:#f8f9fa; display:inline-block; padding:12px 20px; border-radius:8px; border:1px solid #ddd; color:#444;'>
                <code>" . htmlspecialchars($e->getMessage()) . "</code>
            </p>
            <br/><br/>
            <p>Please make sure Apache & MySQL are running in <strong>XAMPP</strong> and that you have imported <code>database.sql</code> via phpMyAdmin.</p>
         </div>");
}
?>
