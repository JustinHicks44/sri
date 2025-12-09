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

// Fetch event
$sql = "
    SELECT * FROM Events
    WHERE EventID = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$eventID]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    die("Event not found");
}

// Fetch Location
$locStmt = $pdo->prepare("
    SELECT LocationID, Name, Address, City, State, PostalCode
    FROM Locations
    WHERE LocationID = ?
");
$locStmt->execute([$event['LocationID']]);
$location = $locStmt->fetch(PDO::FETCH_ASSOC);

// Fetch Category
$category = 'Uncategorized';
if ($event['CategoryID']) {
    $catStmt = $pdo->prepare("
        SELECT Name FROM Categories
        WHERE CategoryID = ?
    ");
    $catStmt->execute([$event['CategoryID']]);
    $catData = $catStmt->fetch(PDO::FETCH_ASSOC);
    if ($catData) {
        $category = $catData['Name'];
    }
}

// Fetch Organizer
$orgStmt = $pdo->prepare("
    SELECT Username FROM Users
    WHERE UserID = ?
");
$orgStmt->execute([$event['OrganizerID']]);
$organizer = $orgStmt->fetch(PDO::FETCH_ASSOC);

// Fetch Ticket Stats
$capStmt = $pdo->prepare("
    SELECT SUM(TotalCapacity) AS TotalCapacity
    FROM TicketTypes
    WHERE EventID = ?
");
$capStmt->execute([$eventID]);
$capacityData = $capStmt->fetch(PDO::FETCH_ASSOC);
$totalCapacity = $capacityData['TotalCapacity'] ?? 0;

$soldStmt = $pdo->prepare("
    SELECT COUNT(*) AS TicketsSold
    FROM Tickets
    JOIN TicketTypes ON Tickets.TicketTypeID = TicketTypes.TicketTypeID
    WHERE TicketTypes.EventID = ?
");
$soldStmt->execute([$eventID]);
$soldData = $soldStmt->fetch(PDO::FETCH_ASSOC);
$ticketsSold = $soldData['TicketsSold'] ?? 0;
$ticketsAvailable = max(0, $totalCapacity - $ticketsSold);
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Event - <?= htmlspecialchars($event['Title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../Css_sri/global.css">
    <link rel="stylesheet" href="../Css_sri/organisers.css">
</head>
<body>

<?php include "../navbar.php"; ?>

<div class="container mt-5 mb-5">

    <h1><?= htmlspecialchars($event['Title']) ?></h1>
    <hr>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Event Details</h5>
                    
                    <div class="mb-2">
                        <strong>Date & Time:</strong><br>
                        <?= date("M d, Y h:i A", strtotime($event['EventDateTime'])) ?>
                    </div>

                    <div class="mb-2">
                        <strong>Duration:</strong><br>
                        <?= $event['DurationMinutes'] ? $event['DurationMinutes'] . ' minutes' : 'Not specified' ?>
                    </div>

                    <div class="mb-2">
                        <strong>Category:</strong><br>
                        <span class="badge bg-info"><?= htmlspecialchars($category) ?></span>
                    </div>

                    <div class="mb-2">
                        <strong>Status:</strong><br>
                        <span class="badge bg-<?= $event['ApprovalStatus'] === 'Approved' ? 'success' : ($event['ApprovalStatus'] === 'Pending' ? 'warning' : 'danger') ?>">
                            <?= htmlspecialchars($event['ApprovalStatus']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Location</h5>
                    
                    <?php if ($location): ?>
                        <div class="mb-2">
                            <strong>Venue:</strong><br>
                            <?= htmlspecialchars($location['Name']) ?>
                        </div>

                        <div class="mb-2">
                            <strong>Address:</strong><br>
                            <?= htmlspecialchars($location['Address']) ?>
                        </div>

                        <div class="mb-2">
                            <strong>City, State ZIP:</strong><br>
                            <?= htmlspecialchars($location['City']) ?>, <?= htmlspecialchars($location['State']) ?> <?= htmlspecialchars($location['PostalCode']) ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Location information not available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Organizer</h5>
                    <?php if ($organizer): ?>
                        <p><?= htmlspecialchars($organizer['Username']) ?></p>
                    <?php else: ?>
                        <p class="text-muted">Organizer information not available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Ticket Information</h5>
                    
                    <div class="mb-2">
                        <strong>Total Capacity:</strong> <?= $totalCapacity ?>
                    </div>

                    <div class="mb-2">
                        <strong>Sold:</strong> <?= $ticketsSold ?>
                    </div>

                    <div class="mb-2">
                        <strong>Available:</strong> 
                        <span class="badge bg-<?= $ticketsAvailable > 0 ? 'success' : 'danger' ?>">
                            <?= $ticketsAvailable ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Description</h5>
                    <p><?= nl2br(htmlspecialchars($event['Description'])) ?: '<em class="text-muted">No description</em>' ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="btn-group" role="group">
        <a href="edit_event.php?id=<?= urlencode($eventID) ?>" class="btn btn-warning">Edit Event</a>
        <a href="organiser_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
