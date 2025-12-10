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
    die("Event not found or unauthorized");
}

// Fetch Locations
$locations = $pdo->query("
    SELECT LocationID, Name, City, State
    FROM Locations
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Categories
$categories = $pdo->query("
    SELECT CategoryID, Name, Description
    FROM Categories
")->fetchAll(PDO::FETCH_ASSOC);

// Handle Update
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST["title"]);
    $desc = trim($_POST["description"]);
    $categoryID = $_POST["category"];
    $locationID = $_POST["location"];
    $datetime = $_POST["date_time"];
    $duration = $_POST["duration"];

    if (!$title || !$datetime || !$locationID) {
        $message = "<div class='alert alert-danger'>Please fill required fields.</div>";
    } else {

        $updateSQL = "
            UPDATE Events
            SET Title = ?, Description = ?, CategoryID = ?, LocationID = ?, 
                EventDateTime = ?, DurationMinutes = ?
            WHERE EventID = ?
        ";

        $stmt = $pdo->prepare($updateSQL);
        $stmt->execute([
            $title, $desc, $categoryID, $locationID,
            $datetime, $duration,
            $eventID
        ]);

        header("Location: organiser_dashboard.php?updated=1");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Event</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../Css_sri/global.css">
    <link rel="stylesheet" href="../Css_sri/organisers.css">
</head>

<body>

<?php include "../navbar.php"; ?>

<div class="container mt-5">

    <h1>Edit Event</h1>
    <hr>

    <?= $message ?>

    <form method="POST" class="card p-4 shadow-sm">

        <div class="mb-3">
            <label class="form-label">Event Title *</label>
            <input type="text" name="title" class="form-control"
                   value="<?= htmlspecialchars($event['Title']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="4">
                <?= htmlspecialchars($event['Description']) ?>
            </textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Category</label>
            <select name="category" class="form-select">
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['CategoryID'] ?>"
                        <?= $cat['CategoryID'] == $event['CategoryID'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['Name']) ?> — <?= htmlspecialchars($cat['Description']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Location *</label>
            <select name="location" class="form-select" required>
                <?php foreach ($locations as $loc): ?>
                    <option value="<?= $loc['LocationID'] ?>"
                        <?= $loc['LocationID'] == $event['LocationID'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($loc['Name']) ?> — <?= htmlspecialchars($loc['City']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Date & Time *</label>
            <input type="datetime-local" name="date_time" 
                   class="form-control"
                   value="<?= date('Y-m-d\TH:i', strtotime($event['EventDateTime'])) ?>"
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label">Duration (minutes)</label>
            <input type="number" name="duration" class="form-control"
                   value="<?= $event['DurationMinutes'] ?>">
        </div>

        <button class="btn btn-warning">Update Event</button>
        <a href="organiser_dashboard.php" class="btn btn-secondary">Cancel</a>

    </form>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
