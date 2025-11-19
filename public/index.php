<?php
require_once __DIR__ . '/../includes/Repository.php';
require_once __DIR__ . '/../includes/helpers.php';

$repository = new Repository();
$summary = $repository->getPublicSummary();
$searchResult = null;
$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fileNo = trim($_POST['file_no'] ?? '');
    if ($fileNo === '') {
        $message = 'Please enter a valid file number.';
    } else {
        $searchResult = $repository->searchApplication($fileNo);
        if (!$searchResult) {
            $message = 'No results found.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mining &amp; Geology Application Monitoring System</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<header>
    <div class="container">
        <h1>Mining &amp; Geology Application Monitoring System</h1>
        <p>Department of Mining &amp; Geology – Public Portal</p>
    </div>
</header>
<main class="container">
    <section class="panel">
        <h2>Dashboard Summary</h2>
        <div class="cards">
            <?= renderCard('Total Applications Received', (int) $summary['received'], 'blue', '📁'); ?>
            <?= renderCard('Total Applications Disposed', (int) $summary['disposed'], 'green', '✅'); ?>
            <?= renderCard('Under Processing', (int) $summary['under_processing'], 'orange', '⏳'); ?>
            <?= renderCard('Rejected', (int) $summary['rejected'], 'red', '⚠️'); ?>
        </div>
    </section>

    <section class="panel">
        <h2>Search Application Status</h2>
        <?php if ($repository->isUsingFallback()) : ?>
            <div class="notice">
                Demo mode enabled – showing static prototype data because MySQL connection is not configured yet.
            </div>
        <?php endif; ?>
        <form method="post" class="form-grid">
            <div>
                <label for="file_no">File Number</label>
                <input type="text" name="file_no" id="file_no" placeholder="Enter file number" required>
            </div>
            <div style="display:flex;align-items:flex-end;">
                <button type="submit">Search</button>
            </div>
        </form>
        <?php if ($message) : ?>
            <p class="muted"><?= htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <?php if ($searchResult) : ?>
            <div class="table-wrapper" style="margin-top:1rem;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>File Number</th>
                            <th>Application Type</th>
                            <th>Applicant</th>
                            <th>Current Status</th>
                            <th>Current Level</th>
                            <th>Last Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?= htmlspecialchars($searchResult['file_no']); ?></td>
                            <td><?= htmlspecialchars($searchResult['application_type']); ?></td>
                            <td><?= htmlspecialchars($searchResult['applicant_name']); ?></td>
                            <td><span class="badge orange"><?= htmlspecialchars($searchResult['status']); ?></span></td>
                            <td><?= htmlspecialchars($searchResult['current_level_label'] ?? $searchResult['current_level']); ?></td>
                            <td><?= formatDate($searchResult['last_updated'] ?? null); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h2>Status Flow</h2>
        <ul class="status-flow">
            <li class="badge blue">Received</li>
            <li class="badge orange">Under Processing (District)</li>
            <li class="badge orange">Forwarded to DMG</li>
            <li class="badge yellow">Clarification / Returned</li>
            <li class="badge green">Approved / Sanctioned</li>
            <li class="badge red">Rejected</li>
            <li class="badge green">Disposed</li>
        </ul>
    </section>
</main>
<footer class="footer">
    <p>Department of Mining &amp; Geology, Government of Kerala</p>
    <p>Contact: dmg.support@kerala.gov.in | Phone: +91-471-1234567</p>
    <p class="muted">Disclaimer: Prototype interface for demonstration purposes only.</p>
</footer>
</body>
</html>
