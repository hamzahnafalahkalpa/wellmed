<?php

namespace App\Console\Commands;

use Hanafalah\MicroTenant\Facades\MicroTenant;
use Illuminate\Console\Command;
use Projects\WellmedBackbone\Services\DashboardMetricsService;

class TestDashboardMetrics extends Command
{
    protected $signature = 'dashboard:test-metrics
                            {--tenant= : Tenant ID (required)}
                            {--workspace= : Workspace ID (required)}
                            {--patients=5000 : Number of patients to seed for yesterday}
                            {--new-patients=1 : Number of new patients to add today (for new-patient test)}
                            {--period=daily : Period type (daily, weekly, monthly, yearly)}
                            {--step=all : Step to run (all, seed, delete, verify, cleanup, new-patient)}
                            {--cleanup : Cleanup test data after verification}';

    protected $description = 'Test dashboard metrics default behavior - verifies previous/current period comparison';

    protected DashboardMetricsService $service;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->service = app(DashboardMetricsService::class);

        $tenantId = $this->option('tenant');
        if (isset($tenantId)){
            $tenant_model = app(config('database.models.Tenant'))->findOrFail($tenantId);
            $workspaceId = $tenant_model->reference_id;
        }
        if (!isset($workspaceId)){
            $workspaceId = $this->option('workspace_id');
            if (!isset($tenantId)){
                $workspace_model = app('database.models.Workspace')->with('tenant')->findOrFail($workspaceId);
                $tenantId = $workspace_model->tenant->getKey();
            }
        }
        if (isset($tenantId)){
            MicroTenant::tenantImpersonate($tenantId);
        }
        $patientCount = (int) $this->option('patients');
        $newPatientsCount = (int) $this->option('new-patients');
        $periodType = $this->option('period');
        $step = $this->option('step');
        $shouldCleanup = $this->option('cleanup');

        // Validate required options
        if (!$tenantId || !$workspaceId) {
            $this->error('Both --tenant and --workspace options are required');
            $this->newLine();
            $this->info('Usage: php artisan dashboard:test-metrics --tenant=4 --workspace=your-uuid --patients=5000');
            return Command::FAILURE;
        }

        $tenantId = (int) $tenantId;

        $this->info('Dashboard Metrics Test');
        $this->info('======================');
        $this->table(
            ['Parameter', 'Value'],
            [
                ['Tenant ID', $tenantId],
                ['Workspace ID', $workspaceId],
                ['Patient Count', $patientCount],
                ['New Patients (for new-patient test)', $newPatientsCount],
                ['Period Type', $periodType],
                ['Step', $step],
            ]
        );
        $this->newLine();

        $result = match ($step) {
            'seed' => $this->runSeed($tenantId, $workspaceId, $patientCount, $periodType),
            'delete' => $this->runDelete($tenantId, $workspaceId, $periodType),
            'verify' => $this->runVerify($tenantId, $workspaceId, $patientCount, $periodType),
            'cleanup' => $this->runCleanup($tenantId, $workspaceId, $periodType),
            'new-patient' => $this->runNewPatientTest($tenantId, $workspaceId, $patientCount, $newPatientsCount, $periodType),
            default => $this->runAll($tenantId, $workspaceId, $patientCount, $periodType),
        };

        if ($shouldCleanup && $step !== 'cleanup') {
            $this->newLine();
            $this->runCleanup($tenantId, $workspaceId, $periodType);
        }

        return $result;
    }

    protected function runAll(int $tenantId, string $workspaceId, int $patientCount, string $periodType): int
    {
        $this->info('Running full test scenario...');
        $this->newLine();

        $result = $this->service->runDashboardMetricsTest($tenantId, $workspaceId, $patientCount, $periodType);

        // Display seed results
        $this->info('Step 1: Seed Yesterday Data');
        $seedResult = $result['steps']['seed_yesterday'] ?? [];
        if ($seedResult['success'] ?? false) {
            $this->info("  [OK] Seeded {$patientCount} patients for {$seedResult['date']}");
            $this->info("  Document ID: {$seedResult['document_id']}");
        } else {
            $this->error("  [FAIL] " . ($seedResult['error'] ?? 'Unknown error'));
        }
        $this->newLine();

        // Display delete results
        $this->info('Step 2: Delete Today Document');
        $deleteResult = $result['steps']['delete_today'] ?? [];
        if ($deleteResult['success'] ?? false) {
            if ($deleteResult['deleted'] ?? false) {
                $this->info("  [OK] Deleted document for {$deleteResult['date']}");
            } else {
                $this->info("  [OK] No document to delete (already clean)");
            }
        } else {
            $this->error("  [FAIL] " . ($deleteResult['error'] ?? 'Unknown error'));
        }
        $this->newLine();

        // Display verification results
        $this->info('Step 3: Verify Metrics');
        $verifyResult = $result['steps']['verify_metrics'] ?? [];
        $this->displayVerificationResults($verifyResult);

        $this->newLine();
        if ($result['success'] ?? false) {
            $this->info('[SUCCESS] All tests passed!');
            return Command::SUCCESS;
        } else {
            $this->error('[FAILED] Some tests failed. Check errors above.');
            return Command::FAILURE;
        }
    }

    protected function runSeed(int $tenantId, string $workspaceId, int $patientCount, string $periodType): int
    {
        $this->info('Seeding yesterday data...');

        $result = $this->service->seedTestDataYesterday($tenantId, $workspaceId, $patientCount, $periodType);

        if ($result['success'] ?? false) {
            $this->info("[OK] Seeded {$patientCount} patients");
            $this->table(
                ['Field', 'Value'],
                [
                    ['Date', $result['date'] ?? 'N/A'],
                    ['Patient Count', $result['patient_count'] ?? 'N/A'],
                    ['Document ID', $result['document_id'] ?? 'N/A'],
                ]
            );
            return Command::SUCCESS;
        }

        $this->error("[FAIL] " . ($result['error'] ?? 'Unknown error'));
        return Command::FAILURE;
    }

    protected function runDelete(int $tenantId, string $workspaceId, string $periodType): int
    {
        $this->info('Deleting today document...');

        $result = $this->service->deleteTodayDocument($tenantId, $workspaceId, $periodType);

        if ($result['success'] ?? false) {
            if ($result['deleted'] ?? false) {
                $this->info("[OK] Deleted document: {$result['document_id']}");
            } else {
                $this->info("[OK] No document to delete");
            }
            return Command::SUCCESS;
        }

        $this->error("[FAIL] " . ($result['error'] ?? 'Unknown error'));
        return Command::FAILURE;
    }

    protected function runVerify(int $tenantId, string $workspaceId, int $patientCount, string $periodType): int
    {
        $this->info('Verifying dashboard metrics...');
        $this->newLine();

        $result = $this->service->verifyDashboardMetrics($tenantId, $workspaceId, $patientCount, $periodType);

        $this->displayVerificationResults($result);

        return ($result['success'] ?? false) ? Command::SUCCESS : Command::FAILURE;
    }

    protected function runCleanup(int $tenantId, string $workspaceId, string $periodType): int
    {
        $this->info('Cleaning up test data...');

        $result = $this->service->cleanupTestData($tenantId, $workspaceId, $periodType);

        if ($result['success'] ?? false) {
            $deleted = $result['deleted'] ?? [];
            $this->info("[OK] Cleaned up " . count($deleted) . " document(s)");
            foreach ($deleted as $docId) {
                $this->line("  - {$docId}");
            }
            return Command::SUCCESS;
        }

        $this->error("[FAIL] Cleanup failed");
        return Command::FAILURE;
    }

    protected function runNewPatientTest(int $tenantId, string $workspaceId, int $patientCount, int $newPatientsCount, string $periodType): int
    {
        $this->info('Running New Patient Registration Test...');
        $this->info("Scenario: Yesterday has {$patientCount} patients, today adds {$newPatientsCount} new patient(s)");
        $this->newLine();

        $result = $this->service->runNewPatientTest($tenantId, $workspaceId, $patientCount, $newPatientsCount, $periodType);

        // Display seed results
        $this->info('Step 1: Seed Yesterday Data');
        $seedResult = $result['steps']['seed_yesterday'] ?? [];
        if ($seedResult['success'] ?? false) {
            $this->info("  [OK] Seeded {$patientCount} patients for {$seedResult['date']}");
        } else {
            $this->error("  [FAIL] " . ($seedResult['error'] ?? 'Unknown error'));
        }
        $this->newLine();

        // Display delete results
        $this->info('Step 2: Delete Today Document');
        $deleteResult = $result['steps']['delete_today'] ?? [];
        if ($deleteResult['success'] ?? false) {
            $this->info("  [OK] Today's document cleared");
        } else {
            $this->error("  [FAIL] " . ($deleteResult['error'] ?? 'Unknown error'));
        }
        $this->newLine();

        // Display add new patient results
        $this->info('Step 3: Simulate New Patient Registration');
        $addResult = $result['steps']['add_new_patient'] ?? [];
        if ($addResult['success'] ?? false) {
            $this->info("  [OK] Added {$newPatientsCount} new patient(s)");
            $this->info("  New total: {$addResult['new_total_patients']}");
        } else {
            $this->error("  [FAIL] " . ($addResult['error'] ?? 'Unknown error'));
        }
        $this->newLine();

        // Display verification results
        $this->info('Step 4: Verify Metrics');
        $verifyResult = $result['steps']['verify_metrics'] ?? [];
        $this->displayNewPatientVerificationResults($verifyResult);

        $this->newLine();
        if ($result['success'] ?? false) {
            $this->info('[SUCCESS] New Patient Test Passed!');
            return Command::SUCCESS;
        } else {
            $this->error('[FAILED] New Patient Test Failed. Check errors above.');
            return Command::FAILURE;
        }
    }

    protected function displayNewPatientVerificationResults(array $result): void
    {
        $checks = $result['checks'] ?? [];
        $errors = $result['errors'] ?? [];
        $summary = $result['summary'] ?? [];

        // Display checks table
        $this->info('Checks:');
        $checkRows = [];
        foreach ($checks as $key => $value) {
            $displayValue = is_bool($value) ? ($value ? 'Yes' : 'No') : (string) $value;
            $checkRows[] = [$key, $displayValue];
        }
        $this->table(['Check', 'Result'], $checkRows);

        // Display errors if any
        if (!empty($errors)) {
            $this->newLine();
            $this->error('Errors:');
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
        }

        // Display summary for patients and new-patients
        if (!empty($summary)) {
            $this->newLine();
            $this->info('Summary:');

            $patients = $summary['patients'] ?? [];
            $newPatients = $summary['new_patients'] ?? [];

            $this->table(
                ['Metric', 'Previous', 'Current', 'Expected Prev', 'Expected Curr'],
                [
                    [
                        'Total Patients',
                        $patients['previous'] ?? 'N/A',
                        $patients['current'] ?? 'N/A',
                        $patients['expected_previous'] ?? 'N/A',
                        $patients['expected_current'] ?? 'N/A',
                    ],
                    [
                        'New Patients',
                        $newPatients['previous'] ?? 'N/A',
                        $newPatients['current'] ?? 'N/A',
                        $newPatients['expected_previous'] ?? 'N/A',
                        $newPatients['expected_current'] ?? 'N/A',
                    ],
                ]
            );

            $this->newLine();
            $this->info('All Checks Passed: ' . (($summary['all_checks_passed'] ?? false) ? 'Yes' : 'No'));
        }
    }

    protected function displayVerificationResults(array $result): void
    {
        $checks = $result['checks'] ?? [];
        $errors = $result['errors'] ?? [];
        $summary = $result['summary'] ?? [];

        // Display checks
        $this->info('Checks:');
        $checkRows = [];
        foreach ($checks as $key => $value) {
            $displayValue = is_bool($value) ? ($value ? 'Yes' : 'No') : (string) $value;
            $checkRows[] = [$key, $displayValue];
        }
        $this->table(['Check', 'Result'], $checkRows);

        // Display errors if any
        if (!empty($errors)) {
            $this->newLine();
            $this->error('Errors:');
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
        }

        // Display summary
        if (!empty($summary)) {
            $this->newLine();
            $this->info('Summary:');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Yesterday Date', $summary['yesterday_date'] ?? 'N/A'],
                    ['Today Date', $summary['today_date'] ?? 'N/A'],
                    ['Expected Previous', $summary['expected_previous'] ?? 'N/A'],
                    ['Actual Previous', $summary['actual_previous'] ?? 'N/A'],
                    ['Actual Current', $summary['actual_current'] ?? 'N/A'],
                    ['Cumulative Behavior OK', ($summary['is_cumulative_behavior_correct'] ?? false) ? 'Yes' : 'No'],
                ]
            );
        }
    }
}
