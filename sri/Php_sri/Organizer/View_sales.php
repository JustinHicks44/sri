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

// Verify event exists
$check = $pdo->prepare("
    SELECT Title FROM Events
    WHERE EventID = ?
");
$check->execute([$eventID]);
$event = $check->fetch(PDO::FETCH_ASSOC);

if (!$event) die("Event not found");

// Fetch sales
$sql = "
    SELECT 
        Tickets.TicketID,
        Tickets.UniqueBarcode,
        Tickets.PurchasePrice,
        TicketTypes.Name AS TicketTypeName,
        Orders.OrderDate,
        Users.Username AS CustomerName
    FROM Tickets
    JOIN TicketTypes ON Tickets.TicketTypeID = TicketTypes.TicketTypeID
    JOIN Orders ON Tickets.OrderID = Orders.OrderID
    JOIN Users ON Orders.CustomerID = Users.UserID
    WHERE TicketTypes.EventID = ?
    ORDER BY Orders.OrderDate DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$eventID]);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate revenue summary
$totalRevenue = 0;
$ticketCount = count($sales);
foreach ($sales as $sale) {
    $totalRevenue += $sale['PurchasePrice'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sales - <?= htmlspecialchars($event['Title']) ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../Css_sri/global.css">
    <link rel="stylesheet" href="../Css_sri/organisers.css">
</head>

<body>

<?php include "../navbar.php"; ?>

<div class="container mt-5">

    <h1>Sales for: <?= htmlspecialchars($event['Title']) ?></h1>
    <hr>

    <!-- SUMMARY CARDS -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Tickets Sold</h5>
                    <h2 class="text-primary"><?= $ticketCount ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Revenue</h5>
                    <h2 class="text-success">$<?= number_format($totalRevenue, 2) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-info">
                <div class="card-body text-center">
                    <h5 class="card-title">Average Price</h5>
                    <h2 class="text-info">$<?= $ticketCount > 0 ? number_format($totalRevenue / $ticketCount, 2) : '0.00' ?></h2>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($sales)): ?>
        <div class="alert alert-info">No ticket sales yet.</div>
    <?php else: ?>

        <table class="table table-striped table-bordered shadow-sm">
            <thead class="table-dark">
                <tr>
                    <th>Barcode</th>
                    <th>Ticket Type</th>
                    <th>Customer</th>
                    <th>Purchase Price</th>
                    <th>Order Date</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($sales as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['UniqueBarcode']) ?></td>
                    <td><?= htmlspecialchars($s['TicketTypeName']) ?></td>
                    <td><?= htmlspecialchars($s['CustomerName']) ?></td>
                    <td>$<?= number_format($s['PurchasePrice'], 2) ?></td>
                    <td><?= date("M d, Y h:i A", strtotime($s['OrderDate'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>

    <a href="organiser_dashboard.php" class="btn btn-secondary mt-3">← Back to Dashboard</a>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
