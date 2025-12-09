<?php
require '../Lib/auth.php';
require '../Lib/db.php';

// Require authentication and Organizer or Admin role
auth_require_role(['Organizer', 'Admin']);

// Get organizer ID from authenticated user
$user = auth_current_user();
$organizerID = $user['UserID'] ?? null;

if (!$organizerID) {
    header('Location: /sri/Admin/login.php');
    exit;
}

// ---------------------------------------
// SANITIZE FILTERS & SEARCH
// ---------------------------------------
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'date';
$page = isset($_GET['page']) && ctype_digit($_GET['page']) ? (int)$_GET['page'] : 1;

// Validate filters
$valid_statuses = ['Pending', 'Approved', 'Rejected'];
if ($status && !in_array($status, $valid_statuses)) {
    $status = '';
}
if ($search && strlen($search) > 255) {
    $search = '';
}

$valid_sorts = ['date', 'title', 'status'];
if (!in_array($sort, $valid_sorts)) {
    $sort = 'date';
}

$per_page = 10;
$offset = ($page - 1) * $per_page;

// ---------------------------------------
// BUILD QUERY WITH FILTERS
// ---------------------------------------
try {
    $sql = "
        SELECT 
            Events.EventID,
            Events.Title,
            Events.EventDateTime,
            Events.ApprovalStatus,
            Locations.City,
            Categories.Name AS CategoryName,
            (SELECT SUM(TotalCapacity) FROM TicketTypes WHERE TicketTypes.EventID = Events.EventID) AS TotalCapacity,
            (SELECT COUNT(*) FROM Tickets JOIN TicketTypes ON Tickets.TicketTypeID = TicketTypes.TicketTypeID WHERE TicketTypes.EventID = Events.EventID) AS TicketsSold
        FROM Events
        JOIN Locations ON Events.LocationID = Locations.LocationID
        LEFT JOIN Categories ON Events.CategoryID = Categories.CategoryID
        WHERE 1=1
    ";

    $params = [];

    // Apply filters
    if ($status !== '') {
        $sql .= " AND Events.ApprovalStatus = ?";
        $params[] = $status;
    }

    if ($search !== '') {
        $sql .= " AND Events.Title LIKE ?";
        $params[] = '%' . $search . '%';
    }

    // Apply sorting
    if ($sort === 'title') {
        $sql .= " ORDER BY Events.Title ASC";
    } elseif ($sort === 'status') {
        $sql .= " ORDER BY Events.ApprovalStatus ASC, Events.EventDateTime ASC";
    } else {
        $sql .= " ORDER BY Events.EventDateTime ASC";
    }

    // Get total count for pagination
    $count_sql = "
        SELECT COUNT(*) FROM Events
        WHERE 1=1
    ";
    if ($status !== '') $count_sql .= " AND Events.ApprovalStatus = ?";
    if ($search !== '') $count_sql .= " AND Events.Title LIKE ?";

    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_events = $count_stmt->fetchColumn();
    $total_pages = ceil($total_events / $per_page);

    // Add pagination
    $sql .= " LIMIT " . intval($per_page) . " OFFSET " . intval($offset);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Dashboard query error: " . $e->getMessage());
    $events = [];
    $total_events = 0;
    $total_pages = 0;
    $error_message = "Error loading events. Please try again later.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Organizer Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../Css_sri/global.css">
    <link rel="stylesheet" href="../Css_sri/organisers.css">
</head>

<body>

<?php include "../navbar.php"; ?>

<div class="container mt-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Your Events</h1>
        <a href="create_event.php" class="btn btn-primary">
            + Create New Event
        </a>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- SEARCH & FILTER FORM -->
    <form method="GET" class="row g-3 mb-4">

        <!-- Search -->
        <div class="col-12 col-md-4">
            <input type="text" class="form-control" name="search" placeholder="Search events..." 
                   value="<?= htmlspecialchars($search) ?>">
        </div>

        <!-- Status Filter -->
        <div class="col-12 col-sm-6 col-md-3">
            <select class="form-select" name="status">
                <option value="">All Status</option>
                <option value="Approved" <?= $status === 'Approved' ? 'selected' : '' ?>>Approved</option>
                <option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="Rejected" <?= $status === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
        </div>

        <!-- Sort -->
        <div class="col-12 col-sm-6 col-md-2">
            <select class="form-select" name="sort">
                <option value="date" <?= $sort === 'date' ? 'selected' : '' ?>>Sort: Date</option>
                <option value="title" <?= $sort === 'title' ? 'selected' : '' ?>>Sort: Title</option>
                <option value="status" <?= $sort === 'status' ? 'selected' : '' ?>>Sort: Status</option>
            </select>
        </div>

        <!-- Filter Button -->
        <div class="col-12 col-sm-6 col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>

        <!-- Clear -->
        <div class="col-12 col-sm-6 col-md-1">
            <a href="organiser_dashboard.php" class="btn btn-secondary w-100">Clear</a>
        </div>

    </form>

    <?php if (empty($events)): ?>
        <div class="alert alert-info text-center py-5" role="alert">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 16 16" class="mb-3">
                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
            </svg>
            <h4>No events found</h4>
            <p>
                <?php if ($search || $status): ?>
                    Try adjusting your filters or <a href="organiser_dashboard.php">view all events</a>.
                <?php else: ?>
                    You haven't created any events yet. <a href="create_event.php">Create your first event</a>.
                <?php endif; ?>
            </p>
        </div>
    <?php else: ?>

        <div class="mb-3">
            <p class="text-muted">
                Showing <strong><?= count($events) ?></strong> of <strong><?= $total_events ?></strong> events
            </p>
        </div>

        <!-- RESPONSIVE TABLE WRAPPER -->
        <div class="table-responsive shadow-sm">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Category</th>
                        <th class="d-none d-md-table-cell">City</th>
                        <th>Tickets</th>
                        <th>Status</th>
                        <th style="width: 200px;">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($events as $ev): ?>

                        <?php
                            $total = $ev["TotalCapacity"] ?? 0;
                            $sold  = $ev["TicketsSold"] ?? 0;
                            $available = max(0, $total - $sold);
                            $category = $ev["CategoryName"] ?? "Uncategorized";
                        ?>

                        <tr>
                            <td><strong><?= htmlspecialchars($ev["Title"]) ?></strong></td>

                            <td class="text-nowrap">
                                <?= date("M d, Y", strtotime($ev["EventDateTime"])) ?>
                            </td>

                            <td>
                                <span class="badge bg-info text-dark">
                                    <?= htmlspecialchars($category) ?>
                                </span>
                            </td>

                            <td class="d-none d-md-table-cell"><?= htmlspecialchars($ev["City"]) ?></td>

                            <td class="text-center">
                                <small>
                                    <span class="badge bg-success"><?= $sold ?></span> /
                                    <span class="badge bg-primary"><?= $total ?></span>
                                </small>
                            </td>

                            <td>
                                <?php if ($ev["ApprovalStatus"] === "Approved"): ?>
                                    <span class="badge bg-success">✓ Approved</span>
                                <?php elseif ($ev["ApprovalStatus"] === "Pending"): ?>
                                    <span class="badge bg-warning text-dark">⏳ Pending</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">✕ Rejected</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="view_event.php?id=<?= urlencode($ev['EventID']) ?>"
                                       class="btn btn-info" title="View">
                                        👁
                                    </a>
                                    <a href="edit_event.php?id=<?= urlencode($ev['EventID']) ?>"
                                       class="btn btn-warning" title="Edit">
                                        ✎
                                    </a>
                                    <a href="view_sales.php?id=<?= urlencode($ev['EventID']) ?>"
                                       class="btn btn-primary" title="Sales">
                                        📊
                                    </a>
                                    <a href="delete_event.php?id=<?= urlencode($ev['EventID']) ?>"
                                       class="btn btn-danger"
                                       onclick="return confirm('Delete &quot;<?= htmlspecialchars(addslashes($ev['Title'])) ?>&quot;? This cannot be undone.');"
                                       title="Delete">
                                        🗑
                                    </a>
                                </div>
                            </td>
                        </tr>

                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=1<?= $search ? '&search=' . urlencode($search) : '' ?><?= $status ? '&status=' . urlencode($status) : '' ?><?= $sort !== 'date' ? '&sort=' . urlencode($sort) : '' ?>">First</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $status ? '&status=' . urlencode($status) : '' ?><?= $sort !== 'date' ? '&sort=' . urlencode($sort) : '' ?>">← Previous</a>
                    </li>
                <?php else: ?>
                    <li class="page-item disabled"><span class="page-link">First</span></li>
                    <li class="page-item disabled"><span class="page-link">← Previous</span></li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i === $page): ?>
                        <li class="page-item active"><span class="page-link"><?= $i ?></span></li>
                    <?php else: ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $status ? '&status=' . urlencode($status) : '' ?><?= $sort !== 'date' ? '&sort=' . urlencode($sort) : '' ?>"><?= $i ?></a>
                        </li>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $status ? '&status=' . urlencode($status) : '' ?><?= $sort !== 'date' ? '&sort=' . urlencode($sort) : '' ?>">Next →</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $total_pages ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $status ? '&status=' . urlencode($status) : '' ?><?= $sort !== 'date' ? '&sort=' . urlencode($sort) : '' ?>">Last</a>
                    </li>
                <?php else: ?>
                    <li class="page-item disabled"><span class="page-link">Next →</span></li>
                    <li class="page-item disabled"><span class="page-link">Last</span></li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>

    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
