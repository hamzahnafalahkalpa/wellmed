<?php

echo "Testing Queue Number Assignment\n";
echo "================================\n\n";

// Simulate the controller logic
$queue_number = null;
$queueService = null;
$esEnabled = true; // Simulate ES enabled

echo "1. Initial state:\n";
echo "   queue_number = " . var_export($queue_number, true) . "\n";
echo "   esEnabled = " . var_export($esEnabled, true) . "\n\n";

echo "2. Simulating reserve:\n";
if ($esEnabled) {
    try {
        // Simulate successful reserve
        $mockReservedNumber = 5; // This simulates what reserveNextQueueNumber() would return
        $queue_number = $mockReservedNumber;
        echo "   Reserved number: " . $queue_number . "\n";
    } catch (\Throwable $e) {
        echo "   ERROR: " . $e->getMessage() . "\n";
    }
}

echo "\n3. Creating visit_registration array:\n";
$visit_registration = [
    'id' => null,
    'status' => 'DRAFT',
    'queue_number' => $queue_number,
];
echo "   visit_registration['queue_number'] = " . var_export($visit_registration['queue_number'], true) . "\n";

echo "\n4. After store (simulating success):\n";
if ($queueService && $queue_number) {
    echo "   Would confirm queue number: " . $queue_number . "\n";
} else {
    echo "   PROBLEM DETECTED!\n";
    echo "   queueService = " . var_export($queueService, true) . "\n";
    echo "   queue_number = " . var_export($queue_number, true) . "\n";

    if ($queueService === null) {
        echo "   => Queue service was not initialized!\n";
    }
    if ($queue_number === null) {
        echo "   => Queue number is NULL (should not happen if reserve was successful)!\n";
    }
}

echo "\n================================\n";
echo "Issue Analysis:\n";
echo "If queue_number is NULL in database, possible causes:\n";
echo "1. elasticsearch.enabled config is false\n";
echo "2. Exception during service instantiation (app() call failed)\n";
echo "3. Exception during reserveNextQueueNumber() call (caught by outer try-catch)\n";
echo "4. Method reserveNextQueueNumber() itself threw exception before return\n";
echo "\nCheck logs for 'Failed to reserve queue number from ES' to confirm.\n";
