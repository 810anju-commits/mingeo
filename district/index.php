<?php
require_once __DIR__ . '/../includes/Repository.php';
require_once __DIR__ . '/../includes/helpers.php';

$repository = new Repository();
$districtName = $_GET['district'] ?? 'Kasargod';
$view = $_GET['view'] ?? 'dashboard';
$message = null;

if ($view === 'application-entry' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'file_no' => $_POST['file_no'] ?? '',
        'applicant_name' => $_POST['applicant_name'] ?? '',
        'address' => $_POST['address'] ?? '',
        'district' => $_POST['district'] ?? $districtName,
        'taluk' => $_POST['taluk'] ?? '',
        'village' => $_POST['village'] ?? '',
        'area' => $_POST['area'] ?? '',
        'mineral_type' => $_POST['mineral_type'] ?? '',
        'date_of_appln' => $_POST['date_of_appln'] ?? null,
        'application_type' => $_POST['application_type'] ?? '',
        'date_file_created' => $_POST['date_file_created'] ?? null,
        'proposed_scrutiny' => $_POST['proposed_scrutiny'] ?? null,
        'latest_document' => $_POST['latest_document'] ?? null,
        'correction_letter' => $_POST['correction_letter'] ?? null,
        'proposed_inspection' => $_POST['proposed_inspection'] ?? null,
        'rectification_letter' => $_POST['rectification_letter'] ?? null,
        'approved_mining_plan' => $_POST['approved_mining_plan'] ?? null,
        'forwarded_to_dmg' => $_POST['forwarded_to_dmg'] ?? null,
        'release_execution' => $_POST['release_execution'] ?? null,
        'first_movement_permit' => $_POST['first_movement_permit'] ?? null,
        'statutory_license_submitted' => $_POST['statutory_license_submitted'] ?? null,
        'statutory_license_forwarded' => $_POST['statutory_license_forwarded'] ?? null,
        'directorate_received' => $_POST['directorate_received'] ?? null,
        'directorate_scrutiny' => $_POST['directorate_scrutiny'] ?? null,
        'directorate_rectification' => $_POST['directorate_rectification'] ?? null,
        'loi_issued' => $_POST['loi_issued'] ?? null,
        'statutory_license_issued' => $_POST['statutory_license_issued'] ?? null,
        'ql_order_issued' => $_POST['ql_order_issued'] ?? null,
        'ql_validity' => $_POST['ql_validity'] ?? null,
        'pending_reason' => $_POST['remarks'] ?? '',
        'pending_with' => $_POST['pending_with'] ?? 'District',
        'status' => $_POST['status'] ?? 'Under Processing',
        'current_level' => $_POST['current_level'] ?? 'District',
        'current_level_label' => $_POST['current_level_label'] ?? 'District Office',
    ];
    if ($repository->saveApplication($data)) {
        $message = 'Application saved successfully.';
    } else {
        $message = 'Unable to save application. Please verify your database connection.';
    }
}

$summary = $repository->getDistrictSummary($districtName);
$monthlyTrend = $repository->getDistrictMonthlyTrend($districtName);
$pendingBreakdown = $repository->getDistrictPendingBreakdown($districtName);
$applications = array_filter(
    $repository->getAllApplications(),
    fn ($app) => strcasecmp($app['district'], $districtName) === 0
);

$pendingApplicant = array_values(array_filter($applications, fn ($app) => str_contains(strtolower($app['status']), 'applicant')));
$underProcessing = array_values(array_filter($applications, fn ($app) => str_contains(strtolower($app['status']), 'under') || str_contains(strtolower($app['status']), 'district')));
$returnedFromDmg = array_values(array_filter($applications, fn ($app) => str_contains(strtolower($app['pending_reason'] ?? ''), 'dmg') || str_contains(strtolower($app['status']), 'returned')));
$disposedApplications = array_values(array_filter($applications, fn ($app) => str_contains(strtolower($app['status']), 'dispose')));
$sanctionedApplications = array_values(array_filter($applications, fn ($app) => str_contains(strtolower($app['status']), 'sanction')));

function active(string $value, string $view): string
{
    return $value === $view ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>District Portal | <?= htmlspecialchars($districtName); ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <h2><?= htmlspecialchars($districtName); ?> District</h2>
        <nav>
            <a href="?view=dashboard&district=<?= urlencode($districtName); ?>" class="<?= active('dashboard', $view); ?>">Home</a>
            <a href="?view=application-entry&district=<?= urlencode($districtName); ?>" class="<?= active('application-entry', $view); ?>">Application Entry</a>
            <a href="?view=pending&district=<?= urlencode($districtName); ?>" class="<?= active('pending', $view); ?>">Pending</a>
            <a href="?view=disposed&district=<?= urlencode($districtName); ?>" class="<?= active('disposed', $view); ?>">Disposed</a>
            <a href="?view=sanctioned&district=<?= urlencode($districtName); ?>" class="<?= active('sanctioned', $view); ?>">Sanctioned</a>
            <a href="?view=search&district=<?= urlencode($districtName); ?>" class="<?= active('search', $view); ?>">File Search</a>
            <a href="#">Logout</a>
        </nav>
    </aside>
    <main class="main-content">
        <header style="margin-bottom:1.5rem;">
            <h1>District Office Portal</h1>
            <p class="muted">Workflow management for mining applications</p>
        </header>

        <?php if ($message) : ?>
            <div class="notice"><?= htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($view === 'dashboard') : ?>
            <section class="panel">
                <h2>Dashboard Cards</h2>
                <div class="cards">
                    <?= renderCard('Total Applications Received', (int) $summary['received'], 'blue', '📥'); ?>
                    <?= renderCard('Under Processing', (int) $summary['under_processing'], 'orange', '⏳'); ?>
                    <?= renderCard('Pending with Applicant', count($pendingApplicant), 'yellow', '📄'); ?>
                    <?= renderCard('Pending with District Office', count($underProcessing), 'orange', '🏢'); ?>
                    <?= renderCard('Returned from DMG', count($returnedFromDmg), 'yellow', '🔁'); ?>
                    <?= renderCard('Disposed', (int) $summary['disposed'], 'green', '✅'); ?>
                    <?= renderCard('Sanctioned', count($sanctionedApplications), 'green', '🪪'); ?>
                </div>
            </section>
            <section class="panel">
                <h2>Monthly Trend</h2>
                <?= renderBars($monthlyTrend, ['received' => 'var(--blue)', 'disposed' => 'var(--green)', 'under_processing' => 'var(--orange)']); ?>
            </section>
            <section class="panel">
                <h2>Pending Status Breakdown</h2>
                <div class="cards">
                    <?php foreach ($pendingBreakdown as $label => $value) : ?>
                        <?= renderCard($label, (int) $value, 'yellow'); ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php elseif ($view === 'application-entry') : ?>
            <section class="panel">
                <h2>Application Entry</h2>
                <form method="post">
                    <div class="form-grid">
                        <div>
                            <label>File Number</label>
                            <input type="text" name="file_no" required>
                        </div>
                        <div>
                            <label>Applicant Name</label>
                            <input type="text" name="applicant_name" required>
                        </div>
                        <div>
                            <label>Address</label>
                            <input type="text" name="address">
                        </div>
                        <div>
                            <label>District</label>
                            <input type="text" name="district" value="<?= htmlspecialchars($districtName); ?>">
                        </div>
                        <div>
                            <label>Taluk</label>
                            <input type="text" name="taluk">
                        </div>
                        <div>
                            <label>Village</label>
                            <input type="text" name="village">
                        </div>
                        <div>
                            <label>Area (Ha)</label>
                            <input type="text" name="area">
                        </div>
                        <div>
                            <label>Mineral Type</label>
                            <input type="text" name="mineral_type">
                        </div>
                        <div>
                            <label>Date of Application Submission</label>
                            <input type="date" name="date_of_appln">
                        </div>
                        <div>
                            <label>Application Type</label>
                            <select name="application_type">
                                <option>Quarrying Lease (QL)</option>
                                <option>Quarrying Permit (QP)</option>
                                <option>Building Permit</option>
                                <option>Special Permission</option>
                                <option>Others</option>
                            </select>
                        </div>
                        <div>
                            <label>Date of File Created</label>
                            <input type="date" name="date_file_created">
                        </div>
                        <div>
                            <label>Proposed Date of Scrutiny</label>
                            <input type="date" name="proposed_scrutiny">
                        </div>
                        <div>
                            <label>Latest Document / Mining Plan Date</label>
                            <input type="date" name="latest_document">
                        </div>
                        <div>
                            <label>Date of Correction Letter</label>
                            <input type="date" name="correction_letter">
                        </div>
                        <div>
                            <label>Proposed Inspection Date</label>
                            <input type="date" name="proposed_inspection">
                        </div>
                        <div>
                            <label>Date of Rectification Letter</label>
                            <input type="date" name="rectification_letter">
                        </div>
                        <div>
                            <label>Date Approved Mining Plan Issued</label>
                            <input type="date" name="approved_mining_plan">
                        </div>
                        <div>
                            <label>Date Forwarded to DMG</label>
                            <input type="date" name="forwarded_to_dmg">
                        </div>
                        <div>
                            <label>Date of Release Execution</label>
                            <input type="date" name="release_execution">
                        </div>
                        <div>
                            <label>Date of First Movement Permit</label>
                            <input type="date" name="first_movement_permit">
                        </div>
                        <div>
                            <label>Date Statutory Licence Submitted</label>
                            <input type="date" name="statutory_license_submitted">
                        </div>
                        <div>
                            <label>Date Statutory Licence Forwarded to DMG</label>
                            <input type="date" name="statutory_license_forwarded">
                        </div>
                        <div>
                            <label>Application Received at Directorate</label>
                            <input type="date" name="directorate_received">
                        </div>
                        <div>
                            <label>Proposed File Scrutiny Date (Directorate)</label>
                            <input type="date" name="directorate_scrutiny">
                        </div>
                        <div>
                            <label>Rectification Letter Issued (Directorate)</label>
                            <input type="date" name="directorate_rectification">
                        </div>
                        <div>
                            <label>Date of LOI Issued</label>
                            <input type="date" name="loi_issued">
                        </div>
                        <div>
                            <label>Date Statutory Licence Issued</label>
                            <input type="date" name="statutory_license_issued">
                        </div>
                        <div>
                            <label>Date of QL Order Issued</label>
                            <input type="date" name="ql_order_issued">
                        </div>
                        <div>
                            <label>Validity of QL</label>
                            <input type="date" name="ql_validity">
                        </div>
                    </div>
                    <div class="form-grid" style="margin-top:1rem;">
                        <div>
                            <label>Pending With</label>
                            <select name="pending_with">
                                <option value="District">District Office</option>
                                <option value="Applicant">Applicant</option>
                                <option value="Directorate">DMG Directorate</option>
                            </select>
                        </div>
                        <div>
                            <label>Current Level</label>
                            <select name="current_level">
                                <option value="District">District</option>
                                <option value="DMG">DMG</option>
                            </select>
                        </div>
                        <div>
                            <label>Status</label>
                            <select name="status">
                                <option>Under Processing</option>
                                <option>Pending with Applicant</option>
                                <option>Returned from DMG</option>
                                <option>Sanctioned</option>
                                <option>Disposed</option>
                                <option>Rejected</option>
                            </select>
                        </div>
                        <div>
                            <label>Remarks / Pending Reason</label>
                            <textarea name="remarks"></textarea>
                        </div>
                    </div>
                    <div style="margin-top:1rem;display:flex;gap:1rem;">
                        <button type="submit">Submit</button>
                        <button type="button" class="secondary">Save Draft</button>
                        <button type="button" class="secondary">Submit to DMG</button>
                    </div>
                </form>
            </section>
        <?php elseif ($view === 'pending') : ?>
            <section class="panel">
                <h2>Pending - Applicant</h2>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>File Number</th>
                                <th>Applicant Name</th>
                                <th>Application Type</th>
                                <th>Pending Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingApplicant as $row) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['file_no']); ?></td>
                                    <td><?= htmlspecialchars($row['applicant_name']); ?></td>
                                    <td><?= htmlspecialchars($row['application_type']); ?></td>
                                    <td><?= htmlspecialchars($row['pending_reason'] ?: 'Awaiting applicant response'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <section class="panel">
                <h2>Under Processing (District)</h2>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>File Number</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($underProcessing as $row) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['file_no']); ?></td>
                                    <td><?= formatDate($row['last_updated'] ?? null); ?></td>
                                    <td><?= htmlspecialchars($row['status']); ?></td>
                                    <td><a class="button secondary" href="?view=application-entry&district=<?= urlencode($districtName); ?>&file=<?= urlencode($row['file_no']); ?>">Open Form</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <section class="panel">
                <h2>Returned from DMG (Clarification)</h2>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>File Number</th>
                                <th>Clarification Reason</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($returnedFromDmg as $row) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['file_no']); ?></td>
                                    <td><?= htmlspecialchars($row['pending_reason'] ?: 'Clarification requested by DMG'); ?></td>
                                    <td><button class="button">Resubmit to DMG</button></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php elseif ($view === 'disposed') : ?>
            <section class="panel">
                <h2>Disposed Applications</h2>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>File Number</th>
                                <th>Application Type</th>
                                <th>Date Disposed</th>
                                <th>View</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($disposedApplications as $row) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['file_no']); ?></td>
                                    <td><?= htmlspecialchars($row['application_type']); ?></td>
                                    <td><?= formatDate($row['ql_order_issued'] ?? $row['last_updated'] ?? null); ?></td>
                                    <td><a class="button secondary" href="?view=search&district=<?= urlencode($districtName); ?>&file=<?= urlencode($row['file_no']); ?>">View Details</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php elseif ($view === 'sanctioned') : ?>
            <section class="panel">
                <h2>Sanctioned Files</h2>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>File Number</th>
                                <th>Sanction Date</th>
                                <th>Document</th>
                                <th>View</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sanctionedApplications as $row) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['file_no']); ?></td>
                                    <td><?= formatDate($row['statutory_license_issued'] ?? null); ?></td>
                                    <td><a href="#" class="button secondary">Download Order</a></td>
                                    <td><a href="?view=search&district=<?= urlencode($districtName); ?>&file=<?= urlencode($row['file_no']); ?>" class="button">View Details</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php elseif ($view === 'search') : ?>
            <section class="panel">
                <h2>File Search</h2>
                <form method="get" class="form-grid">
                    <input type="hidden" name="view" value="search">
                    <input type="hidden" name="district" value="<?= htmlspecialchars($districtName); ?>">
                    <div>
                        <label>File Number</label>
                        <input type="text" name="file" value="<?= htmlspecialchars($_GET['file'] ?? ''); ?>">
                    </div>
                    <div style="display:flex;align-items:flex-end;">
                        <button type="submit">Search</button>
                    </div>
                </form>
                <?php if (!empty($_GET['file'])) : ?>
                    <?php $result = $repository->searchApplication($_GET['file']); ?>
                    <?php if ($result) : ?>
                        <div class="table-wrapper" style="margin-top:1rem;">
                            <table class="table">
                                <tr><th>Applicant</th><td><?= htmlspecialchars($result['applicant_name']); ?></td></tr>
                                <tr><th>District</th><td><?= htmlspecialchars($result['district']); ?></td></tr>
                                <tr><th>Application Type</th><td><?= htmlspecialchars($result['application_type']); ?></td></tr>
                                <tr><th>Current Level</th><td><?= htmlspecialchars($result['current_level_label'] ?? $result['current_level']); ?></td></tr>
                                <tr><th>Pending With</th><td><?= htmlspecialchars($result['pending_with'] ?? ''); ?></td></tr>
                                <tr><th>Pending Reason</th><td><?= htmlspecialchars($result['pending_reason'] ?? ''); ?></td></tr>
                                <tr><th>Last Updated</th><td><?= formatDate($result['last_updated'] ?? null); ?></td></tr>
                            </table>
                        </div>
                    <?php else : ?>
                        <p class="muted">No records found.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
