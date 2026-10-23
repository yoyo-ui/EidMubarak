<?php
/**
 * Diagnostic Tool: Controlled Memory Exhaustion Test
 * Purpose: Simulates an OOM condition to audit PHP engine behavior and logging.
 * Run this ONLY in a non-production development environment.
 */

// 1. Ensure errors are visible for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Starting memory allocation test...<br>";
echo "Current memory limit: " . ini_get('memory_limit') . "<br>";
echo "Initial memory usage: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB<br><br>";

// 2. Register a shutdown function to see if the runtime crashes or exits cleanly
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && $error['type'] === E_ERROR) {
        // In a vulnerable/unstable runtime under certain configurations, 
        // this shutdown handler might be completely bypassed by an abrupt bailout.
        log_diagnostic_message("Shutdown handler executed. Fatal Error Captured: " . $error['message']);
    } else {
        log_diagnostic_message("Shutdown handler executed normally.");
    }
});

function log_diagnostic_message($msg) {
    // Write to a local audit log file
    file_put_contents('audit_log.txt', "[" . date('Y-m-d H:i:s') . "] " . $msg . PHP_EOL, FILE_APPEND);
    echo "<br><b>[Audit Log]:</b> " . htmlspecialchars($msg) . "<br>";
}

// 3. Begin controlled allocation loop
$buffer = [];
$i = 0;

try {
    while (true) {
        $i++;
        // Allocate a 1MB block of string data inside an array
        $buffer[] = str_repeat('A', 1024 * 1024);
        
        // Print progress every 10MB to monitor stability
        if ($i % 10 === 0) {
            echo "Allocated: " . $i . " MB | Current Usage: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB<br>";
            flush();
        }
    }
} catch (Throwable $e) {
    // Modern PHP (7+) handles some allocations via Exceptions, 
    // but a true engine-level OOM bypasses try/catch blocks entirely.
    log_diagnostic_message("Caught exception: " . $e->getMessage());
}
