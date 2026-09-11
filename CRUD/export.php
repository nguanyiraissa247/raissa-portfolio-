<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_admin();

$search = trim((string) ($_GET['search'] ?? ''));
$serviceFilter = trim((string) ($_GET['service'] ?? ''));
$statement = db()->query('SELECT id, name, email, service, message, created_at FROM enquiries ORDER BY created_at DESC');
$enquiries = $statement->fetchAll();
$enquiries = array_filter($enquiries, static function (array $enquiry) use ($search, $serviceFilter): bool {
    $matchesSearch = $search === '' || stripos((string) $enquiry['name'], $search) !== false || stripos((string) $enquiry['email'], $search) !== false || stripos((string) $enquiry['message'], $search) !== false;
    $matchesService = $serviceFilter === '' || (string) $enquiry['service'] === $serviceFilter;

    return $matchesSearch && $matchesService;
});

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="raissa-enquiries.csv"');
$output = fopen('php://output', 'wb');
fputcsv($output, ['ID', 'Name', 'Email', 'Service', 'Message', 'Created at']);
foreach ($enquiries as $enquiry) {
    fputcsv($output, [$enquiry['id'], $enquiry['name'], $enquiry['email'], $enquiry['service'], $enquiry['message'], $enquiry['created_at']]);
}
fclose($output);