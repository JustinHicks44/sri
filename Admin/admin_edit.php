<?php
require_once __DIR__ . '/../Php_sri/Lib/auth.php';
require_once __DIR__ . '/../Php_sri/Lib/db.php';

// Verify admin access
auth_require_role(['Admin']);

$id = $_GET['id'] ?? '';
$item = null;
$error = null;

// Fetch existing user if editing
if ($id) {
    try {
        $stmt = $pdo->prepare('SELECT UserID as id, Username as username, Email as email, Role as role FROM Users WHERE UserID = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = 'Fetch failed: ' . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'Customer';
        
        if ($id) {
            // Update
            if ($password) {
                $stmt = $pdo->prepare('UPDATE Users SET Username = ?, Email = ?, PasswordHash = ?, Role = ? WHERE UserID = ?');
                $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $role, $id]);
            } else {
                $stmt = $pdo->prepare('UPDATE Users SET Username = ?, Email = ?, Role = ? WHERE UserID = ?');
                $stmt->execute([$username, $email, $role, $id]);
            }
        } else {
            // Create
            $stmt = $pdo->prepare('INSERT INTO Users (Username, Email, PasswordHash, Role, IsVerified) VALUES (?, ?, ?, ?, 1)');
            $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
        }
        
        header('Location: admin.php');
        exit;
    } catch (PDOException $e) {
        $error = 'Save failed: ' . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title><?= $id ? 'Edit' : 'Create' ?> <?=htmlspecialchars($type)?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="../css/styles.css" rel="stylesheet" />
</head>
<body>
    <?php include __DIR__ . '/../Php_sri/navbar.php'; ?>

    <main class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h1 class="mb-4"><?= $id ? 'Edit' : 'Create' ?> User</h1>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?=htmlspecialchars($error)?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input id="username" name="username" class="form-control" value="<?=htmlspecialchars($item['username'] ?? '')?>" required />
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input id="email" name="email" type="email" class="form-control" value="<?=htmlspecialchars($item['email'] ?? '')?>" required />
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password <?= $id ? '(leave blank to keep)' : '' ?></label>
                        <input id="password" name="password" type="password" class="form-control" <?= !$id ? 'required' : '' ?> />
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select id="role" name="role" class="form-select">
                            <option value="Customer" <?=isset($item['role']) && $item['role']==='Customer'?'selected':''?>>Customer</option>
                            <option value="Organizer" <?=isset($item['role']) && $item['role']==='Organizer'?'selected':''?>>Organizer</option>
                            <option value="Admin" <?=isset($item['role']) && $item['role']==='Admin'?'selected':''?>>Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary">Save</button>
                        <a class="btn btn-secondary" href="admin.php">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
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
