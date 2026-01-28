<?php
// TEST QUERY - Jalankan file ini untuk test query
require_once '../config.php';

echo "<h1>Testing Support Tickets Query</h1>";

// Test 1: Check if table exists
echo "<h2>Test 1: Check Table Exists</h2>";
$result = $conn->query("SHOW TABLES LIKE 'support_tickets'");
if ($result->num_rows > 0) {
    echo "✅ Table 'support_tickets' EXISTS<br>";
} else {
    echo "❌ Table 'support_tickets' NOT FOUND<br>";
    die("Please create the table first!");
}

// Test 2: Check table structure
echo "<h2>Test 2: Table Structure</h2>";
$result = $conn->query("DESCRIBE support_tickets");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "</tr>";
}
echo "</table><br>";

// Test 3: Count total records
echo "<h2>Test 3: Count Records</h2>";
$result = $conn->query("SELECT COUNT(*) as total FROM support_tickets");
$total = $result->fetch_assoc()['total'];
echo "Total records: <strong>$total</strong><br><br>";

// Test 4: Simple query (one by one)
echo "<h2>Test 4: Simple Count Queries</h2>";
echo "<pre>";

$queries = [
    "Total" => "SELECT COUNT(*) as cnt FROM support_tickets",
    "Open" => "SELECT COUNT(*) as cnt FROM support_tickets WHERE status = 'open'",
    "In Progress" => "SELECT COUNT(*) as cnt FROM support_tickets WHERE status = 'in_progress'",
    "Resolved" => "SELECT COUNT(*) as cnt FROM support_tickets WHERE status = 'resolved'",
    "Closed" => "SELECT COUNT(*) as cnt FROM support_tickets WHERE status = 'closed'",
    "High Priority" => "SELECT COUNT(*) as cnt FROM support_tickets WHERE prioritas = 'high'"
];

foreach ($queries as $label => $query) {
    $result = $conn->query($query);
    if ($result) {
        $count = $result->fetch_assoc()['cnt'];
        echo "$label: $count\n";
    } else {
        echo "$label: ERROR - " . $conn->error . "\n";
    }
}
echo "</pre>";

// Test 5: Complex query (the one causing issues)
echo "<h2>Test 5: Complex Query (Main Query)</h2>";

// SINGLE LINE VERSION - No line break issues
$stats_query = "SELECT COUNT(*) as total, SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_count, SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress, SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved, SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_count, SUM(CASE WHEN prioritas = 'high' THEN 1 ELSE 0 END) as high_priority FROM support_tickets";

echo "<strong>Query:</strong><br>";
echo "<pre>" . htmlspecialchars($stats_query) . "</pre>";

$result = $conn->query($stats_query);

if ($result) {
    echo "<strong style='color: green;'>✅ Query SUCCESS!</strong><br><br>";
    $stats = $result->fetch_assoc();
    echo "<strong>Results:</strong><br>";
    echo "<pre>";
    print_r($stats);
    echo "</pre>";
} else {
    echo "<strong style='color: red;'>❌ Query FAILED!</strong><br>";
    echo "Error: " . $conn->error . "<br>";
}

echo "<hr>";
echo "<p>If all tests pass, the query should work in reports.php!</p>";
?>