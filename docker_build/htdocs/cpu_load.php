<?php
// Increase execution time limit to unlimited (if allowed by server config)
set_time_limit(0);

// Increase memory limit if needed (e.g., to 512MB or more)
ini_set('memory_limit', '512M');

echo "Starting continuous CPU and memory consumption...\n";
echo "Press stop or close the connection to abort (though background PHP processes may persist).\n<br>";

// Flush output immediately to the browser
while (ob_get_level() > 0) {
    ob_end_flush();
}
flush();

$memory_leak = [];
$counter = 0;

// Infinite loop to continuously consume resources
while (true) {
    // 1. CPU Consumption: Perform intensive mathematical calculations (sqrt/pow)
    for ($i = 0; $i < 100000; $i++) {
        sqrt($i * rand());
    }

    // 2. Memory Consumption: Append large strings/arrays to the array each cycle
    // This steadily increases RAM usage until it hits memory_limit
    $memory_leak[] = str_repeat(md5((string)$counter), 10000);
    
    $counter++;
    
    // Optional: Output progress every 1000 iterations so you see it running
    if ($counter % 1000 === 0) {
        echo "Iteration: $counter | Current Memory: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB<br>\n";
        flush();
    }
}
?>



