<?php
require_once __DIR__ . '/../Php_sri/Lib/auth.php';
require_once __DIR__ . '/../Php_sri/Lib/db.php';

// Verify admin access
auth_require_role(['Admin']);

$type = 'users'; // Only manage users
$action = $_GET['action'] ?? 'index';
$tab = $_GET['tab'] ?? 'users'; // Track which tab is active

$error = '';
$success = '';

// ============ USERS MANAGEMENT ============
// Handle delete action for users
if ($action === 'delete' && !empty($_GET['id']) && $tab === 'users') {
    try {
        $stmt = $pdo->prepare('DELETE FROM Users WHERE UserID = ?');
        $stmt->execute([$_GET['id']]);
        $success = 'User deleted successfully!';
    } catch (PDOException $e) {
        $error = 'Delete failed: ' . $e->getMessage();
    }
}

// Fetch users from database
$items = [];
try {
    $stmt = $pdo->query('SELECT UserID as id, Username as username, Email as email, Role as role FROM Users ORDER BY UserID DESC');
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Query failed: ' . $e->getMessage();
}

// ============ EVENT APPROVAL ============
// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'events') {
    $event_action = $_POST['action'] ?? '';
    $event_id = (int)($_POST['event_id'] ?? 0);
    
    if ($event_action === 'approve' && $event_id) {
        try {
            $stmt = $pdo->prepare("UPDATE Events SET ApprovalStatus = 'Approved' WHERE EventID = ?");
            $stmt->execute([$event_id]);
            $success = "Event approved successfully!";
        } catch (PDOException $e) {
            error_log("Error approving event: " . $e->getMessage());
            $error = "Error approving event.";
        }
    } elseif ($event_action === 'reject' && $event_id) {
        try {
            $stmt = $pdo->prepare("UPDATE Events SET ApprovalStatus = 'Rejected' WHERE EventID = ?");
            $stmt->execute([$event_id]);
            $success = "Event rejected.";
        } catch (PDOException $e) {
            error_log("Error rejecting event: " . $e->getMessage());
            $error = "Error rejecting event.";
        }
    }
}

// Fetch pending events
$pending_events = [];
try {
    $pending_query = $pdo->query("
        SELECT 
            Events.EventID,
            Events.Title,
            Events.Description,
            Events.EventDateTime,
            Events.ApprovalStatus,
            Users.Username AS OrganizerName,
            Locations.Name AS LocationName,
            Locations.City,
            Locations.State,
            Categories.Name AS CategoryName
        FROM Events
        JOIN Users ON Events.OrganizerID = Users.UserID
        JOIN Locations ON Events.LocationID = Locations.LocationID
        LEFT JOIN Categories ON Events.CategoryID = Categories.CategoryID
        WHERE Events.ApprovalStatus IN ('Pending', 'Rejected')
        ORDER BY Events.EventDateTime ASC
    ");
    $pending_events = $pending_query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching pending events: " . $e->getMessage());
}

// Fetch approved events
$approved_events = [];
try {
    $approved_query = $pdo->query("
        SELECT 
            Events.EventID,
            Events.Title,
            Events.EventDateTime,
            Events.ApprovalStatus,
            Users.Username AS OrganizerName,
            Locations.Name AS LocationName
        FROM Events
        JOIN Users ON Events.OrganizerID = Users.UserID
        JOIN Locations ON Events.LocationID = Locations.LocationID
        WHERE Events.ApprovalStatus = 'Approved'
        ORDER BY Events.EventDateTime ASC
    ");
    $approved_events = $approved_query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching approved events: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Admin - Manage <?=htmlspecialchars($type)?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="../css/styles.css" rel="stylesheet" />
</head>
<body>
    <?php include __DIR__ . '/../Php_sri/navbar.php'; ?>

    <main class="container my-5">
        <h1 class="mb-4">Admin Panel</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?=htmlspecialchars($error)?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?=htmlspecialchars($success)?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- TAB NAVIGATION -->
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= $tab === 'users' ? 'active' : '' ?>" href="admin.php?tab=users">
                    Manage Users
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= $tab === 'events' ? 'active' : '' ?>" href="admin.php?tab=events">
                    Approve Events <span class="badge bg-warning text-dark"><?= count($pending_events) ?></span>
                </a>
            </li>
        </ul>

        <!-- USERS TAB -->
        <?php if ($tab === 'users'): ?>
        <div class="tab-pane active">
            <div class="mb-3">
                <a class="btn btn-primary" href="admin_edit.php">+ Create New User</a>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($items) === 0): ?>
                            <tr><td colspan="5" class="text-center text-muted">No users found.</td></tr>
                        <?php else: ?>
                            <?php foreach($items as $it): ?>
                                <tr>
                                    <td><?=htmlspecialchars($it['id'] ?? '')?></td>
                                    <td><strong><?=htmlspecialchars($it['username'] ?? '')?></strong></td>
                                    <td><?=htmlspecialchars($it['email'] ?? '')?></td>
                                    <td><span class="badge bg-info"><?=htmlspecialchars($it['role'] ?? 'N/A')?></span></td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-warning" href="admin_edit.php?id=<?=urlencode($it['id'] ?? '')?>">Edit</a>
                                        <a class="btn btn-sm btn-danger" href="admin.php?tab=users&action=delete&id=<?=urlencode($it['id'] ?? '')?>" onclick="return confirm('Delete this user?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- EVENTS TAB -->
        <?php if ($tab === 'events'): ?>
        <div class="tab-pane active">
            <h5 class="mb-4">Pending Events (<?= count($pending_events) ?>)</h5>

            <?php if (empty($pending_events)): ?>
                <p class="text-muted">No pending events.</p>
            <?php else: ?>
                <?php foreach ($pending_events as $event): ?>
                    <div class="card mb-3 border-<?= $event['ApprovalStatus'] === 'Rejected' ? 'danger' : 'warning' ?>">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h5 class="card-title"><?= htmlspecialchars($event['Title']) ?></h5>
                                    <span class="badge bg-<?= $event['ApprovalStatus'] === 'Pending' ? 'warning' : 'danger' ?>"><?= htmlspecialchars($event['ApprovalStatus']) ?></span>
                                    
                                    <p class="mb-2 mt-2">
                                        <strong>Organizer:</strong> <?= htmlspecialchars($event['OrganizerName']) ?>
                                    </p>
                                    <p class="mb-2">
                                        <strong>Category:</strong> <?= htmlspecialchars($event['CategoryName'] ?? 'N/A') ?>
                                    </p>
                                    <p class="mb-2">
                                        <strong>Location:</strong> <?= htmlspecialchars($event['LocationName']) ?>, <?= htmlspecialchars($event['City']) ?>, <?= htmlspecialchars($event['State']) ?>
                                    </p>
                                    <p class="mb-2">
                                        <strong>Date & Time:</strong> <?= date('M d, Y @ g:i A', strtotime($event['EventDateTime'])) ?>
                                    </p>
                                    <p class="mb-0">
                                        <strong>Description:</strong><br>
                                        <?= htmlspecialchars(substr($event['Description'], 0, 200)) ?>...
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <form method="post" class="d-flex flex-column gap-2">
                                        <input type="hidden" name="event_id" value="<?= $event['EventID'] ?>">
                                        <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">✓ Approve</button>
                                        <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">✗ Reject</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <hr class="my-5">

            <h5 class="mb-4">Approved Events (<?= count($approved_events) ?>)</h5>

            <?php if (empty($approved_events)): ?>
                <p class="text-muted">No approved events.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Event Title</th>
                                <th>Organizer</th>
                                <th>Location</th>
                                <th>Date & Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($approved_events as $event): ?>
                                <tr>
                                    <td><?= htmlspecialchars($event['Title']) ?></td>
                                    <td><?= htmlspecialchars($event['OrganizerName']) ?></td>
                                    <td><?= htmlspecialchars($event['LocationName']) ?></td>
                                    <td><?= date('M d, Y @ g:i A', strtotime($event['EventDateTime'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </main>

    <footer class="py-5 bg-dark">
        <div class="container px-4 px-lg-5">
            <p class="m-0 text-center text-white">Copyright &copy; MyTicket 2025</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
</body>
</html>
