<?php
echo "<h2>Server Time Information</h2>";
echo "<p>Current Server Time (PHP): " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Default Timezone (PHP): " . date_default_timezone_get() . "</p>";

// Also try to get MySQL time
include 'config/db_connect.php';
$mysql_time_query = $conn->query("SELECT NOW() as mysql_now, @@global.time_zone as global_tz, @@session.time_zone as session_tz");
$mysql_time_result = $mysql_time_query->fetch_assoc();
echo "<p>Current MySQL Time: " . $mysql_time_result['mysql_now'] . "</p>";
echo "<p>MySQL Global Timezone: " . $mysql_time_result['global_tz'] . "</p>";
echo "<p>MySQL Session Timezone: " . $mysql_time_result['session_tz'] . "</p>";

$conn->close();
?>