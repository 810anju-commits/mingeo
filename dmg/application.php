<?php
require_once __DIR__ . '/../includes/Repository.php';
require_once __DIR__ . '/../includes/helpers.php';

$repository = new Repository();
$fileNo = $_GET['file'] ?? '';
$application = $fileNo ? $repository->searchApplication($fileNo) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DMG Application Processing</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<header>
    <div class="container">
        <h1>Application Processing</h1>
        <p>Directorate level actions for file <?= htmlspecialchars($fileNo ?: ''); ?></p>
    </div>
</header>
<main class="container">
    <?php if (!$application) : ?>
        <p class="muted">No application selected. <a href="index.php">Go back</a></p>
    <?php else : ?>
        <section class="panel">
            <h2>1. Application Summary</h2>
            <div class="table-wrapper">
                <table class="table">
                    <tr><th>File Number</th><td><?= htmlspecialchars($application['file_no']); ?></td></tr>
                    <tr><th>Applicant Name</th><td><?= htmlspecialchars($application['applicant_name']); ?></td></tr>
                    <tr><th>Application Type</th><td><?= htmlspecialchars($application['application_type']); ?></td></tr>
                    <tr><th>Current Status</th><td><?= htmlspecialchars($application['status']); ?></td></tr>
                    <tr><th>Current Level</th><td><?= htmlspecialchars($application['current_level_label'] ?? $application['current_level']); ?></td></tr>
                    <tr><th>Last Updated</th><td><?= formatDate($application['last_updated'] ?? null); ?></td></tr>
                </table>
            </div>
        </section>
        <section class="panel">
            <h2>2. District Entered Fields</h2>
            <div class="table-wrapper">
                <table class="table">
                    <tr><th>District</th><td><?= htmlspecialchars($application['district']); ?></td></tr>
                    <tr><th>Taluk</th><td><?= htmlspecialchars($application['taluk']); ?></td></tr>
                    <tr><th>Village</th><td><?= htmlspecialchars($application['village']); ?></td></tr>
                    <tr><th>Area</th><td><?= htmlspecialchars($application['area']); ?> Ha</td></tr>
                    <tr><th>Mineral</th><td><?= htmlspecialchars($application['mineral_type']); ?></td></tr>
                    <tr><th>Application Submission</th><td><?= formatDate($application['application_submission'] ?? null); ?></td></tr>
                    <tr><th>Survey / Land Details</th><td><?= htmlspecialchars($application['address']); ?></td></tr>
                </table>
            </div>
        </section>
        <section class="panel">
            <h2>3. DMG Editable Fields</h2>
            <form class="form-grid">
                <div>
                    <label>File Scrutiny Date</label>
                    <input type="date" value="<?= htmlspecialchars($application['directorate_scrutiny'] ?? ''); ?>">
                </div>
                <div>
                    <label>Rectification Letter Issued</label>
                    <input type="date" value="<?= htmlspecialchars($application['directorate_rectification'] ?? ''); ?>">
                </div>
                <div>
                    <label>LOI Issued Date</label>
                    <input type="date" value="<?= htmlspecialchars($application['loi_issued'] ?? ''); ?>">
                </div>
                <div>
                    <label>Statutory Licence Issued Date</label>
                    <input type="date" value="<?= htmlspecialchars($application['statutory_license_issued'] ?? ''); ?>">
                </div>
                <div>
                    <label>QL Order Issued Date</label>
                    <input type="date" value="<?= htmlspecialchars($application['ql_order_issued'] ?? ''); ?>">
                </div>
                <div>
                    <label>Validity of QL</label>
                    <input type="date" value="<?= htmlspecialchars($application['ql_validity'] ?? ''); ?>">
                </div>
            </form>
        </section>
        <section class="panel">
            <h2>4. Clarification Section</h2>
            <textarea placeholder="Enter clarification message for district office" style="width:100%;min-height:120px;"></textarea>
            <div style="margin-top:1rem;">
                <button class="button secondary">Send Back to District</button>
            </div>
        </section>
        <section class="panel">
            <h2>5. Approval Actions</h2>
            <div style="display:flex;gap:1rem;">
                <button class="button green">Approve / Sanction</button>
                <button class="button secondary">Request Clarification</button>
                <button class="button danger">Reject</button>
            </div>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
