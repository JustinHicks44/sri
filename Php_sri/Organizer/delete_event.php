<?php
require "../Lib/auth.php";
require "../Lib/db.php";

// Require authentication and Organizer or Admin role
auth_require_role(['Organizer', 'Admin']);

// Get organizer ID from authenticated user
$user = auth_current_user();
$organizerID = $user['UserID'] ?? null;

if (!$organizerID) {
    header('Location: /sri/Admin/login.php');
    exit;
}

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    die("Invalid ID");
}

$eventID = $_GET['id'];

// Delete
$sql = "
    DELETE FROM Events
    WHERE EventID = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$eventID]);

header("Location: organiser_dashboard.php?deleted=1");
exit;
