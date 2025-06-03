<?php
// Include authentication check
include_once 'includes/auth_session.php';

$title = "Audit Trail";
include_once 'includes/header.php';
include_once 'includes/navigation.php';
require_once 'config/db_config.php';

// Check if user is admin
$isAdmin = false;
if (isset($_SESSION["role"]) && $_SESSION["role"] === 'admin') {
    $isAdmin = true;
}

if (!$isAdmin) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>Access denied. Admin privileges required.</div></div>";
    include_once 'includes/footer.php';
    exit;
}

// Set default timezone
date_default_timezone_set('Asia/Manila');

// Check if database exists
$result = mysqli_query($conn, "SHOW DATABASES LIKE '" . DB_NAME . "'");
$database_exists = mysqli_num_rows($result) > 0;

if (!$database_exists) {
    echo '<div class="container mt-4"><div class="alert alert-warning">Database doesn\'t exist yet. Please <a href="system_settings.php">initialize the database</a> first.</div></div>';
    include_once 'includes/footer.php';
    exit;
}

mysqli_select_db($conn, DB_NAME);

// Pagination setup
$records_per_page = 50;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

// Build search condition
$search_term = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$search_condition = '';

if (!empty($search_term)) {
    $search_condition = " AND (
        a.table_name LIKE '%$search_term%' OR 
        a.field_name LIKE '%$search_term%' OR 
        a.old_value LIKE '%$search_term%' OR 
        a.new_value LIKE '%$search_term%' OR 
        u.username LIKE '%$search_term%'
    )";
}

// Date range filter
if (!empty($_GET['date_from'])) {
    $date_from = mysqli_real_escape_string($conn, $_GET['date_from']);
    $search_condition .= " AND a.created_at >= '$date_from 00:00:00'";
}

if (!empty($_GET['date_to'])) {
    $date_to = mysqli_real_escape_string($conn, $_GET['date_to']);
    $search_condition .= " AND a.created_at <= '$date_to 23:59:59'";
}

// Action type filter
if (!empty($_GET['action_type'])) {
    $action_type = mysqli_real_escape_string($conn, $_GET['action_type']);
    $search_condition .= " AND a.action_type = '$action_type'";
}

// Table name filter
if (!empty($_GET['table_name'])) {
    $table_name = mysqli_real_escape_string($conn, $_GET['table_name']);
    $search_condition .= " AND a.table_name = '$table_name'";
}

// Username filter
if (!empty($_GET['username'])) {
    $username = mysqli_real_escape_string($conn, $_GET['username']);
    $search_condition .= " AND u.username = '$username'";
}

// Get total record count for pagination
$count_sql = "SELECT COUNT(*) as total FROM audit_trail a LEFT JOIN users u ON a.user_id = u.id WHERE 1=1 $search_condition";
$count_result = mysqli_query($conn, $count_sql);
$total_records = 0;
if ($count_result && $row = mysqli_fetch_assoc($count_result)) {
    $total_records = $row['total'];
}
$total_pages = ceil($total_records / $records_per_page);

// Get audit log data
$sql = "SELECT a.*, u.username 
        FROM audit_trail a 
        LEFT JOIN users u ON a.user_id = u.id 
        WHERE 1=1 $search_condition
        ORDER BY created_at DESC 
        LIMIT $offset, $records_per_page";

$result = mysqli_query($conn, $sql);
$audit_logs = [];
if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        $audit_logs[] = $row;
    }
}

// Get unique table names for filter dropdown
$tables_sql = "SELECT DISTINCT table_name FROM audit_trail ORDER BY table_name";
$tables_result = mysqli_query($conn, $tables_sql);
$table_names = [];
if ($tables_result) {
    while($row = mysqli_fetch_assoc($tables_result)) {
        $table_names[] = $row['table_name'];
    }
}

// Get unique usernames for filter dropdown
$users_sql = "SELECT DISTINCT u.username FROM audit_trail a LEFT JOIN users u ON a.user_id = u.id WHERE u.username IS NOT NULL ORDER BY u.username";
$users_result = mysqli_query($conn, $users_sql);
$usernames = [];
if ($users_result) {
    while($row = mysqli_fetch_assoc($users_result)) {
        $usernames[] = $row['username'];
    }
}
?>

<div class="container-fluid py-4">
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h1 class="mb-0"><i class="fas fa-history me-3 text-primary"></i>Audit Trail</h1>
        </div>
        <div class="col-md-6 d-flex justify-content-end">
            <form method="get" action="" class="d-flex me-2">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Search audit logs..." 
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                    <?php if (!empty($_GET['search'])): ?>
                    <a href="audit_trail.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </form>
            
            <a href="export_audit_pdf.php?<?php echo http_build_query($_GET); ?>" class="btn btn-secondary">
                <i class="fas fa-file-export me-1"></i> Export PDF
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <?php include 'includes/components/audit_filters.php'; ?>
            
            <?php if (empty($audit_logs)): ?>
                <div class="alert alert-info">No audit logs found.</div>
            <?php else: ?>
                <!-- Display active filters -->
                <?php
                $active_filters = [];
                if (!empty($_GET['search'])) $active_filters[] = 'Search: ' . htmlspecialchars($_GET['search']);
                if (!empty($_GET['date_from'])) $active_filters[] = 'From: ' . htmlspecialchars($_GET['date_from']);
                if (!empty($_GET['date_to'])) $active_filters[] = 'To: ' . htmlspecialchars($_GET['date_to']);
                if (!empty($_GET['action_type'])) $active_filters[] = 'Action Type: ' . htmlspecialchars($_GET['action_type']);
                if (!empty($_GET['table_name'])) $active_filters[] = 'Table: ' . htmlspecialchars($_GET['table_name']);
                if (!empty($_GET['username'])) $active_filters[] = 'User: ' . htmlspecialchars($_GET['username']);
                ?>
                
                <?php if (!empty($active_filters)): ?>
                <div class="mb-3">
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="text-muted me-2">Active filters:</span>
                        <?php foreach ($active_filters as $filter): ?>
                            <span class="badge bg-light text-dark"><?php echo $filter; ?></span>
                        <?php endforeach; ?>
                        <a href="audit_trail.php" class="btn btn-sm btn-outline-secondary">Clear all</a>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Action</th>
                                <th>Table</th>
                                <th>Record ID</th>
                                <th>Field</th>
                                <th style="min-width: 300px;">Old Value</th>
                                <th style="min-width: 300px;">New Value</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($audit_logs as $log): ?>
                                <tr>
                                    <td><?php echo date('M d, Y g:i:s A', strtotime($log['created_at'])); ?></td>
                                    <td>
                                        <?php if ($log['action_type'] == 'insert'): ?>
                                            <span class="badge bg-success">INSERT</span>
                                        <?php elseif ($log['action_type'] == 'update'): ?>
                                            <span class="badge bg-info">UPDATE</span>
                                        <?php elseif ($log['action_type'] == 'delete'): ?>
                                            <span class="badge bg-danger">DELETE</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['table_name']); ?></td>
                                    <td><?php echo $log['record_id'] ?? 'N/A'; ?></td>
                                    <td><?php echo htmlspecialchars($log['field_name'] ?? 'N/A'); ?></td>
                                    <td class="text-wrap" style="word-break: break-word;">
                                        <?php echo htmlspecialchars($log['old_value'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="text-wrap" style="word-break: break-word;">
                                        <?php echo htmlspecialchars($log['new_value'] ?? 'N/A'); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> entries
                    </div>
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <?php 
                            $queryParams = $_GET;
                            unset($queryParams['page']); // Remove page param
                            $queryString = http_build_query($queryParams);
                            $queryString = !empty($queryString) ? '&' . $queryString : '';
                            ?>
                            
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $queryString; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            
                            <?php 
                            $start_page = max(1, $page - 2);
                            $end_page = min($start_page + 4, $total_pages);
                            if ($end_page - $start_page < 4 && $start_page > 1) {
                                $start_page = max(1, $end_page - 4);
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++): 
                            ?>
                                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $queryString; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $queryString; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
