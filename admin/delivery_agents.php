<?php
include('conn.php');

function deliveryStatusLabel($rawStatus)
{
    $status = strtolower(trim((string)$rawStatus));
    if (in_array($status, ['assigned', 'out_for_delivery', 'out for delivery', 'out_for_order', 'out for order', 'shipped'], true)) {
        return 'Out for Delivery';
    }
    if ($status === '' || $status === 'pending') {
        return 'Pending';
    }
    return ucwords(str_replace('_', ' ', $status));
}

function ensureDeliveryPortalColumns($con)
{
    $createAgentTableSql = "CREATE TABLE IF NOT EXISTS delivery_agents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        agent_name VARCHAR(120) NOT NULL,
        username VARCHAR(80) NOT NULL UNIQUE,
        passward VARCHAR(255) NOT NULL,
        phone VARCHAR(30) DEFAULT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($con, $createAgentTableSql);

    $checkDeliveryAgentId = mysqli_query($con, "SHOW COLUMNS FROM purchase LIKE 'delivery_agent_id'");
    if (!$checkDeliveryAgentId || mysqli_num_rows($checkDeliveryAgentId) === 0) {
        mysqli_query($con, "ALTER TABLE purchase ADD COLUMN delivery_agent_id INT NULL");
    }

    $checkAssignedAt = mysqli_query($con, "SHOW COLUMNS FROM purchase LIKE 'assigned_at'");
    if (!$checkAssignedAt || mysqli_num_rows($checkAssignedAt) === 0) {
        mysqli_query($con, "ALTER TABLE purchase ADD COLUMN assigned_at DATETIME NULL");
    }

    $checkDeliveredAt = mysqli_query($con, "SHOW COLUMNS FROM purchase LIKE 'delivered_at'");
    if (!$checkDeliveredAt || mysqli_num_rows($checkDeliveredAt) === 0) {
        mysqli_query($con, "ALTER TABLE purchase ADD COLUMN delivered_at DATETIME NULL");
    }

    $checkCanceledAt = mysqli_query($con, "SHOW COLUMNS FROM purchase LIKE 'canceled_at'");
    if (!$checkCanceledAt || mysqli_num_rows($checkCanceledAt) === 0) {
        mysqli_query($con, "ALTER TABLE purchase ADD COLUMN canceled_at DATETIME NULL");
    }
}

ensureDeliveryPortalColumns($con);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_agent'])) {
    $agentName = trim($_POST['agent_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $passward = trim($_POST['passward'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($agentName === '' || $username === '' || $passward === '') {
        $error = 'Agent name, username, and password are required.';
    } else {
        $checkStmt = mysqli_prepare($con, "SELECT id FROM delivery_agents WHERE username=? LIMIT 1");
        if ($checkStmt) {
            mysqli_stmt_bind_param($checkStmt, 's', $username);
            mysqli_stmt_execute($checkStmt);
            $checkRes = mysqli_stmt_get_result($checkStmt);
            $exists = ($checkRes && mysqli_num_rows($checkRes) > 0);
            mysqli_stmt_close($checkStmt);

            if ($exists) {
                $error = 'Username already exists. Please choose another one.';
            } else {
                $stmt = mysqli_prepare($con, "INSERT INTO delivery_agents (agent_name, username, passward, phone, is_active) VALUES (?, ?, ?, ?, 1)");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'ssss', $agentName, $username, $passward, $phone);
                    if (mysqli_stmt_execute($stmt)) {
                        $success = 'Delivery agent created successfully.';
                    } else {
                        $error = 'Unable to create delivery agent.';
                    }
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_agent'])) {
    $agentId = (int)($_POST['agent_id'] ?? 0);
    $newState = (int)($_POST['new_state'] ?? 0);
    if ($agentId > 0) {
        $toggleStmt = mysqli_prepare($con, "UPDATE delivery_agents SET is_active=? WHERE id=?");
        if ($toggleStmt) {
            mysqli_stmt_bind_param($toggleStmt, 'ii', $newState, $agentId);
            mysqli_stmt_execute($toggleStmt);
            mysqli_stmt_close($toggleStmt);
            $success = 'Agent status updated.';
        }
    }
}

$agents = [];
$agentRes = mysqli_query($con, "SELECT * FROM delivery_agents ORDER BY id DESC");
if ($agentRes) {
    while ($row = mysqli_fetch_assoc($agentRes)) {
        $agents[] = $row;
    }
}

$activeAgents = [];
$activeAgentRes = mysqli_query($con, "SELECT id, agent_name, username FROM delivery_agents WHERE is_active=1 ORDER BY agent_name ASC");
if ($activeAgentRes) {
    while ($row = mysqli_fetch_assoc($activeAgentRes)) {
        $activeAgents[] = $row;
    }
}

include('header.php');
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Delivery Agent Management</h3>
        <a href="../delivery/login.php" class="btn btn-outline-primary btn-sm" target="_blank">Open Delivery Portal</a>
    </div>

    <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark text-white">Create Delivery Agent</div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Agent Name</label>
                            <input type="text" name="agent_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="text" name="passward" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone (Optional)</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <button class="btn btn-success w-100" name="add_agent" type="submit">Create Agent</button>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-secondary text-white">Agent Directory</div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Agent Name</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($agents) === 0): ?>
                                <tr><td colspan="3" class="text-center py-3">No agents available.</td></tr>
                            <?php else: ?>
                                <?php foreach ($agents as $agent): ?>
                                    <tr>
                                        <td>#<?php echo (int)$agent['id']; ?></td>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($agent['agent_name']); ?></div>
                                            <small class="text-muted">ID: <?php echo htmlspecialchars($agent['username']); ?></small>
                                        </td>
                                        <td>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="agent_id" value="<?php echo (int)$agent['id']; ?>">
                                                <input type="hidden" name="new_state" value="<?php echo ((int)$agent['is_active'] === 1) ? 0 : 1; ?>">
                                                <button class="btn btn-sm <?php echo ((int)$agent['is_active'] === 1) ? 'btn-outline-success' : 'btn-outline-danger'; ?>" name="toggle_agent" type="submit">
                                                    <?php echo ((int)$agent['is_active'] === 1) ? 'Active' : 'Inactive'; ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
