<?php
// Run once from the terminal for accounts still using the old passwords.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../config/db.php';

$count = 0;
$db->beginTransaction();

try {
    // Lock the accounts until all the old passwords are converted.
    $rows = $db->query("SELECT user_id, password FROM `user` FOR UPDATE")->fetchAll();
    foreach ($rows as $row) {
        // skip accounts already hashed, so running twice is safe
        if (password_get_info($row['password'])['algoName'] !== 'unknown') continue;
        if ($row['password'] === '' || strlen($row['password']) > 72 || strpos($row['password'], "\0") !== false) {
            throw new RuntimeException('An old password needs a reset before migration: user ' . $row['user_id']);
        }

        $hash = password_hash($row['password'], PASSWORD_BCRYPT);
        $st = $db->prepare("UPDATE `user` SET password = ? WHERE user_id = ?");
        $st->execute([$hash, $row['user_id']]);
        $count += $st->rowCount();
    }
    $db->commit();
    echo "Passwords converted: $count\n";
} catch (Exception $ex) {
    $db->rollBack();
    fwrite(STDERR, "Passwords were not converted. " . $ex->getMessage() . "\n");
    exit(1);
}
