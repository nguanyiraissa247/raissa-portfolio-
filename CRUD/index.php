<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_admin();
require_once __DIR__ . '/database.php';

$error = null;
$enquiries = [];
$ratings = [];
$serviceCounts = [];
$search = trim((string) ($_GET['search'] ?? ''));
$serviceFilter = trim((string) ($_GET['service'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
try {
    $enquiries = db()->query('SELECT id, name, email, service, message, created_at FROM enquiries ORDER BY created_at DESC')->fetchAll();
    $ratings = db()->query('SELECT id, name, rating, feedback, is_approved, created_at FROM ratings ORDER BY created_at DESC')->fetchAll();
    foreach ($enquiries as $enquiry) {
        $service = (string) $enquiry['service'];
        $serviceCounts[$service] = ($serviceCounts[$service] ?? 0) + 1;
    }
} catch (Throwable $exception) {
    $error = 'Your enquiries are temporarily unavailable. Please make sure MySQL is running in XAMPP and refresh this page.';
}
$visibleEnquiries = array_values(array_filter($enquiries, static function (array $enquiry) use ($search, $serviceFilter): bool {
    $matchesSearch = $search === '' || stripos((string) $enquiry['name'], $search) !== false || stripos((string) $enquiry['email'], $search) !== false || stripos((string) $enquiry['message'], $search) !== false;
    $matchesService = $serviceFilter === '' || (string) $enquiry['service'] === $serviceFilter;

    return $matchesSearch && $matchesService;
}));
$serviceOptions = array_keys($serviceCounts);
sort($serviceOptions);
$totalPages = max(1, (int) ceil(count($visibleEnquiries) / $perPage));
$page = min($page, $totalPages);
$pageEnquiries = array_slice($visibleEnquiries, ($page - 1) * $perPage, $perPage);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raissa | Client Enquiries</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="topbar">
    <div class="shell topbar-inner">
        <a class="logo" href="../project/index.php">RAISSA</a>
        <div class="actions">
            <a class="button secondary" href="create.php">+ New enquiry</a>
            <a class="button secondary" href="change-password.php">Change password</a>
            <a class="button secondary" href="logout.php">Log out</a>
        </div>
    </div>
</header>
<main class="shell">
    <span class="eyebrow">PHP + MySQL workspace</span>
    <h1>Client enquiries</h1>
    <p class="lead">Keep requests for websites, shops, and booking pages in one simple place.</p>
    <?php if ($error !== null): ?>
        <p class="error"><?= e($error) ?></p>
    <?php else: ?>
        <?php
        arsort($serviceCounts);
        $topService = array_key_first($serviceCounts);
        $approvedRatings = count(array_filter($ratings, static fn (array $rating): bool => (int) $rating['is_approved'] === 1));
        $pendingRatings = count($ratings) - $approvedRatings;
        ?>
        <section class="stats-grid" aria-label="Workspace summary">
            <article class="stat-card"><span class="stat-label">Total enquiries</span><strong><?= count($enquiries) ?></strong><span class="stat-note">All client requests</span></article>
            <article class="stat-card"><span class="stat-label">Pending ratings</span><strong><?= $pendingRatings ?></strong><span class="stat-note">Awaiting approval</span></article>
            <article class="stat-card"><span class="stat-label">Visible ratings</span><strong><?= $approvedRatings ?></strong><span class="stat-note">Published testimonials</span></article>
            <article class="stat-card accent"><span class="stat-label">Top service</span><strong><?= $topService !== null ? e($topService) : 'None yet' ?></strong><span class="stat-note"><?= $topService !== null ? $serviceCounts[$topService] . ' request' . ($serviceCounts[$topService] === 1 ? '' : 's') : 'Add an enquiry to begin' ?></span></article>
        </section>
        <form class="filter-bar" method="get">
            <label class="filter-field" for="search">Search enquiries<input id="search" name="search" value="<?= e($search) ?>" placeholder="Name, email, or message"></label>
            <label class="filter-field" for="service">Service<select id="service" name="service"><option value="">All services</option><?php foreach ($serviceOptions as $service): ?><option value="<?= e($service) ?>" <?= $serviceFilter === $service ? 'selected' : '' ?>><?= e($service) ?></option><?php endforeach; ?></select></label>
            <button class="button" type="submit">Filter</button>
            <?php if ($search !== '' || $serviceFilter !== ''): ?><a class="button secondary" href="index.php">Clear</a><?php endif; ?>
            <a class="button secondary" href="export.php?search=<?= urlencode($search) ?>&amp;service=<?= urlencode($serviceFilter) ?>">Export CSV</a>
        </form>
        <section class="card">
            <?php if ($enquiries === []): ?>
                <p class="empty">No enquiries yet. Add the first one to test the system.</p>
            <?php elseif ($visibleEnquiries === []): ?>
                <p class="empty">No enquiries match the current filters.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Name</th><th>Email</th><th>Service</th><th>Message</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($pageEnquiries as $enquiry): ?>
                            <tr>
                                <td><?= e($enquiry['name']) ?></td>
                                <td><a href="mailto:<?= e($enquiry['email']) ?>"><?= e($enquiry['email']) ?></a></td>
                                <td><?= e($enquiry['service']) ?></td>
                                <td class="message"><?= e($enquiry['message']) ?></td>
                                <td>
                                    <div class="actions">
                                        <a class="button secondary" href="update.php?id=<?= (int) $enquiry['id'] ?>">Edit</a>
                                        <form class="inline-form" action="delete.php" method="post" onsubmit="return confirm('Delete this enquiry?');">
                                            <input type="hidden" name="id" value="<?= (int) $enquiry['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <button class="button danger" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($totalPages > 1): ?>
                    <nav class="pagination" aria-label="Enquiry pages">
                        <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                            <a class="<?= $pageNumber === $page ? 'current' : '' ?>" href="?search=<?= urlencode($search) ?>&amp;service=<?= urlencode($serviceFilter) ?>&amp;page=<?= $pageNumber ?>"><?= $pageNumber ?></a>
                        <?php endfor; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($error === null): ?>
        <section class="card">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">Choose what visitors see</span>
                    <h2>Rating approvals</h2>
                </div>
                <a class="button secondary" href="rating.php">Open rating page</a>
            </div>
            <?php if ($ratings === []): ?>
                <p class="empty">No ratings have been submitted yet.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Name</th><th>Rating</th><th>Feedback</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php foreach ($ratings as $rating): ?>
                            <tr>
                                <td><?= e($rating['name']) ?></td>
                                <td class="stars"><?= str_repeat('★', (int) $rating['rating']) . str_repeat('☆', 5 - (int) $rating['rating']) ?></td>
                                <td class="message"><?= e($rating['feedback']) ?></td>
                                <td><?= (int) $rating['is_approved'] === 1 ? 'Visible' : 'Hidden' ?></td>
                                <td>
                                    <form class="inline-form" action="approve-rating.php" method="post">
                                        <input type="hidden" name="id" value="<?= (int) $rating['id'] ?>">
                                        <input type="hidden" name="approved" value="<?= (int) $rating['is_approved'] === 1 ? '0' : '1' ?>">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <button class="button <?= (int) $rating['is_approved'] === 1 ? 'danger' : '' ?>" type="submit"><?= (int) $rating['is_approved'] === 1 ? 'Hide' : 'Approve' ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
