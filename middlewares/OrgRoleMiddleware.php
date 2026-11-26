<?php
// member 4 models use $conn; it is the same PDO connection as $db
require_once __DIR__ . '/../config/db.php';
$conn = $db;

// look up the saved role instead of trusting a form value
function isPresident($userId, $orgId) {
    global $conn;
    $users = new UserModel($conn);
    $user = $users->findById($userId);
    if (!$user || $user['role'] !== 'student') return false;
    return $users->canManageOrg($user, $orgId, true);
}

function canManageOrg($userId, $orgId) {
    global $conn;
    $users = new UserModel($conn);
    $user = $users->findById($userId);
    return $users->canManageOrg($user, $orgId);
}
