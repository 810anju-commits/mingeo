<?php
class Repository
{
    private ?mysqli $connection = null;
    private bool $useFallback = false;
    private array $sampleData = [];

    public function __construct()
    {
        $config = require __DIR__ . '/../config/database.php';
        $this->connection = @new mysqli(
            $config['host'],
            $config['username'],
            $config['password'],
            $config['database'],
            (int) $config['port']
        );

        if ($this->connection && !$this->connection->connect_errno) {
            $this->connection->set_charset($config['charset'] ?? 'utf8mb4');
        } else {
            $this->useFallback = true;
            $this->sampleData = require __DIR__ . '/../data/sample_data.php';
            $runtimeFile = __DIR__ . '/../data/runtime/applications.json';
            if (file_exists($runtimeFile)) {
                $runtime = json_decode(file_get_contents($runtimeFile), true) ?: [];
                $this->sampleData['applications'] = array_merge($runtime, $this->sampleData['applications']);
            }
        }
    }

    public function __destruct()
    {
        if ($this->connection && !$this->connection->connect_errno) {
            $this->connection->close();
        }
    }

    public function isUsingFallback(): bool
    {
        return $this->useFallback;
    }

    public function getPublicSummary(): array
    {
        if ($this->useFallback) {
            return $this->buildSummaryFromSample($this->sampleData['applications']);
        }

        $sql = "SELECT status, COUNT(*) as total FROM applications GROUP BY status";
        $result = $this->connection->query($sql);
        $data = ['received' => 0, 'disposed' => 0, 'under_processing' => 0, 'rejected' => 0];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $status = strtolower($row['status']);
                if (str_contains($status, 'reject')) {
                    $data['rejected'] += (int) $row['total'];
                } elseif (str_contains($status, 'dispose') || str_contains($status, 'sanction')) {
                    $data['disposed'] += (int) $row['total'];
                } elseif (str_contains($status, 'under')) {
                    $data['under_processing'] += (int) $row['total'];
                } else {
                    $data['received'] += (int) $row['total'];
                }
            }
        }
        $data['received'] = max($data['received'], $this->getTotalApplications());
        return $data;
    }

    public function getDistrictSummary(string $district): array
    {
        if ($this->useFallback) {
            $apps = array_filter(
                $this->sampleData['applications'],
                fn ($app) => strcasecmp($app['district'], $district) === 0
            );
            return $this->buildSummaryFromSample($apps);
        }

        $stmt = $this->connection->prepare(
            "SELECT status, COUNT(*) as total FROM applications WHERE district = ? GROUP BY status"
        );
        $stmt->bind_param('s', $district);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = ['received' => 0, 'disposed' => 0, 'under_processing' => 0, 'rejected' => 0];
        while ($row = $result->fetch_assoc()) {
            $status = strtolower($row['status']);
            if (str_contains($status, 'reject')) {
                $data['rejected'] += (int) $row['total'];
            } elseif (str_contains($status, 'dispose') || str_contains($status, 'sanction')) {
                $data['disposed'] += (int) $row['total'];
            } elseif (str_contains($status, 'pending') || str_contains($status, 'under')) {
                $data['under_processing'] += (int) $row['total'];
            } else {
                $data['received'] += (int) $row['total'];
            }
        }
        $stmt->close();
        return $data;
    }

    public function getDistrictMonthlyTrend(string $district): array
    {
        if ($this->useFallback) {
            return $this->sampleData['district_monthly'];
        }

        $sql = "SELECT DATE_FORMAT(application_submission, '%Y-%m') as month,
                       COUNT(*) as received,
                       SUM(CASE WHEN status IN ('Disposed','Sanctioned') THEN 1 ELSE 0 END) as disposed,
                       SUM(CASE WHEN status LIKE 'Under%' OR status LIKE 'Pending%' THEN 1 ELSE 0 END) as under_processing
                FROM applications
                WHERE district = ?
                GROUP BY month
                ORDER BY month";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param('s', $district);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[$row['month']] = [
                'received' => (int) $row['received'],
                'disposed' => (int) $row['disposed'],
                'under_processing' => (int) $row['under_processing'],
            ];
        }
        $stmt->close();
        return $data;
    }

    public function getDistrictPendingBreakdown(string $district): array
    {
        if ($this->useFallback) {
            return $this->sampleData['district_pending_breakdown'];
        }

        $sql = "SELECT pending_with, COUNT(*) as total
                FROM applications
                WHERE district = ?
                GROUP BY pending_with";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param('s', $district);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[$row['pending_with'] ?: 'Unknown'] = (int) $row['total'];
        }
        $stmt->close();
        return $data;
    }

    public function getApplicationsByStatus(?string $district, string $status): array
    {
        if ($this->useFallback) {
            return array_values(array_filter($this->sampleData['applications'], function ($app) use ($district, $status) {
                $matchesDistrict = $district ? strcasecmp($app['district'], $district) === 0 : true;
                if (!$matchesDistrict) {
                    return false;
                }
                return strcasecmp($app['status'], $status) === 0
                    || ($status === 'Under Processing' && str_contains(strtolower($app['status']), 'pending'));
            }));
        }

        $sql = "SELECT * FROM applications WHERE (? IS NULL OR district = ?) AND status = ? ORDER BY last_updated DESC";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param('sss', $district, $district, $status);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
        return $data;
    }

    public function searchApplication(string $fileNo): ?array
    {
        if ($this->useFallback) {
            foreach ($this->sampleData['applications'] as $app) {
                if (strcasecmp($app['file_no'], $fileNo) === 0) {
                    return $app;
                }
            }
            return null;
        }

        $stmt = $this->connection->prepare("SELECT * FROM applications WHERE file_no = ? LIMIT 1");
        $stmt->bind_param('s', $fileNo);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc() ?: null;
        $stmt->close();
        return $row;
    }

    public function saveApplication(array $payload): bool
    {
        $payload['status'] = $payload['status'] ?? 'Under Processing';
        $payload['current_level'] = $payload['current_level'] ?? 'District';
        $payload['current_level_label'] = $payload['current_level_label'] ?? 'District Office';
        $payload['last_updated'] = date('Y-m-d');

        if ($this->useFallback) {
            $payload = $this->normalizePayload($payload);
            $runtimePath = __DIR__ . '/../data/runtime';
            if (!is_dir($runtimePath)) {
                mkdir($runtimePath, 0777, true);
            }
            $filename = $runtimePath . '/applications.json';
            $existing = [];
            if (file_exists($filename)) {
                $existing = json_decode(file_get_contents($filename), true) ?: [];
            }
            $existing[] = $payload;
            file_put_contents($filename, json_encode($existing, JSON_PRETTY_PRINT));
            return true;
        }

        $sql = "INSERT INTO applications
            (file_no, applicant_name, address, district, taluk, village, area, mineral_type,
             application_submission, application_type, file_created, proposed_scrutiny,
             latest_document, correction_letter, proposed_inspection, rectification_letter,
             approved_plan, forwarded_to_dmg, release_execution, first_permit,
             statutory_license_submitted, statutory_license_forwarded, directorate_received,
             directorate_scrutiny, directorate_rectification, loi_issued,
             statutory_license_issued, ql_order_issued, ql_validity, status,
             current_level, current_level_label, last_updated, pending_reason, pending_with)
            VALUES
            (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param(
            'sssssssssssssssssssssssssssssssssss',
            $payload['file_no'],
            $payload['applicant_name'],
            $payload['address'],
            $payload['district'],
            $payload['taluk'],
            $payload['village'],
            $payload['area'],
            $payload['mineral_type'],
            $payload['date_of_appln'],
            $payload['application_type'],
            $payload['date_file_created'],
            $payload['proposed_scrutiny'],
            $payload['latest_document'],
            $payload['correction_letter'],
            $payload['proposed_inspection'],
            $payload['rectification_letter'],
            $payload['approved_mining_plan'],
            $payload['forwarded_to_dmg'],
            $payload['release_execution'],
            $payload['first_movement_permit'],
            $payload['statutory_license_submitted'],
            $payload['statutory_license_forwarded'],
            $payload['directorate_received'],
            $payload['directorate_scrutiny'],
            $payload['directorate_rectification'],
            $payload['loi_issued'],
            $payload['statutory_license_issued'],
            $payload['ql_order_issued'],
            $payload['ql_validity'],
            $payload['status'],
            $payload['current_level'],
            $payload['current_level_label'],
            $payload['last_updated'],
            $payload['pending_reason'],
            $payload['pending_with']
        );
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function getDmgSummary(): array
    {
        if ($this->useFallback) {
            $apps = $this->sampleData['applications'];
            return $this->buildSummaryFromSample($apps);
        }

        $sql = "SELECT COUNT(*) AS total,
                       SUM(CASE WHEN status LIKE 'Under%' OR status LIKE 'Pending%' THEN 1 ELSE 0 END) AS under_processing,
                       SUM(CASE WHEN status LIKE 'Disposed' OR status LIKE 'Sanctioned' THEN 1 ELSE 0 END) AS disposed,
                       SUM(CASE WHEN status LIKE 'Rejected%' THEN 1 ELSE 0 END) AS rejected
                FROM applications";
        $result = $this->connection->query($sql);
        $row = $result->fetch_assoc();
        return [
            'received' => (int) $row['total'],
            'under_processing' => (int) $row['under_processing'],
            'disposed' => (int) $row['disposed'],
            'rejected' => (int) $row['rejected'],
        ];
    }

    public function getDmgMonthlyStats(): array
    {
        if ($this->useFallback) {
            return $this->sampleData['dmg_monthly'];
        }

        $sql = "SELECT DATE_FORMAT(application_submission, '%Y-%m') as month,
                       COUNT(*) as received,
                       SUM(CASE WHEN status LIKE 'Disposed' OR status LIKE 'Sanctioned' THEN 1 ELSE 0 END) as disposed,
                       SUM(CASE WHEN status LIKE 'Rejected%' THEN 1 ELSE 0 END) as rejected,
                       SUM(CASE WHEN status LIKE 'Sanctioned' THEN 1 ELSE 0 END) as sanctioned
                FROM applications
                GROUP BY month
                ORDER BY month";
        $result = $this->connection->query($sql);
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[$row['month']] = [
                'received' => (int) $row['received'],
                'disposed' => (int) $row['disposed'],
                'sanctioned' => (int) $row['sanctioned'],
                'rejected' => (int) $row['rejected'],
            ];
        }
        return $data;
    }

    public function getDmgDistrictComparison(): array
    {
        if ($this->useFallback) {
            return $this->sampleData['dmg_districts'];
        }

        $sql = "SELECT district,
                       COUNT(*) as received,
                       SUM(CASE WHEN status LIKE 'Under%' OR status LIKE 'Pending%' THEN 1 ELSE 0 END) as under_processing,
                       SUM(CASE WHEN status LIKE 'Disposed' OR status LIKE 'Sanctioned' THEN 1 ELSE 0 END) as disposed,
                       SUM(CASE WHEN status LIKE 'Sanctioned' THEN 1 ELSE 0 END) as sanctioned
                FROM applications
                GROUP BY district";
        $result = $this->connection->query($sql);
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[$row['district']] = [
                'received' => (int) $row['received'],
                'under_processing' => (int) $row['under_processing'],
                'disposed' => (int) $row['disposed'],
                'sanctioned' => (int) $row['sanctioned'],
            ];
        }
        return $data;
    }

    public function getAllApplications(): array
    {
        if ($this->useFallback) {
            return $this->sampleData['applications'];
        }

        $result = $this->connection->query("SELECT * FROM applications");
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    private function buildSummaryFromSample(array $apps): array
    {
        $summary = [
            'received' => count($apps),
            'disposed' => 0,
            'under_processing' => 0,
            'rejected' => 0,
        ];
        foreach ($apps as $app) {
            $status = strtolower($app['status']);
            if (str_contains($status, 'reject')) {
                $summary['rejected']++;
            } elseif (str_contains($status, 'dispose') || str_contains($status, 'sanction')) {
                $summary['disposed']++;
            } elseif (str_contains($status, 'pending') || str_contains($status, 'under')) {
                $summary['under_processing']++;
            }
        }
        return $summary;
    }

    private function getTotalApplications(): int
    {
        if ($this->useFallback) {
            return count($this->sampleData['applications']);
        }
        $result = $this->connection->query("SELECT COUNT(*) as total FROM applications");
        $row = $result->fetch_assoc();
        return (int) $row['total'];
    }

    private function normalizePayload(array $payload): array
    {
        $payload['applicant_name'] = $payload['applicant_name'] ?? ($payload['name'] ?? '');
        $payload['application_submission'] = $payload['application_submission']
            ?? ($payload['date_of_appln'] ?? null);
        $payload['file_created'] = $payload['file_created']
            ?? ($payload['date_file_created'] ?? null);
        $payload['approved_plan'] = $payload['approved_plan']
            ?? ($payload['approved_mining_plan'] ?? null);
        $payload['first_permit'] = $payload['first_permit']
            ?? ($payload['first_movement_permit'] ?? null);
        return $payload;
    }
}
