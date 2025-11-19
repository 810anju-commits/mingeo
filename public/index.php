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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mining &amp; Geology Application Monitoring System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php
$sidebarMenu = [
    [
        'label' => 'Applications Received',
        'count' => (int) $summary['received'],
        'icon' => 'bi-inbox-arrow-down',
        'accent' => 'info'
    ],
    [
        'label' => 'Under Processing',
        'count' => (int) $summary['under_processing'],
        'icon' => 'bi-hourglass-split',
        'accent' => 'warning'
    ],
    [
        'label' => 'Forwarded to DMG',
        'count' => (int) max($summary['under_processing'] - 12, 0),
        'icon' => 'bi-arrow-left-right',
        'accent' => 'secondary'
    ],
    [
        'label' => 'Clarifications Issued',
        'count' => (int) max($summary['received'] - $summary['disposed'], 5),
        'icon' => 'bi-question-circle',
        'accent' => 'light'
    ],
    [
        'label' => 'Approved / Sanctioned',
        'count' => (int) max($summary['disposed'] - $summary['rejected'], 0),
        'icon' => 'bi-check2-circle',
        'accent' => 'success'
    ],
    [
        'label' => 'Rejected',
        'count' => (int) $summary['rejected'],
        'icon' => 'bi-x-octagon',
        'accent' => 'danger'
    ],
    [
        'label' => 'Disposed',
        'count' => (int) $summary['disposed'],
        'icon' => 'bi-clipboard-check',
        'accent' => 'primary'
    ],
];

$receivedBase = max(120, (int) $summary['received']);
$disposedBase = max(80, (int) $summary['disposed']);
$processingBase = max(60, (int) $summary['under_processing']);
$rejectedBase = max(15, (int) $summary['rejected']);

$monthlyFlow = [
    'Jan' => ['received' => (int) round($receivedBase * 0.22), 'disposed' => (int) round($disposedBase * 0.18)],
    'Feb' => ['received' => (int) round($receivedBase * 0.19), 'disposed' => (int) round($disposedBase * 0.2)],
    'Mar' => ['received' => (int) round($receivedBase * 0.21), 'disposed' => (int) round($disposedBase * 0.21)],
    'Apr' => ['received' => (int) round($receivedBase * 0.2), 'disposed' => (int) round($disposedBase * 0.22)],
    'May' => ['received' => (int) round($receivedBase * 0.18), 'disposed' => (int) round($disposedBase * 0.19)],
];

$divisionShare = [
    'State HQ' => (int) round($processingBase * 0.35),
    'Central Clearance Cell' => (int) round($processingBase * 0.27),
    'District Offices' => (int) round($processingBase * 0.23),
    'Field Inspection Units' => (int) round($processingBase * 0.15),
];

$insightTiles = [
    ['icon' => 'bi-speedometer2', 'label' => 'Avg. Processing Time', 'value' => '18 days', 'trend' => '+6% faster'],
    ['icon' => 'bi-geo-alt-fill', 'label' => 'Field Verifications', 'value' => number_format((int) round($processingBase * 0.42)), 'trend' => 'completed this month'],
    ['icon' => 'bi-lightning-charge', 'label' => 'Digital Clearances', 'value' => number_format((int) round($disposedBase * 0.64)), 'trend' => 'issued online'],
];
?>
<header class="hero-header text-white">
    <div class="hero-overlay"></div>
    <nav class="top-nav container-fluid">
        <div class="govt-brand d-flex align-items-center gap-3">
            <img src="https://via.placeholder.com/72x72.png?text=Logo" alt="Kerala Logo" class="brand-mark">
            <div class="brand-text">
                <p class="small text-uppercase mb-0">Government of Kerala</p>
                <h1 class="h4 mb-0">Department of Mining &amp; Geology</h1>
                <p class="mb-0">Application Monitoring &amp; Citizen Dashboard</p>
            </div>
        </div>
        <ul class="nav-menu list-unstyled d-none d-lg-flex">
            <li><i class="bi bi-stars"></i> Govt. of Kerala</li>
            <li><i class="bi bi-building"></i> Department</li>
            <li><i class="bi bi-gem"></i> Mining &amp; Geology</li>
            <li><i class="bi bi-people"></i> Citizen Services</li>
        </ul>
    </nav>
    <div class="hero-content container">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <p class="badge rounded-pill text-bg-warning text-dark shadow-sm mb-3">Vibrant Mining Governance Suite</p>
                <h2 class="display-6 fw-bold">Live insights on quarrying, mineral concessions and citizen applications in Kerala.</h2>
                <p class="lead">Transparent status tracking, proactive alerts and colorful informatics empower applicants and officials to collaborate seamlessly.</p>
                <div class="d-flex flex-wrap gap-3">
                    <div class="hero-chip"><i class="bi bi-broadcast"></i> Real-time monitoring</div>
                    <div class="hero-chip"><i class="bi bi-bar-chart"></i> Policy dashboards</div>
                    <div class="hero-chip"><i class="bi bi-shield-check"></i> Trusted data</div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="glow-card">
                    <h3 class="h5">This Week</h3>
                    <ul class="glow-list list-unstyled">
                        <li><span>Fresh Applications</span><strong><?= number_format((int) round($receivedBase * 0.12)); ?></strong></li>
                        <li><span>Approvals Granted</span><strong><?= number_format((int) round($disposedBase * 0.14)); ?></strong></li>
                        <li><span>Queries Raised</span><strong><?= number_format((int) round($processingBase * 0.08)); ?></strong></li>
                    </ul>
                    <p class="mb-0 text-white-50 small">Updated <?= date('d M Y'); ?> | Figures include quarrying, mineral and exploration permits.</p>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="app-shell container-fluid">
    <aside class="status-sidebar" id="statusSidebar">
        <div class="sidebar-header d-flex align-items-center justify-content-between">
            <div>
                <p class="text-uppercase small mb-0 text-white-50">Status Navigator</p>
                <h3 class="h5 mb-0">Application Journey</h3>
            </div>
            <button class="btn btn-outline-light btn-sm d-md-none" id="closeSidebar"><i class="bi bi-x-lg"></i></button>
        </div>
        <ul class="status-menu list-unstyled mt-4">
            <?php foreach ($sidebarMenu as $item): ?>
                <li>
                    <a href="#" class="status-link">
                        <span class="status-icon"><i class="bi <?= htmlspecialchars($item['icon']); ?>"></i></span>
                        <span class="status-label"><?= htmlspecialchars($item['label']); ?></span>
                        <span class="badge rounded-pill text-bg-<?= htmlspecialchars($item['accent']); ?>"><?= number_format($item['count']); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="sidebar-footer mt-4">
            <p class="mb-1 fw-semibold">Assistance Desk</p>
            <p class="small mb-0">dmg.support@kerala.gov.in</p>
            <p class="small text-white-50 mb-0">+91 471 252 3625 (10AM – 5PM)</p>
        </div>
    </aside>

    <main class="main-content">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
            <div>
                <p class="text-uppercase text-muted small mb-1">Government to Citizen Dashboard</p>
                <h2 class="fw-bold mb-0">Vibrant Mining Informatics</h2>
                <p class="mb-0 text-muted">Colorful analytics representing live departmental performance.</p>
            </div>
            <button class="btn btn-primary d-md-none" id="openSidebar"><i class="bi bi-list"></i> Status Menu</button>
        </div>

        <section class="panel glass-panel">
            <div class="row g-4">
                <div class="col-6 col-lg-3">
                    <div class="metric-card gradient-blue">
                        <span class="metric-icon"><i class="bi bi-clipboard-data"></i></span>
                        <p class="metric-label">Total Received</p>
                        <p class="metric-value"><?= number_format((int) $summary['received']); ?></p>
                        <p class="metric-trend text-white-50"><i class="bi bi-arrow-up"></i> 12% higher than last cycle</p>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="metric-card gradient-green">
                        <span class="metric-icon"><i class="bi bi-patch-check"></i></span>
                        <p class="metric-label">Disposed</p>
                        <p class="metric-value"><?= number_format((int) $summary['disposed']); ?></p>
                        <p class="metric-trend text-white-50"><i class="bi bi-activity"></i> Continuous clearances</p>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="metric-card gradient-orange">
                        <span class="metric-icon"><i class="bi bi-clock-history"></i></span>
                        <p class="metric-label">Under Process</p>
                        <p class="metric-value"><?= number_format((int) $summary['under_processing']); ?></p>
                        <p class="metric-trend text-white-50"><i class="bi bi-bell"></i> Alerts sent to districts</p>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="metric-card gradient-red">
                        <span class="metric-icon"><i class="bi bi-slash-circle"></i></span>
                        <p class="metric-label">Rejected</p>
                        <p class="metric-value"><?= number_format((int) $summary['rejected']); ?></p>
                        <p class="metric-trend text-white-50"><i class="bi bi-info-circle"></i> Reasons communicated</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel mt-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <h3 class="h5 mb-3">Search Application Status</h3>
                    <?php if ($repository->isUsingFallback()) : ?>
                        <div class="alert alert-warning shadow-sm" role="alert">
                            Demo mode enabled – showing static prototype data because MySQL connection is not configured yet.
                        </div>
                    <?php endif; ?>
                    <form method="post" class="row g-3">
                        <div class="col-md-8">
                            <label for="file_no" class="form-label">File Number</label>
                            <input type="text" name="file_no" id="file_no" class="form-control form-control-lg" placeholder="Enter file number" required>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-lg btn-gradient w-100"><i class="bi bi-search"></i> Track Now</button>
                        </div>
                    </form>
                    <?php if ($message) : ?>
                        <p class="text-muted mt-3 mb-0"><?= htmlspecialchars($message); ?></p>
                    <?php endif; ?>
                </div>
                <div class="col-lg-6">
                    <div class="data-tiles">
                        <?php foreach ($insightTiles as $tile): ?>
                            <div class="insight-card">
                                <span class="insight-icon"><i class="bi <?= htmlspecialchars($tile['icon']); ?>"></i></span>
                                <div>
                                    <p class="insight-label mb-1"><?= htmlspecialchars($tile['label']); ?></p>
                                    <p class="insight-value mb-0"><?= htmlspecialchars($tile['value']); ?></p>
                                    <small class="text-muted"><?= htmlspecialchars($tile['trend']); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php if ($searchResult) : ?>
                <div class="table-wrapper mt-4">
                    <table class="table table-hover table-modern">
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
                            <td><span class="badge text-bg-warning text-dark"><?= htmlspecialchars($searchResult['status']); ?></span></td>
                            <td><?= htmlspecialchars($searchResult['current_level_label'] ?? $searchResult['current_level']); ?></td>
                            <td><?= formatDate($searchResult['last_updated'] ?? null); ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="panel mt-4">
            <div class="row g-4">
                <div class="col-lg-7">
                    <h3 class="h5">Monthly Progress Snapshot</h3>
                    <div class="chart-grid">
                        <?php foreach ($monthlyFlow as $month => $data): ?>
                            <div class="chart-bar">
                                <p class="chart-label mb-1"><?= htmlspecialchars($month); ?></p>
                                <div class="bar-stack">
                                    <div class="bar-segment received" style="height: <?= max(20, $data['received']); ?>px"></div>
                                    <div class="bar-segment disposed" style="height: <?= max(20, $data['disposed']); ?>px"></div>
                                </div>
                                <div class="chart-legends d-flex justify-content-between text-muted small">
                                    <span><?= number_format($data['received']); ?> received</span>
                                    <span><?= number_format($data['disposed']); ?> disposed</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-lg-5">
                    <h3 class="h5">Processing Footprint</h3>
                    <ul class="list-group list-group-flush footprint-list">
                        <?php foreach ($divisionShare as $division => $value): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="mb-0 fw-semibold"><?= htmlspecialchars($division); ?></p>
                                    <small class="text-muted">Active queue &amp; inspections</small>
                                </div>
                                <span class="badge rounded-pill text-bg-primary">
                                    <?= number_format($value); ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </section>

        <section class="panel mt-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <h3 class="h5">Status Flow</h3>
                    <ul class="status-flow-list list-unstyled">
                        <li>
                            <span class="flow-index">1</span>
                            <div>
                                <p class="mb-0 fw-semibold">Application Received</p>
                                <small class="text-muted">District portals capture citizen submissions.</small>
                            </div>
                        </li>
                        <li>
                            <span class="flow-index">2</span>
                            <div>
                                <p class="mb-0 fw-semibold">Scrutiny &amp; Clarifications</p>
                                <small class="text-muted">Technical teams review documents and dispatch clarifications.</small>
                            </div>
                        </li>
                        <li>
                            <span class="flow-index">3</span>
                            <div>
                                <p class="mb-0 fw-semibold">DMG Evaluation</p>
                                <small class="text-muted">Headquarters validates compliance and approvals.</small>
                            </div>
                        </li>
                        <li>
                            <span class="flow-index">4</span>
                            <div>
                                <p class="mb-0 fw-semibold">Final Disposal</p>
                                <small class="text-muted">Applicants receive sanctioned orders or reasons for rejection.</small>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <div class="vibrant-card">
                        <p class="text-uppercase small mb-1 text-white-50">Citizen Snapshot</p>
                        <h3 class="h4 text-white">98.2% of applications now tracked digitally.</h3>
                        <p class="text-white-50">Seamless integration of district, HQ and field inspections brings transparency to mining governance.</p>
                        <div class="d-flex flex-wrap gap-3">
                            <div class="mini-graph">
                                <span>e-Services</span>
                                <strong>142</strong>
                            </div>
                            <div class="mini-graph">
                                <span>Mobile Alerts</span>
                                <strong>876</strong>
                            </div>
                            <div class="mini-graph">
                                <span>Video Hearings</span>
                                <strong>64</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

<footer class="footer text-center text-white-50">
    <div class="container py-4">
        <p class="mb-1">Department of Mining &amp; Geology, Government of Kerala</p>
        <p class="mb-0">Prototype interface for demonstration purposes only.</p>
    </div>
</footer>

<script>
    const sidebar = document.getElementById('statusSidebar');
    const openSidebar = document.getElementById('openSidebar');
    const closeSidebar = document.getElementById('closeSidebar');

    openSidebar?.addEventListener('click', () => sidebar?.classList.add('visible'));
    closeSidebar?.addEventListener('click', () => sidebar?.classList.remove('visible'));
    sidebar?.addEventListener('click', (event) => {
        if (event.target === sidebar && sidebar.classList.contains('visible')) {
            sidebar.classList.remove('visible');
        }
    });
</script>
</body>
</html>
