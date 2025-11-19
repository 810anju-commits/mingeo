<?php
require_once __DIR__ . '/../includes/Repository.php';
require_once __DIR__ . '/../includes/helpers.php';

$repository = new Repository();
$summary = $repository->getDmgSummary();
$monthlyStats = $repository->getDmgMonthlyStats();
$districts = $repository->getDmgDistrictComparison();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DMG Directorate Portal</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<header>
    <div class="container">
        <h1>Directorate of Mining &amp; Geology</h1>
        <p>State level monitoring dashboard</p>
    </div>
</header>
<main class="container">
    <section class="panel">
        <h2>Directorate Dashboard</h2>
        <div class="cards">
            <?= renderCard('Total Applications Received', (int) $summary['received'], 'blue', '📁'); ?>
            <?= renderCard('Total Under Processing', (int) $summary['under_processing'], 'orange', '📊'); ?>
            <?= renderCard('Total Disposed', (int) $summary['disposed'], 'green', '✅'); ?>
            <?= renderCard('Total Rejected', (int) $summary['rejected'], 'red', '❌'); ?>
        </div>
    </section>
    <section class="panel">
        <h2>District Comparison</h2>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>District</th>
                        <th>Received</th>
                        <th>Under Processing</th>
                        <th>Disposed</th>
                        <th>Sanctioned</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($districts as $district => $row) : ?>
                        <tr>
                            <td><a href="district.php?district=<?= urlencode($district); ?>"><?= htmlspecialchars($district); ?></a></td>
                            <td><?= (int) $row['received']; ?></td>
                            <td><?= (int) $row['under_processing']; ?></td>
                            <td><?= (int) $row['disposed']; ?></td>
                            <td><?= (int) $row['sanctioned']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <section class="panel">
        <h2>Monthly Applications</h2>
        <?= renderBars($monthlyStats, [
            'received' => 'var(--blue)',
            'disposed' => 'var(--green)',
            'sanctioned' => 'var(--green)',
            'rejected' => 'var(--red)'
        ]); ?>
    </section>
</main>
<footer class="footer">
    <p>Directorate Monitoring Console</p>
</footer>
</body>
</html>
