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

// Get all contracts with related information
$stmt = $db->query("
    SELECT rc.*,
           p.title as property_title,
           l_u.name as landlord_name,
           t_u.name as tenant_name,
           rc.status as contract_status
    FROM rent_contracts rc
    JOIN properties p ON rc.property_id = p.id
    JOIN users l_u ON p.landlord_id = l_u.id
    JOIN tenants t ON rc.tenant_id = t.id
    JOIN users t_u ON t.user_id = t_u.id
    ORDER BY rc.id DESC
");
$contracts = $stmt->fetchAll();

$pageTitle = "Contracts Management";
require_once __DIR__.'/../views/layouts/header.php';
?>

<section class="dashboard-section">
    <h2><i class="bi bi-file-text"></i> Contracts Management</h2>
    <div class="btn-toolbar mb-3">
        <button type="button" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create New Contract
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Property</th>
                            <th>Landlord</th>
                            <th>Tenant</th>
                            <th>Rent (TZS)</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contracts as $contract): ?>
                        <tr>
                            <td><?= $contract['id'] ?></td>
                            <td><?= htmlspecialchars($contract['property_title']) ?></td>
                            <td><?= htmlspecialchars($contract['landlord_name']) ?></td>
                            <td><?= htmlspecialchars($contract['tenant_name']) ?></td>
                            <td>TZS <?= number_format($contract['monthly_rent'], 2) ?></td>
                            <td><?= date('M j, Y', strtotime($contract['start_date'])) ?></td>
                            <td><?= date('M j, Y', strtotime($contract['end_date'])) ?></td>
                            <td>
                                <?php if ($contract['contract_status'] == 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php elseif ($contract['contract_status'] == 'expired'): ?>
                                    <span class="badge bg-secondary">Expired</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Terminated</span>
                                <?php endif; ?>
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