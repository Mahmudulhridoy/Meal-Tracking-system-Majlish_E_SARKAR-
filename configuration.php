<?php
// MAJLISH E SARKAR - configuration + core system helper
/**
 * this file is loaded in every page and API. It handles:
 *
 *    debug mode settings
 *    secure session handling
 *    PDO database connection
 *    authentication helpers (loggedIn, isAdmin, etc.)
 *    settings helpers (getSetting, updateSetting)
 *    meal cutoff logic
 *    snitization function for safe output
 *
 * foundation of the entire project.
 */


// DEBUGGING
/**
 * These settings show all PHP errors on screen.
 * very helpful during development.
 * you should disable display_errors on a live production server.
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// SESSION START (SECURE)
/**
 * session_status() checks if a session is already active.
 * If not, we start a secure one.
 *
 * cookie_httponly = prevents JavaScript from reading session cookies.
 * cookie_samesite = protects against CSRF attacks.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax'
    ]);
}

// database configuration
//database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'majlish_e_sarkar');
define('DB_USER', 'root');
define('DB_PASS', '');

// PDO CONNECT
/**
 * PDO is used instead of MySQLi because:
 *   - More secure
 *   - Better error handling
 *   - Prepared statements by default
 *   - Supports multiple database drivers
 *
 * ATTR_ERRMODE             Throws exceptions on database errors
 * ATTR_DEFAULT_FETCH_MODE  Always returns associative arrays
 * ATTR_EMULATE_PREPARES    Prevents SQL injection at driver level
 */
try {
    $pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} 
catch(PDOException $e) {
        // Log database error silently and show safe message to user
    error_log("DB ERROR: " . $e->getMessage());
    die("Database connection failed!");
}

// AUTH FUNCTIONS
/**
 * Check if a user is logged in.
 * Returns TRUE when session has user_id set.
 */
function loggedIn() { 
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']); 
}
/**
 * Check if logged-in user is an Admin.
 * Useful for protecting admin-only sections.
 */
function isAdmin() { 
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin'; 
}
/**
 * Require login for protected pages.
 * If user is not logged in - redirect to login page.
 */
function login_Need() {
    if (!loggedIn()) {
        header("Location: login.php");
        exit;
    }
}

function adminrequire() {
    login_Need(); // must be logged in first

    if (!isAdmin()) {
        echo json_encode(["success" => false, "message" => "Admin access required"]);
        exit;
    }
}

// GETTERS
function getUserId() { return $_SESSION['user_id'] ?? null; }
function getRole() { return $_SESSION['role'] ?? null; }
function getUserName() { return $_SESSION['name'] ?? "Guest"; }

// MEMBER ID FETCHER
/**
 * This function returns the logged-in member's ID from the `members` table.
 *
 * Admins do not have a member_id - return NULL for them.
 *
 * To avoid repeating the query too often, we store the member_id in session
 * after the first fetch.
 */
function getMemberId() {
    global $pdo;

    if (isAdmin()) return null; // ADMINS HAVE NO MEMBER ID-return null

    if (!isset($_SESSION['member_id'])) {
        $stmt = $pdo->prepare("SELECT id FROM members WHERE user_id=? LIMIT 1");
        $stmt->execute([getUserId()]);
        $m = $stmt->fetch();

        if ($m) {
            $_SESSION['member_id'] = $m['id'];
        }
    }

    return $_SESSION['member_id'] ?? null;
}

// SETTINGS
/**
 * Fetch value from `settings` table.
 *
 * Example:
 *   getSetting("cost_per_meal", 60);
 *
 * Returns default if not found.
 */
function getSetting($key, $default='') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE key_name=? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row['value'] ?? $default;
    } 
    catch(Exception $e) {
    // In case of DB error, fallback to default
        return $default;
    }
}

/**
 * Insert or update a setting.
 * If key exists  update value.
 * If not - create new setting row
 */
function updateSetting($key, $value) {
    global $pdo;

    $stmt = $pdo->prepare("
        INSERT INTO settings (key_name, value)
        VALUES(?, ?)
        ON DUPLICATE KEY UPDATE value = VALUES(value)
    ");
    return $stmt->execute([$key, $value]);
}

// CUT-OFF LOGIC (FINAL)
/**
 * Determines whether a meal can be edited (attendance updated).
 *
 * RULES:
 *  - FUTURE date   always editable
 *  - PAST date     never editable
 *  - TODAY         editable only before cutoff time
 */
function canEditMeal($mealType, $date) {
    $today = date("Y-m-d"); //current date
    $nowTime = date("H:i"); //current time(24h)
    $cutoff = getSetting($mealType . "_cutoff", "00:00"); //fallback to midnight

    if ($date > $today) return true;   // Future → always editable
    if ($date < $today) return false;  // Past → never editable

    // Today → compare times
    return $nowTime < $cutoff;
}

// SANITIZATION
/**
 * Escape HTML to prevent XSS attacks.
 * Use this when printing database or user inputs inside HTML pages.
 */
function e($s) { 
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); 
}
?>
