#!/usr/bin/env php
<?php

/**
 * Get Elasticsearch Data and Test Filters
 *
 * Usage: docker exec -it wellmed-backbone php /var/www/projects/wellmed/test-es-get-data.php
 */

require __DIR__ . '/bootstrap/app.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Elasticsearch Data Inspection & Filter Test ===\n\n";

try {
    // Get Elasticsearch client
    $client = app('elasticsearch');

    // Get VisitRegistration model to know the index name
    $modelClass = config('database.models.VisitRegistration');
    if (!$modelClass) {
        echo "ERROR: VisitRegistration model not configured\n";
        exit(1);
    }

    $model = new $modelClass();
    $indexName = $model->getElasticIndexName();

    echo "Model: $modelClass\n";
    echo "Index: $indexName\n";
    echo "ES Enabled: " . ($model->isElasticSearchEnabled() ? 'YES' : 'NO') . "\n\n";

    // Step 1: Get index info
    echo "=== Step 1: Index Information ===\n";
    try {
        $indexInfo = $client->indices()->get(['index' => $indexName]);
        echo "Index exists: YES\n";
        $mappings = $indexInfo[$indexName]['mappings']['properties'] ?? [];
        echo "Fields in index: " . count($mappings) . "\n";
        echo "Field names: " . implode(', ', array_keys($mappings)) . "\n\n";
    } catch (\Exception $e) {
        echo "Index error: " . $e->getMessage() . "\n\n";
    }

    // Step 2: Get total documents
    echo "=== Step 2: Total Documents ===\n";
    $countResponse = $client->count([
        'index' => $indexName
    ]);
    $totalDocs = $countResponse['count'] ?? 0;
    echo "Total documents: $totalDocs\n\n";

    if ($totalDocs == 0) {
        echo "WARNING: No documents in index! Cannot test filters.\n";
        exit(0);
    }

    // Step 3: Get sample documents
    echo "=== Step 3: Sample Documents (first 3) ===\n";
    $sampleResponse = $client->search([
        'index' => $indexName,
        'body' => [
            'query' => ['match_all' => new \stdClass()],
            'size' => 3
        ]
    ]);

    $hits = $sampleResponse['hits']['hits'] ?? [];
    foreach ($hits as $i => $hit) {
        echo "\nDocument " . ($i + 1) . ":\n";
        $source = $hit['_source'];
        echo "  ID: " . ($source['id'] ?? 'N/A') . "\n";
        echo "  Status: " . ($source['status'] ?? 'N/A') . "\n";
        echo "  Patient Name: " . ($source['patient_name'] ?? 'N/A') . "\n";
        echo "  Patient NIK: " . ($source['patient_nik'] ?? 'N/A') . "\n";
        echo "  Created At: " . ($source['created_at'] ?? 'N/A') . "\n";
        echo "  Available fields: " . implode(', ', array_keys($source)) . "\n";
    }
    echo "\n";

    // Step 4: Test status filter (should return results)
    echo "=== Step 4: Test Status Filter (COMPLETED) ===\n";
    $statusResponse = $client->search([
        'index' => $indexName,
        'body' => [
            'query' => [
                'term' => ['status' => 'COMPLETED']
            ],
            'size' => 1
        ]
    ]);
    $statusTotal = $statusResponse['hits']['total']['value'] ?? 0;
    echo "Documents with status=COMPLETED: $statusTotal\n\n";

    // Step 5: Test random string (should return 0)
    echo "=== Step 5: Test Random String Search (should be 0) ===\n";
    $randomString = 'Hamzahshdfsdfsdfsdadgdgadsgf';
    $randomResponse = $client->search([
        'index' => $indexName,
        'body' => [
            'query' => [
                'bool' => [
                    'should' => [
                        ['match' => ['patient_name' => $randomString]],
                        ['match' => ['patient_nik' => $randomString]],
                        ['match' => ['patient_id' => $randomString]],
                    ],
                    'minimum_should_match' => 1
                ]
            ],
            'size' => 1
        ]
    ]);
    $randomTotal = $randomResponse['hits']['total']['value'] ?? 0;
    echo "Documents matching random string: $randomTotal\n";
    echo "Expected: 0\n";
    if ($randomTotal > 0) {
        echo "⚠️  WARNING: Found matches for random string!\n";
        $randomHits = $randomResponse['hits']['hits'] ?? [];
        if (!empty($randomHits)) {
            $doc = $randomHits[0]['_source'];
            echo "Sample match:\n";
            echo "  Patient Name: " . ($doc['patient_name'] ?? 'N/A') . "\n";
            echo "  Patient NIK: " . ($doc['patient_nik'] ?? 'N/A') . "\n";
        }
    }
    echo "\n";

    // Step 6: Test HYBRID query (random string + status filter)
    echo "=== Step 6: Test HYBRID Query (random + status) ===\n";
    echo "Query: random string OR search + status=COMPLETED (AND)\n";
    $hybridResponse = $client->search([
        'index' => $indexName,
        'body' => [
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'bool' => [
                                'should' => [
                                    ['match' => ['patient_name' => $randomString]],
                                    ['match' => ['patient_nik' => $randomString]],
                                    ['match' => ['patient_id' => $randomString]],
                                ],
                                'minimum_should_match' => 1
                            ]
                        ],
                        [
                            'term' => ['status' => 'COMPLETED']
                        ]
                    ]
                ]
            ],
            'size' => 1
        ]
    ]);
    $hybridTotal = $hybridResponse['hits']['total']['value'] ?? 0;
    echo "Results: $hybridTotal\n";
    echo "Expected: 0 (because random string should match nothing)\n";
    if ($hybridTotal > 0) {
        echo "❌ BUG CONFIRMED: Hybrid query returned results when it shouldn't!\n";
    } else {
        echo "✅ Hybrid query working correctly\n";
    }
    echo "\n";

    // Step 7: Check what the application query produces
    echo "=== Step 7: Test Application Query ===\n";
    echo "Simulating: search_value=$randomString&search_status=COMPLETED\n\n";

    request()->replace([
        'search_value' => $randomString,
        'search_status' => 'COMPLETED',
        'page' => 1,
        'limit' => 10
    ]);

    $schemaClass = config('database.schemas.VisitRegistration');
    if (!$schemaClass) {
        echo "ERROR: VisitRegistration schema not configured\n";
        exit(1);
    }

    $schema = app($schemaClass);
    $result = $schema->conditionals(function($query) {
        // Empty
    })->viewVisitRegistrationPaginate();

    $appTotal = $result['meta']['total'] ?? count($result['data'] ?? []);
    echo "Application returned: $appTotal results\n";
    echo "Expected: 0\n";

    if ($appTotal > 0) {
        echo "❌ BUG: Application returned results when it shouldn't!\n";
        echo "\nFirst result:\n";
        $first = $result['data'][0] ?? null;
        if ($first) {
            echo "  Patient Name: " . ($first['patient_name'] ?? 'N/A') . "\n";
            echo "  Status: " . ($first['status'] ?? 'N/A') . "\n";
        }
    } else {
        echo "✅ Application working correctly\n";
    }

    echo "\n=== Check logs for detailed query ===\n";
    echo "docker exec -it wellmed-backbone tail -100 storage/logs/laravel.log | grep SearchDebug\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\nDone!\n";
