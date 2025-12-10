<?php
require "../Lib/auth.php";
require "../Lib/db.php";

// Check if user is logged in
$user = auth_current_user();

// ---------------------------------------------------------
// VALIDATE EVENT ID
// ---------------------------------------------------------
$id = $_GET['id'] ?? null;

if (!$id || !ctype_digit($id)) {
    http_response_code(404);
    $event = null;
} else {

    try {
        // ---------------------------------------------------------
        // FETCH EVENT + CATEGORY + LOCATION + ORGANIZER
        // ---------------------------------------------------------
        $sql = "
        SELECT 
            Events.EventID,
            Events.Title,
            Events.Description,
            Events.EventDateTime,
            Users.Username AS OrganizerName,
            Locations.Address,
            Locations.City,
            Locations.State,
            Locations.PostalCode,
            Categories.Name AS CategoryName
        FROM Events
        JOIN Users ON Events.OrganizerID = Users.UserID
        JOIN Locations ON Events.LocationID = Locations.LocationID
        LEFT JOIN Categories ON Events.CategoryID = Categories.CategoryID
        WHERE Events.EventID = ? AND Events.ApprovalStatus = 'Approved'
    ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($event) {

            // If category is null, fallback
            if (!$event['CategoryName']) {
                $event['CategoryName'] = "Uncategorized";
            }

            // ---------------------------------------------------------
            // FETCH ALL TICKET TYPES WITH SOLD COUNT
            // ---------------------------------------------------------
            $ticketStmt = $pdo->prepare("
                SELECT 
                    TicketTypes.TicketTypeID,
                    TicketTypes.Name,
                    TicketTypes.Price,
                    TicketTypes.TotalCapacity,
                    COALESCE(COUNT(Tickets.TicketID), 0) AS TicketsSold
                FROM TicketTypes
                LEFT JOIN Tickets ON TicketTypes.TicketTypeID = Tickets.TicketTypeID
                WHERE TicketTypes.EventID = ?
                GROUP BY TicketTypes.TicketTypeID, TicketTypes.Name, TicketTypes.Price, TicketTypes.TotalCapacity
                ORDER BY TicketTypes.TicketTypeID ASC
            ");
            $ticketStmt->execute([$id]);
            $ticketTypes = $ticketStmt->fetchAll(PDO::FETCH_ASSOC);

            // ---------------------------------------------------------
            // CALCULATE TOTALS
            // ---------------------------------------------------------
            $totalCapacity = 0;
            $ticketsSold = 0;
            $startingPrice = PHP_FLOAT_MAX;

            foreach ($ticketTypes as $ticket) {
                $totalCapacity += $ticket['TotalCapacity'];
                $ticketsSold += $ticket['TicketsSold'];
                $startingPrice = min($startingPrice, $ticket['Price']);
            }

            if ($startingPrice === PHP_FLOAT_MAX) {
                $startingPrice = 0;
            }

            // Remaining tickets
            $ticketsAvailable = max(0, $totalCapacity - $ticketsSold);
        }
    } catch (PDOException $e) {
        error_log("Database error fetching event details: " . $e->getMessage());
        $event = null;
    }
}

// ---------------------------------------------------------
// HANDLE TICKET PURCHASE
// ---------------------------------------------------------
$purchase_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_tickets'])) {
    if (!$user) {
        $purchase_message = "<div class='alert alert-danger'>Please log in to purchase tickets.</div>";
    } else {
        $ticket_selections = $_POST['ticket_selections'] ?? [];
        $total_amount = 0;
        $ticket_data = [];

        // Validate and calculate total
        foreach ($ticket_selections as $ticket_type_id => $quantity) {
            $quantity = (int)$quantity;
            if ($quantity > 0) {
                $ticketInfo = null;
                foreach ($ticketTypes as $tt) {
                    if ($tt['TicketTypeID'] == $ticket_type_id) {
                        $ticketInfo = $tt;
                        break;
                    }
                }

                if (!$ticketInfo) {
                    $purchase_message = "<div class='alert alert-danger'>Invalid ticket type selected.</div>";
                    break;
                }

                $available = $ticketInfo['TotalCapacity'] - $ticketInfo['TicketsSold'];
                if ($quantity > $available) {
                    $purchase_message = "<div class='alert alert-danger'>Not enough tickets available for {$ticketInfo['Name']}. Only {$available} available.</div>";
                    break;
                }

                $ticket_data[] = [
                    'ticket_type_id' => $ticket_type_id,
                    'quantity' => $quantity,
                    'price' => $ticketInfo['Price'],
                    'name' => $ticketInfo['Name']
                ];

                $total_amount += $quantity * $ticketInfo['Price'];
            }
        }

        if ($purchase_message === '' && empty($ticket_data)) {
            $purchase_message = "<div class='alert alert-warning'>Please select at least one ticket.</div>";
        }

        // Create order and tickets if validation passed
        if ($purchase_message === '' && !empty($ticket_data)) {
            try {
                $pdo->beginTransaction();

                // Create order
                $orderStmt = $pdo->prepare("
                    INSERT INTO Orders (CustomerID, OrderDate, TotalAmount, Status)
                    VALUES (?, NOW(), ?, 'Paid')
                ");
                $orderStmt->execute([$user['UserID'], $total_amount]);
                $orderID = $pdo->lastInsertId();

                // Create individual tickets for each purchase
                foreach ($ticket_data as $td) {
                    for ($i = 0; $i < $td['quantity']; $i++) {
                        $barcode = bin2hex(random_bytes(16));
                        $ticketInsertStmt = $pdo->prepare("
                            INSERT INTO Tickets (OrderID, TicketTypeID, UniqueBarcode, PurchasePrice)
                            VALUES (?, ?, ?, ?)
                        ");
                        $ticketInsertStmt->execute([
                            $orderID,
                            $td['ticket_type_id'],
                            $barcode,
                            $td['price']
                        ]);
                    }
                }

                $pdo->commit();
                $purchase_message = "<div class='alert alert-success'>Tickets purchased successfully! Order ID: {$orderID}. Total: $" . number_format($total_amount, 2) . "</div>";

                // Refresh ticket data
                $ticketStmt = $pdo->prepare("
                    SELECT 
                        TicketTypes.TicketTypeID,
                        TicketTypes.Name,
                        TicketTypes.Price,
                        TicketTypes.TotalCapacity,
                        COALESCE(COUNT(Tickets.TicketID), 0) AS TicketsSold
                    FROM TicketTypes
                    LEFT JOIN Tickets ON TicketTypes.TicketTypeID = Tickets.TicketTypeID
                    WHERE TicketTypes.EventID = ?
                    GROUP BY TicketTypes.TicketTypeID, TicketTypes.Name, TicketTypes.Price, TicketTypes.TotalCapacity
                    ORDER BY TicketTypes.TicketTypeID ASC
                ");
                $ticketStmt->execute([$id]);
                $ticketTypes = $ticketStmt->fetchAll(PDO::FETCH_ASSOC);

                // Recalculate totals
                $ticketsSold = 0;
                foreach ($ticketTypes as $ticket) {
                    $ticketsSold += $ticket['TicketsSold'];
                }
                $ticketsAvailable = max(0, $totalCapacity - $ticketsSold);
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Error creating order: " . $e->getMessage());
                $purchase_message = "<div class='alert alert-danger'>Error processing purchase. Please try again.</div>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>
        <?= $event ? htmlspecialchars($event['Title']) : "Event Not Found" ?>
    </title>

    <link rel="stylesheet" href="/Css_sri/global.css">
    <link rel="stylesheet" href="/Css_sri/event.css">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>

<?php include "../navbar.php"; ?>

<?php
// Generate consistent color for this event based on event ID
$colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E2', '#F8B88B', '#A8D8EA'];
$bgColor = $colors[crc32($id) % count($colors)];
?>

<div class="container mt-5 mb-5">
    <!-- Colored Header with Event Name -->
    <div class="text-white p-5 rounded mb-4 d-flex align-items-center justify-content-center text-center"
        style="background: linear-gradient(135deg, <?= htmlspecialchars($bgColor) ?> 0%, <?= htmlspecialchars($bgColor) ?>dd 100%); min-height: 200px; font-weight: bold; font-size: 2rem;">
        <?= htmlspecialchars($event['Title']) ?>
    </div>

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="events.php">Events</a></li>
            <li class="breadcrumb-item active">
                <?= $event ? htmlspecialchars($event['Title']) : "Event Not Found" ?>
            </li>
        </ol>
    </nav>

    <?php if ($purchase_message): ?>
        <?= $purchase_message ?>
    <?php endif; ?>

    <?php if (!$event): ?>

        <!-- ERROR STATE -->
        <div class="alert alert-danger">
            <h4>Event Not Found</h4>
            <a href="../events.php" class="btn btn-primary">← Back to Events</a>
        </div>

    <?php else: ?>

        <!-- DETAILS -->
        <div class="row mb-4">

            <!-- LEFT -->
            <div class="col-md-6">
                <div class="card mb-3" style="background-color: #F8F9FA; border: 1px solid #E0E0E0;">
                    <div class="card-body">

                        <h5 class="card-title">Event Information</h5>

                        <div class="mb-3">
                            <strong>Date & Time:</strong><br>
                            <?= date("M d, Y h:i A", strtotime($event['EventDateTime'])) ?>
                        </div>

                        <div class="mb-3">
                            <strong>Location:</strong><br>
                            <?= htmlspecialchars($event['Address']) ?>,
                            <?= htmlspecialchars($event['City']) ?>,
                            <?= htmlspecialchars($event['State']) ?>
                            <?= htmlspecialchars($event['PostalCode']) ?>
                        </div>

                        <div class="mb-3">
                            <strong>Organizer:</strong><br>
                            <?= htmlspecialchars($event['OrganizerName']) ?>
                        </div>

                    </div>
                </div>
            </div>

            <!-- RIGHT -->
            <div class="col-md-6">
                <div class="card mb-3" style="background-color: #E3F2FD; border: 2px solid #2196F3;">
                    <div class="card-body">

                        <h5 class="card-title">Ticket Information</h5>

                        <div class="mb-3">
                            <strong>Starting Price:</strong><br>
                            <span class="h4 text-primary">
                                $<?= number_format($startingPrice, 2) ?>
                            </span>
                        </div>

                        <div class="mb-3">
                            <strong>Tickets Available:</strong><br>

                            <?php if ($ticketsAvailable > 0): ?>
                                <span class="badge bg-success">
                                    <?= $ticketsAvailable ?> Available
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger">Sold Out</span>
                            <?php endif; ?>
                        </div>

                        <?php if ($ticketsAvailable > 0): ?>
                            <form method="post" class="mb-3">
                                <h6 class="mt-4 mb-3">Select Tickets:</h6>

                                <?php foreach ($ticketTypes as $ticket): ?>
                                    <?php 
                                        $available = $ticket['TotalCapacity'] - $ticket['TicketsSold'];
                                    ?>
                                    <div class="mb-3 p-2 border rounded bg-light">
                                        <div class="row align-items-center">
                                            <div class="col-md-6">
                                                <strong><?= htmlspecialchars($ticket['Name']) ?></strong><br>
                                                <span class="text-primary font-weight-bold">$<?= number_format($ticket['Price'], 2) ?></span><br>
                                                <small class="text-muted">
                                                    <?= $available ?> / <?= $ticket['TotalCapacity'] ?> available
                                                </small>
                                            </div>
                                            <div class="col-md-6">
                                                <input type="number" 
                                                       name="ticket_selections[<?= $ticket['TicketTypeID'] ?>]"
                                                       class="form-control" 
                                                       min="0" 
                                                       max="<?= $available ?>"
                                                       value="0"
                                                       placeholder="Qty">
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <button type="submit" name="buy_tickets" class="btn btn-success w-100">
                                    Complete Purchase
                                </button>
                            </form>
                        <?php else: ?>
                            <button class="btn btn-secondary w-100" disabled>
                                Sold Out
                            </button>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

        </div>

        <!-- DESCRIPTION -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card" style="background-color: #F8F9FA; border: 1px solid #E0E0E0;">
                    <div class="card-body">
                        <h5>Description</h5>
                        <p><?= nl2br(htmlspecialchars($event['Description'])) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- BACK -->
        <a href="events.php" class="btn btn-outline-primary">← Back to Events</a>

    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
