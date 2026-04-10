<?php

/**
 * Test script for Queue Service debugging
 * Run with: php artisan tinker < test-queue-service.php
 * Or: php test-queue-service.php (if running standalone)
 */

echo "=== Queue Service Debug Test ===\n\n";

// Check ES configuration
echo "1. Checking Elasticsearch Configuration:\n";
echo "   elasticsearch.enabled: " . (config('elasticsearch.enabled', false) ? 'true' : 'false') . "\n";
echo "   elasticsearch.hosts: " . config('elasticsearch.hosts', 'not set') . "\n";
echo "   elasticsearch.prefix: " . config('elasticsearch.prefix', 'not set') . "\n";
echo "\n";

// Check tenant
echo "2. Checking Tenant:\n";
try {
    $tenant = tenancy()->tenant;
    echo "   Tenant ID: " . $tenant->getKey() . "\n";
    echo "   Tenant Name: " . ($tenant->name ?? 'N/A') . "\n";
} catch (\Throwable $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// Test ES connection
echo "3. Testing Elasticsearch Connection:\n";
try {
    $client = app('elasticsearch');
    $info = $client->info();
    echo "   Connection: SUCCESS\n";
    echo "   Cluster Name: " . ($info['cluster_name'] ?? 'N/A') . "\n";
} catch (\Throwable $e) {
    echo "   Connection: FAILED\n";
    echo "   ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// Test Queue Service
echo "4. Testing Queue Service:\n";
try {
    $queueService = app(\Projects\WellmedBackbone\Services\VisitRegistrationQueueService::class);
    echo "   Service instantiated: SUCCESS\n";

    // Test getCurrentCount
    try {
        $currentCount = $queueService->getCurrentCount();
        echo "   Current Count: " . $currentCount . "\n";
    } catch (\Throwable $e) {
        echo "   getCurrentCount() FAILED: " . $e->getMessage() . "\n";
    }

    // Test reserveNextQueueNumber
    try {
        $reserved = $queueService->reserveNextQueueNumber();
        echo "   Reserved Number: " . $reserved . "\n";
        echo "   Reserved Type: " . gettype($reserved) . "\n";

        if ($reserved === null) {
            echo "   WARNING: Reserved number is NULL!\n";
        }
    } catch (\Throwable $e) {
        echo "   reserveNextQueueNumber() FAILED: " . $e->getMessage() . "\n";
        echo "   Stack trace:\n";
        echo "   " . $e->getTraceAsString() . "\n";
    }

    // Check count again (should still be the same since we only reserved)
    try {
        $currentCount2 = $queueService->getCurrentCount();
        echo "   Current Count After Reserve: " . $currentCount2 . "\n";
    } catch (\Throwable $e) {
        echo "   getCurrentCount() after reserve FAILED: " . $e->getMessage() . "\n";
    }

    // Test confirmQueueNumber
    try {
        $confirmed = $queueService->confirmQueueNumber();
        echo "   Confirmed: " . ($confirmed ? 'true' : 'false') . "\n";
    } catch (\Throwable $e) {
        echo "   confirmQueueNumber() FAILED: " . $e->getMessage() . "\n";
    }

    // Check final count (should be incremented)
    try {
        $finalCount = $queueService->getCurrentCount();
        echo "   Final Count After Confirm: " . $finalCount . "\n";
    } catch (\Throwable $e) {
        echo "   getCurrentCount() after confirm FAILED: " . $e->getMessage() . "\n";
    }

} catch (\Throwable $e) {
    echo "   Service instantiation FAILED: " . $e->getMessage() . "\n";
}
echo "\n";

echo "=== Test Complete ===\n";
