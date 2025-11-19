<?php
require_once __DIR__ . '/../includes/Repository.php';
require_once __DIR__ . '/../includes/helpers.php';

$repository = new Repository();
$districtName = $_GET['district'] ?? 'Kasargod';
$summary = $repository->getDistrictSummary($districtName);
$monthly = $repository->getDistrictMonthlyTrend($districtName);
$pending = $repository->getDistrictPendingBreakdown($districtName);
$searchResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searchResult = $repository->searchApplication($_POST['file_no'] ?? '');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DMG District View | <?= htmlspecialchars($districtName); ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<header>
    <div class="container">
        <nav class="breadcrumbs"><a href="index.php">Directorate Dashboard</a> / <?= htmlspecialchars($districtName); ?></nav>
        <h1><?= htmlspecialchars($districtName); ?> District Summary</h1>
        <p class="muted">Monitoring of district level applications</p>
    </div>
</header>
<main class="container">
    <section class="panel">
        <h2>Summary</h2>
        <div class="cards">
            <?= renderCard('Total Received', (int) $summary['received'], 'blue', '📥'); ?>
            <?= renderCard('Under Processing', (int) $summary['under_processing'], 'orange', '⏳'); ?>
            <?= renderCard('Disposed', (int) $summary['disposed'], 'green', '✅'); ?>
            <?= renderCard('Rejected', (int) $summary['rejected'], 'red', '❌'); ?>
        </div>
    </section>
    <section class="panel">
        <h2>Monthly Applications</h2>
        <?= renderBars($monthly, ['received' => 'var(--blue)', 'disposed' => 'var(--green)', 'under_processing' => 'var(--orange)']); ?>
    </section>
    <section class="panel">
        <h2>Pending Status</h2>
        <div class="cards">
            <?php foreach ($pending as $label => $value) : ?>
                <?= renderCard($label, (int) $value, 'yellow'); ?>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="panel">
        <h2>File Search</h2>
        <form method="post" class="form-grid">
            <div>
                <label>File Number</label>
                <input type="text" name="file_no" required>
            </div>
            <div style="display:flex;align-items:flex-end;">
                <button type="submit">Search</button>
            </div>
        </form>
        <?php if ($searchResult) : ?>
            <div class="table-wrapper" style="margin-top:1rem;">
                <table class="table">
                    <tr><th>Applicant</th><td><?= htmlspecialchars($searchResult['applicant_name']); ?></td></tr>
                    <tr><th>Application Type</th><td><?= htmlspecialchars($searchResult['application_type']); ?></td></tr>
                    <tr><th>Current Level</th><td><?= htmlspecialchars($searchResult['current_level_label'] ?? $searchResult['current_level']); ?></td></tr>
                    <tr><th>Status</th><td><?= htmlspecialchars($searchResult['status']); ?></td></tr>
                    <tr><th>Pending With</th><td><?= htmlspecialchars($searchResult['pending_with'] ?? ''); ?></td></tr>
                    <tr><th>Last Updated</th><td><?= formatDate($searchResult['last_updated'] ?? null); ?></td></tr>
                </table>
            </div>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST') : ?>
            <p class="muted">No results found.</p>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
