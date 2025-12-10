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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch Locations & Categories with error handling
$locations = [];
$categories = [];
$message = "";
try {
    $locStmt = $pdo->query("SELECT LocationID, Name, Address, City, State, PostalCode FROM Locations ORDER BY Name");
    $locations = $locStmt->fetchAll(PDO::FETCH_ASSOC);

    $catStmt = $pdo->query("SELECT CategoryID, Name, Description FROM Categories ORDER BY Name ASC");
    $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('DB error fetching locations/categories: ' . $e->getMessage());
    $message = "<div class='alert alert-danger'>Error loading form data. Try again later.</div>";
}

// Handle Form Submission
// Preserve old values on error
$old = ['title' => '', 'description' => '', 'category' => '', 'location' => '', 'date_time' => '', 'duration' => ''];
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // CSRF check
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $message = "<div class='alert alert-danger'>Invalid form submission (CSRF).</div>";
    } else {
        $title = trim($_POST["title"] ?? '');
        $desc = trim($_POST["description"] ?? '');
        $categoryID = $_POST["category"] ?? '';
        $locationID = $_POST["location"] ?? '';
        $datetime = $_POST["date_time"] ?? '';
        $duration = $_POST["duration"] ?? '';

        $old = ['title' => $title, 'description' => $desc, 'category' => $categoryID, 'location' => $locationID, 'date_time' => $datetime, 'duration' => $duration];

        // Basic required checks
        if ($title === '' || $locationID === '' || $datetime === '') {
            $message = "<div class='alert alert-danger'>Title, Location, and Date/Time are required.</div>";
        }

        // Whitelist checks for category and location
        $valid_cat_ids = array_column($categories, 'CategoryID');
        $valid_loc_ids = array_column($locations, 'LocationID');

        if ($categoryID !== '' && !in_array($categoryID, $valid_cat_ids)) {
            $message = "<div class='alert alert-danger'>Invalid category selected.</div>";
        }
        if (!in_array($locationID, $valid_loc_ids)) {
            $message = "<div class='alert alert-danger'>Invalid location selected.</div>";
        }

        // Validate datetime-local format (accepts with or without seconds)
        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $datetime);
        if (!$dt) {
            $dt = DateTime::createFromFormat('Y-m-d\TH:i:s', $datetime);
        }
        if (!$dt) {
            $message = "<div class='alert alert-danger'>Invalid date/time format.</div>";
        }

        // Duration
        if ($duration === '') {
            $duration = null;
        } else {
            $duration = (int)$duration;
            if ($duration < 0) {
                $message = "<div class='alert alert-danger'>Duration must be 0 or greater.</div>";
            }
        }

        // If no errors so far, insert event and ticket types
        if ($message === "") {
            try {
                $sql = "
                    INSERT INTO Events (
                        OrganizerID, LocationID, CategoryID, Title, Description,
                        EventDateTime, DurationMinutes, ApprovalStatus
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')
                ";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $organizerID,
                    $locationID,
                    $categoryID !== '' ? $categoryID : null,
                    $title,
                    $desc,
                    $dt->format('Y-m-d H:i:s'),
                    $duration
                ]);

                $eventID = $pdo->lastInsertId();

                // Insert ticket types if provided
                $ticketNames = $_POST['ticket_names'] ?? [];
                $ticketPrices = $_POST['ticket_prices'] ?? [];
                $ticketCapacities = $_POST['ticket_capacities'] ?? [];

                // Track if at least one valid ticket was created
                $validTicketCreated = false;

                for ($i = 0; $i < count($ticketNames); $i++) {
                    $tName = trim($ticketNames[$i] ?? '');
                    $tPrice = floatval($ticketPrices[$i] ?? 0);
                    $tCapacity = intval($ticketCapacities[$i] ?? 0);

                    if ($tName && $tPrice > 0 && $tCapacity > 0) {
                        $ticketStmt = $pdo->prepare("
                            INSERT INTO TicketTypes (EventID, Name, Price, TotalCapacity)
                            VALUES (?, ?, ?, ?)
                        ");
                        $ticketStmt->execute([$eventID, $tName, $tPrice, $tCapacity]);
                        $validTicketCreated = true;
                    }
                }

                // If no valid tickets were created, delete event and show error
                if (!$validTicketCreated) {
                    $pdo->prepare("DELETE FROM Events WHERE EventID = ?")->execute([$eventID]);
                    $message = "<div class='alert alert-danger'>At least one valid ticket type is required.</div>";
                } else {
                    header("Location: organiser_dashboard.php?created=1");
                    exit;
                }
            } catch (PDOException $e) {
                error_log('DB error inserting event: ' . $e->getMessage());
                $message = "<div class='alert alert-danger'>Unable to create event. Please try again later.</div>";
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Event</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../Css_sri/global.css" rel="stylesheet">
    <link href="../Css_sri/event.css" rel="stylesheet">
</head>
<body>

<?php include "../navbar.php"; ?>

<div class="container py-4">
    <h1>Create Event</h1>

    <?= $message ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <div class="mb-3">
            <label class="form-label">Title *</label>
            <input name="title" class="form-control" required value="<?= htmlspecialchars($old['title']) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control"><?= htmlspecialchars($old['description']) ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Category</label>
            <select name="category" class="form-select">
                <option value="">— None —</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['CategoryID'] ?>" <?= ($old['category'] == $cat['CategoryID']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['Name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Location *</label>
            <select name="location" class="form-select" required>
                <option value="">— Select —</option>
                <?php foreach ($locations as $loc): ?>
                    <option value="<?= $loc['LocationID'] ?>" <?= ($old['location'] == $loc['LocationID']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($loc['Name']) ?> — <?= htmlspecialchars($loc['City']) ?>, <?= htmlspecialchars($loc['State']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Date & Time *</label>
            <input type="datetime-local" name="date_time" class="form-control" required value="<?= htmlspecialchars($old['date_time']) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Duration (minutes)</label>
            <input type="number" name="duration" class="form-control" min="0" value="<?= htmlspecialchars($old['duration']) ?>">
        </div>

        <hr>
        <h5>Ticket Types</h5>

        <div id="ticketTypesContainer">
            <div class="ticketTypeRow mb-3 p-3 border rounded bg-light">
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Ticket Name</label>
                        <input type="text" name="ticket_names[]" class="form-control" placeholder="e.g., General Admission" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Price ($)</label>
                        <input type="number" name="ticket_prices[]" class="form-control" step="0.01" min="0" placeholder="0.00" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Capacity</label>
                        <input type="number" name="ticket_capacities[]" class="form-control" min="1" placeholder="e.g., 100" required>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-danger mt-2 removeTicketBtn" style="display:none;">Remove</button>
            </div>
        </div>

        <button type="button" class="btn btn-sm btn-secondary mb-3" onclick="addTicketType()">+ Add Another Ticket Type</button>

        <button class="btn btn-primary">Create Event</button>
        <a href="organiser_dashboard.php" class="btn btn-secondary">Cancel</a>
    </form>

</div>

<script>
function addTicketType() {
    const container = document.getElementById('ticketTypesContainer');
    const newRow = document.querySelector('.ticketTypeRow').cloneNode(true);
    
    // Clear input values
    newRow.querySelectorAll('input').forEach(inp => inp.value = '');
    
    // Show remove button
    newRow.querySelector('.removeTicketBtn').style.display = '';
    newRow.querySelector('.removeTicketBtn').onclick = function() {
        this.closest('.ticketTypeRow').remove();
        updateRemoveButtons();
    };
    
    container.appendChild(newRow);
    updateRemoveButtons();
}

function updateRemoveButtons() {
    const rows = document.querySelectorAll('.ticketTypeRow');
    rows.forEach((row, idx) => {
        const btn = row.querySelector('.removeTicketBtn');
        btn.style.display = rows.length > 1 ? '' : 'none';
    });
}

document.addEventListener('DOMContentLoaded', updateRemoveButtons);
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
