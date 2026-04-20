#!/usr/bin/env php
<?php

/**
 * Search Debug Test Script
 *
 * Usage from Docker:
 * docker exec -it wellmed-backbone php /var/www/projects/wellmed/test-search-debug.php
 *
 * This script tests the search functionality with debug logging
 */

require __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\Log;

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Search Debug Test ===\n\n";

// Test parameters
$testParams = [
    'search_value' => 'Hamzahshdfsdfsdfsdadgdgadsgf',
    'search_status' => 'COMPLETED',
    'page' => 1,
    'limit' => 10
];

echo "Test Parameters:\n";
echo json_encode($testParams, JSON_PRETTY_PRINT) . "\n\n";

// Set request parameters
request()->replace($testParams);

try {
    // Get VisitRegistration model
    $modelClass = config('database.models.VisitRegistration');
    if (!$modelClass) {
        echo "ERROR: VisitRegistration model not configured\n";
        exit(1);
    }

    echo "Using model: $modelClass\n\n";

    $model = new $modelClass();

    // Check ES config
    echo "Elasticsearch Configuration:\n";
    echo "- Global enabled: " . (config('elasticsearch.enabled', false) ? 'YES' : 'NO') . "\n";
    echo "- Model has isElasticSearchEnabled: " . (method_exists($model, 'isElasticSearchEnabled') ? 'YES' : 'NO') . "\n";
    if (method_exists($model, 'isElasticSearchEnabled')) {
        echo "- Model ES enabled: " . ($model->isElasticSearchEnabled() ? 'YES' : 'NO') . "\n";
    }
    echo "- Model casts: " . json_encode(array_keys($model->getCasts())) . "\n\n";

    // Get schema
    $schemaClass = config('database.schemas.VisitRegistration');
    if (!$schemaClass) {
        echo "ERROR: VisitRegistration schema not configured\n";
        exit(1);
    }

    echo "Using schema: $schemaClass\n\n";

    $schema = app($schemaClass);

    echo "Executing query...\n\n";

    // Execute query
    $result = $schema->conditionals(function($query) {
        // Empty conditional
    })->viewVisitRegistrationPaginate();

    echo "\n=== RESULT ===\n";
    echo "Total results: " . ($result['meta']['total'] ?? count($result['data'] ?? [])) . "\n";
    echo "Returned: " . count($result['data'] ?? []) . "\n";

    if (!empty($result['data'])) {
        echo "\nFirst result:\n";
        echo json_encode($result['data'][0] ?? [], JSON_PRETTY_PRINT) . "\n";
    }

    echo "\n=== Check laravel.log for detailed debugging ===\n";
    echo "docker exec -it wellmed-backbone tail -f storage/logs/laravel.log | grep SearchDebug\n\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\nDone!\n";
