<?php
require __DIR__.'/../config/paths.php';
require __DIR__.'/../config/db.php';
require __DIR__.'/../config/session.php';
require __DIR__.'/../includes/auth.php';

// Verify admin
if (!isAdmin()) {
    header("Location: ".BASE_URL."login.php");
    exit;
}

// Get all users with their roles
$stmt = $db->query("
    SELECT u.*,
           CASE
               WHEN t.id IS NOT NULL THEN 'tenant'
               WHEN l.id IS NOT NULL THEN 'landlord'
               WHEN a.id IS NOT NULL THEN 'agent'
               WHEN tech.id IS NOT NULL THEN 'technician'
               ELSE u.role
           END as actual_role
    FROM users u
    LEFT JOIN tenants t ON u.id = t.user_id
    LEFT JOIN landlords l ON u.id = l.user_id
    LEFT JOIN agents a ON u.id = a.user_id
    LEFT JOIN technicians tech ON u.id = tech.user_id
    ORDER BY u.id DESC
");
$users = $stmt->fetchAll();

$pageTitle = "Users Management";
require_once __DIR__.'/../views/layouts/header.php';
?>

<section class="dashboard-section">
    <h2><i class="bi bi-people"></i> Users Management</h2>
    <div class="btn-toolbar mb-3">
        <a href="<?= BASE_URL ?>register.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add New User
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <span class="badge bg-primary"><?= ucfirst($user['actual_role']) ?></span>
                            </td>
                            <td>
                                <a href="#" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <a href="#" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?php
require_once __DIR__.'/../views/layouts/footer.php';
?>