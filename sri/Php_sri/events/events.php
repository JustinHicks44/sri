<?php
require "../Lib/auth.php";
require "../Lib/db.php";

// Check if user is logged in
$user = auth_current_user();

// -----------------------------------------
// FETCH FILTER OPTIONS FROM DATABASE
// -----------------------------------------
// NOTE: Assumes Events table has a CategoryID or Category field
// If using a separate Categories table, adjust the query below

try {
    // DISTINCT categories - now joins to Categories table
    $categoryQuery = $pdo->query("
        SELECT DISTINCT Categories.Name 
        FROM Categories
        INNER JOIN Events ON Categories.CategoryID = Events.CategoryID
        WHERE Events.ApprovalStatus = 'Approved'
        ORDER BY Categories.Name
    ");
    $valid_categories = $categoryQuery->fetchAll(PDO::FETCH_COLUMN);
    
    // DISTINCT cities (from Locations)
    $locationQuery = $pdo->query("SELECT DISTINCT City FROM Locations ORDER BY City");
    $valid_locations = $locationQuery->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Database error fetching filters: " . $e->getMessage());
    $valid_categories = [];
    $valid_locations = [];
}

// -----------------------------------------
// SANITIZE & VALIDATE INPUTS
// -----------------------------------------
$category = $_GET['category'] ?? '';
$location = $_GET['location'] ?? '';
$date = $_GET['date'] ?? '';

// Validate category against whitelist
if ($category && !in_array($category, $valid_categories)) {
    $category = '';
}

// Validate location against whitelist
if ($location && !in_array($location, $valid_locations)) {
    $location = '';
}

// Validate date format (yyyy-mm-dd)
if ($date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = '';
}

$sort = $_GET['sort'] ?? 'date';
$allowed_sort = ['date', 'title'];
if (!in_array($sort, $allowed_sort)) $sort = 'date';

// Pagination
$page = isset($_GET['page']) && ctype_digit($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// -----------------------------------------
// BUILD SQL QUERY
// -----------------------------------------
$sql = "
    SELECT 
        Events.EventID AS id,
        Events.Title AS title,
        Events.EventDateTime AS datetime,
        Events.Description AS description,
        Categories.Name AS category,
        Locations.Address,
        Locations.City,
        Locations.State,
        Locations.PostalCode
    FROM Events
    JOIN Locations ON Events.LocationID = Locations.LocationID
    LEFT JOIN Categories ON Events.CategoryID = Categories.CategoryID
    WHERE Events.ApprovalStatus = 'Approved'
";

$params = [];

// Optional filters
if ($category !== '') {
    $sql .= " AND Categories.Name = ? ";
    $params[] = $category;
}

if ($location !== '') {
    $sql .= " AND Locations.City = ? ";
    $params[] = $location;
}

if ($date !== '') {
    $sql .= " AND DATE(Events.EventDateTime) = ? ";
    $params[] = $date;
}

// Sorting
if ($sort === 'title') {
    $sql .= " ORDER BY Events.Title ASC";
} else {
    $sql .= " ORDER BY Events.EventDateTime ASC";
}

// Get total count for pagination
$count_sql = "
    SELECT COUNT(*) FROM Events 
    JOIN Locations ON Events.LocationID = Locations.LocationID
    LEFT JOIN Categories ON Events.CategoryID = Categories.CategoryID
    WHERE Events.ApprovalStatus = 'Approved'
";

if ($category !== '') $count_sql .= " AND Categories.Name = ?";
if ($location !== '') $count_sql .= " AND Locations.City = ?";
if ($date !== '') $count_sql .= " AND DATE(Events.EventDateTime) = ?";

$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_events = $count_stmt->fetchColumn();
$total_pages = ceil($total_events / $per_page);

// Add pagination to main query
$sql .= " LIMIT " . intval($per_page) . " OFFSET " . intval($offset);

// Run query
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build filter summary
$filter_summary = [];
if ($category !== '') $filter_summary[] = "Category: " . htmlspecialchars($category);
if ($location !== '') $filter_summary[] = "Location: " . htmlspecialchars($location);
if ($date !== '') $filter_summary[] = "Date: " . htmlspecialchars($date);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Event Listings</title>
    <link rel="stylesheet" href="/Css_sri/global.css">
    <link rel="stylesheet" href="/Css_sri/event.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>

<?php include "../navbar.php"; ?>

<div class="container mt-2">

    <h1 class="mb-4">Browse Events</h1>

    <!-- FILTER FORM -->
    <form method="GET" class="row g-3 mb-4">

        <!-- Category -->
        <div class="col-12 col-sm-6 col-md-2">
            <select class="form-select" name="category">
                <option value="">Category</option>
                <?php foreach ($valid_categories as $c): ?>
                    <option value="<?= htmlspecialchars($c) ?>" 
                        <?= ($category === $c ? 'selected' : '') ?>>
                        <?= htmlspecialchars($c) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Location -->
        <div class="col-12 col-sm-6 col-md-2">
            <select class="form-select" name="location">
                <option value="">Location</option>
                <?php foreach ($valid_locations as $loc): ?>
                    <option value="<?= htmlspecialchars($loc) ?>" 
                        <?= ($location === $loc ? 'selected' : '') ?>>
                        <?= htmlspecialchars($loc) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Date -->
        <div class="col-12 col-sm-6 col-md-2">
            <input type="date" class="form-control" name="date" 
                value="<?= htmlspecialchars($date) ?>">
        </div>

        <!-- Sort -->
        <div class="col-12 col-sm-6 col-md-2">
            <select class="form-select" name="sort">
                <option value="date"  <?= $sort === 'date' ? 'selected' : '' ?>>Sort: Date</option>
                <option value="title" <?= $sort === 'title' ? 'selected' : '' ?>>Sort: Title</option>
            </select>
        </div>

        <!-- Filter Button -->
        <div class="col-12 col-sm-6 col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>

        <!-- Clear -->
        <div class="col-12 col-sm-6 col-md-2">
            <a href="events.php" class="btn btn-secondary w-100">Clear All</a>
        </div>

    </form>

    <div class="row">

        <?php if (empty($events)): ?>
            <div class="col-12">
                <div class="alert alert-info">
                    <strong>No events found.</strong>
                    <?php if (!empty($filter_summary)) echo "Matching: " . implode(", ", $filter_summary); ?>
                </div>
            </div>
        <?php else: ?>
            <div class="col-12 mb-3">
                <p class="text-muted">
                    Showing <strong><?= count($events) ?></strong> of <strong><?= $total_events ?></strong> events
                    <?php if (!empty($filter_summary)): ?>
                        (<?= implode(", ", $filter_summary) ?>)
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- EVENT CARDS -->
        <?php 
            $colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E2', '#F8B88B', '#A8D8EA'];
        ?>
        <?php foreach ($events as $event): ?>
        <?php $bgColor = $colors[crc32($event['id']) % count($colors)]; ?>
        <div class="col-12 col-sm-6 col-lg-4 mb-4">
            <div class="card shadow-sm h-100">

                <!-- Colored Box with Event Name -->
                <div class="text-center p-4 text-white d-flex align-items-center justify-content-center"
                    style="min-height: 200px; background: linear-gradient(135deg, <?= htmlspecialchars($bgColor) ?> 0%, <?= htmlspecialchars($bgColor) ?>dd 100%); font-weight: bold; font-size: 1.2rem;">
                    <?= htmlspecialchars($event['title']) ?>
                </div>

                <div class="card-body d-flex flex-column">

                    <h5 class="card-title"><?= htmlspecialchars($event['title']) ?></h5>

                    <p class="card-text small text-muted">
                        <strong>Date:</strong> <?= date("M d, Y h:i A", strtotime($event['datetime'])) ?><br>
                        <strong>Location:</strong><br>
                        <?= htmlspecialchars($event['Address']) ?>,
                        <?= htmlspecialchars($event['City']) ?>,
                        <?= htmlspecialchars($event['State']) ?>
                        <?= htmlspecialchars($event['PostalCode']) ?><br>
                        <strong>Category:</strong> <span class="badge bg-info"><?= htmlspecialchars($event['category'] ?? 'Uncategorized') ?></span>
                    </p>

                    <a class="btn btn-primary mt-auto"
                       href="event_details.php?id=<?= urlencode($event['id']) ?>">
                       View Details
                    </a>

                </div>

            </div>
        </div>
        <?php endforeach; ?>

    </div>

    <!-- PAGINATION -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Page navigation" class="mt-5">
        <ul class="pagination justify-content-center">
            <!-- Previous -->
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page - 1 ?><?= !empty($_GET['category']) ? '&category=' . urlencode($_GET['category']) : '' ?><?= !empty($_GET['location']) ? '&location=' . urlencode($_GET['location']) : '' ?><?= !empty($_GET['date']) ? '&date=' . urlencode($_GET['date']) : '' ?><?= !empty($_GET['sort']) ? '&sort=' . urlencode($_GET['sort']) : '' ?>">Previous</a>
                </li>
            <?php else: ?>
                <li class="page-item disabled">
                    <span class="page-link">Previous</span>
                </li>
            <?php endif; ?>

            <!-- Page numbers -->
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i === $page): ?>
                    <li class="page-item active">
                        <span class="page-link"><?= $i ?></span>
                    </li>
                <?php else: ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $i ?><?= !empty($_GET['category']) ? '&category=' . urlencode($_GET['category']) : '' ?><?= !empty($_GET['location']) ? '&location=' . urlencode($_GET['location']) : '' ?><?= !empty($_GET['date']) ? '&date=' . urlencode($_GET['date']) : '' ?><?= !empty($_GET['sort']) ? '&sort=' . urlencode($_GET['sort']) : '' ?>"><?= $i ?></a>
                    </li>
                <?php endif; ?>
            <?php endfor; ?>

            <!-- Next -->
            <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page + 1 ?><?= !empty($_GET['category']) ? '&category=' . urlencode($_GET['category']) : '' ?><?= !empty($_GET['location']) ? '&location=' . urlencode($_GET['location']) : '' ?><?= !empty($_GET['date']) ? '&date=' . urlencode($_GET['date']) : '' ?><?= !empty($_GET['sort']) ? '&sort=' . urlencode($_GET['sort']) : '' ?>">Next</a>
                </li>
            <?php else: ?>
                <li class="page-item disabled">
                    <span class="page-link">Next</span>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
