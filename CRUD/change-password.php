<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_admin();

$error = null;
$success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if (!authenticate_admin(ADMIN_USERNAME, $currentPassword)) {
        $error = 'Your current password is incorrect.';
    } elseif (strlen($newPassword) < 10) {
        $error = 'The new password must be at least 10 characters long.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'The new passwords do not match.';
    } else {
        $statement = db()->prepare('UPDATE admin_users SET password_hash = :password_hash WHERE username = :username');
        $statement->execute([
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'username' => ADMIN_USERNAME,
        ]);
        $success = 'Your password has been changed successfully.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password | Raissa</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="topbar"><div class="shell topbar-inner"><a class="logo" href="index.php">RAISSA</a><a class="button secondary" href="index.php">Back to dashboard</a></div></header>
<main class="shell auth-page">
    <span class="eyebrow">Account security</span>
    <h1>Change password</h1>
    <p class="lead">Update the password used to protect your admin dashboard.</p>
    <section class="card">
        <?php if ($error !== null): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
        <?php if ($success !== null): ?><p class="success"><?= e($success) ?></p><?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="field"><label for="current_password">Current password</label><input id="current_password" name="current_password" type="password" autocomplete="current-password" required></div>
            <div class="field"><label for="new_password">New password</label><input id="new_password" name="new_password" type="password" minlength="10" autocomplete="new-password" required></div>
            <div class="field"><label for="confirm_password">Confirm new password</label><input id="confirm_password" name="confirm_password" type="password" minlength="10" autocomplete="new-password" required></div>
            <div class="form-actions"><a class="button secondary" href="index.php">Cancel</a><button class="button" type="submit">Save password</button></div>
        </form>
    </section>
</main>
</body>
</html>