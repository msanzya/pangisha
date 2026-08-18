<?php
require __DIR__.'/../config/paths.php';
require __DIR__.'/../config/db.php';
require __DIR__.'/../config/session.php';
require __DIR__.'/../includes/auth.php';

// Verify admin or agent
if (!(isAdmin() || isAgent())) {
    header("Location: ".BASE_URL."login.php");
    exit;
}

// Get all maintenance issues with related information
$stmt = $db->query("
    SELECT mi.*,
           p.title as property_title
    FROM maintenance_issues mi
    JOIN properties p ON mi.property_id = p.id
    ORDER BY mi.id DESC
");
$issues = $stmt->fetchAll();

$pageTitle = "Maintenance Issues";
require_once __DIR__.'/../views/layouts/header.php';
?>

<section class="dashboard-section">
    <h2><i class="bi bi-tools"></i> Maintenance Issues</h2>
    <div class="btn-toolbar mb-3">
        <button type="button" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Report New Issue
        </button>
    </div>

    <div class="stats-grid mb-4">
        <!-- Reported Issues -->
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #EC7600;">
                <i class="bi bi-exclamation-circle"></i>
            </div>
            <div class="stat-content">
                <h3>Reported</h3>
                <div class="stat-breakdown">
                    <span><?= $db->query("SELECT COUNT(*) FROM maintenance_issues WHERE status='reported'")->fetchColumn() ?> issues</span>
                </div>
            </div>
        </div>

        <!-- In Progress Issues -->
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #0CC0DF;">
                <i class="bi bi-tools"></i>
            </div>
            <div class="stat-content">
                <h3>In Progress</h3>
                <div class="stat-breakdown">
                    <span><?= $db->query("SELECT COUNT(*) FROM maintenance_issues WHERE status='in_progress'")->fetchColumn() ?> issues</span>
                </div>
            </div>
        </div>

        <!-- Resolved Issues -->
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #0CC0DF;">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3>Resolved</h3>
                <div class="stat-breakdown">
                    <span><?= $db->query("SELECT COUNT(*) FROM maintenance_issues WHERE status='resolved'")->fetchColumn() ?> issues</span>
                </div>
            </div>
        </div>

        <!-- Closed Issues -->
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #EC7600;">
                <i class="bi bi-x-circle"></i>
            </div>
            <div class="stat-content">
                <h3>Closed</h3>
                <div class="stat-breakdown">
                    <span><?= $db->query("SELECT COUNT(*) FROM maintenance_issues WHERE status='closed'")->fetchColumn() ?> issues</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Property</th>
                            <th>Title</th>
                            <th>Reported By</th>
                            <th>Assigned To</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Reported On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($issues as $issue): ?>
                        <tr>
                            <td><?= $issue['id'] ?></td>
                            <td><?= htmlspecialchars($issue['property_title']) ?></td>
                            <td><?= htmlspecialchars($issue['title']) ?></td>
                            <td><?= htmlspecialchars($issue['reported_by_name']) ?></td>
                            <td><?= htmlspecialchars($issue['assigned_to_name'] ?? 'Unassigned') ?></td>
                            <td>
                                <?php if ($issue['priority'] == 'urgent'): ?>
                                    <span class="badge bg-danger">Urgent</span>
                                <?php elseif ($issue['priority'] == 'high'): ?>
                                    <span class="badge bg-warning">High</span>
                                <?php elseif ($issue['priority'] == 'medium'): ?>
                                    <span class="badge bg-info">Medium</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Low</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($issue['status'] == 'resolved'): ?>
                                    <span class="badge bg-success">Resolved</span>
                                <?php elseif ($issue['status'] == 'in_progress'): ?>
                                    <span class="badge bg-info">In Progress</span>
                                <?php elseif ($issue['status'] == 'assigned'): ?>
                                    <span class="badge bg-primary">Assigned</span>
                                <?php elseif ($issue['status'] == 'reported'): ?>
                                    <span class="badge bg-warning">Reported</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Closed</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M j, Y', strtotime($issue['created_at'])) ?></td>
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