<?php
// test_notif.php - One-click notification test
session_start();
require_once 'config/db.php';

echo "<h3>Testing Notifications</h3>";

// Add test notifications for user_id 2
$student_id = 2; // Abshir Adan

$messages = [
    "Your complaint #1 has been reviewed",
    "New comment on your complaint",
    "Reminder: Please update your profile",
    "System update completed successfully"
];

foreach ($messages as $message) {
    $status = rand(0, 1) ? 'unread' : 'read'; // Random status
    $sql = "INSERT INTO notifications (student_id, message, status) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $student_id, $message, $status);
    $stmt->execute();
}

echo "✅ Added 4 test notifications for student_id 2<br>";
echo "<a href='student/dashboard.php'>Go to Student Dashboard</a><br>";
echo "<a href='auth/login.php'>Login as abshir@gmail.com</a>";

// Show current notifications
echo "<h4>Current notifications:</h4>";
$result = $conn->query("SELECT * FROM notifications WHERE student_id = 2 ORDER BY created_at DESC");
while ($row = $result->fetch_assoc()) {
    echo "- " . $row['message'] . " (" . $row['status'] . ")<br>";
}
?>