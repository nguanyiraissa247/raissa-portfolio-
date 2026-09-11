<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

if (is_admin_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_valid_csrf();
    } catch (Throwable $exception) {
        $error = 'The security token is invalid. Please refresh the page and try again.';
    }
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($error === null && authenticate_admin($username, $password)) {
        session_regenerate_id(true);
        $_SESSION['admin_authenticated'] = true;
        header('Location: index.php');
        exit;
    }

    $error = 'Incorrect username or password.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Raissa</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="shell auth-page">
    <span class="eyebrow">Private area</span>
    <h1>Admin login</h1>
    <p class="lead">Sign in to manage enquiries and approve ratings.</p>
    <section class="card">
        <?php if ($error !== null): ?><p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="field"><label for="username">Username</label><input id="username" name="username" autocomplete="username" required></div>
            <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
            <div class="form-actions"><a class="button secondary" href="../project/index.php">Back to portfolio</a><button class="button" type="submit">Sign in</button></div>
        </form>
    </section>
</main>
</body>
</html>