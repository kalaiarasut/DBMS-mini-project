<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a manager
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'manager') {
    header("Location: ../index.php");
    exit();
}

// Handle status update
if (isset($_POST['order_id'], $_POST['new_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['new_status'];
    $allowed = ['pending', 'approved', 'declined', 'shipped', 'delivered', 'cancelled'];
    if (in_array($new_status, $allowed)) {
        $update_sql = "UPDATE orders SET status = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "si", $new_status, $order_id);
        mysqli_stmt_execute($update_stmt);
    }
}

// Handle search and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

$where = [];
$params = [];
$types = '';
if ($search !== '') {
    $where[] = "(o.id = ? OR u.name LIKE ? OR u.email LIKE ?)";
    $params[] = $search;
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'iss';
}
if ($status_filter !== '') {
    $where[] = "o.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}
if ($date_from !== '') {
    $where[] = "DATE(o.created_at) >= ?";
    $params[] = $date_from;
    $types .= 's';
}
if ($date_to !== '') {
    $where[] = "DATE(o.created_at) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

// Pagination setup
$orders_per_page = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $orders_per_page;

// Count total filtered orders
$count_sql = "SELECT COUNT(*) as total FROM orders o JOIN users u ON o.user_id = u.id";
if (!empty($where)) {
    $count_sql .= " WHERE " . implode(' AND ', $where);
}
$count_stmt = mysqli_prepare($conn, $count_sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
}
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total_orders = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_orders / $orders_per_page);

// Main query with LIMIT
$sql = "SELECT o.*, u.name as customer_name, u.email FROM orders o JOIN users u ON o.user_id = u.id";
if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY o.created_at DESC LIMIT $orders_per_page OFFSET $offset";
$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Fetch order items for each order
$order_items = [];
$order_ids = [];
$orders = [];
while ($order = mysqli_fetch_assoc($result)) {
    $order_ids[] = $order['id'];
    $orders[] = $order;
}
$order_items_map = [];
if (!empty($order_ids)) {
    $ids = implode(',', $order_ids);
    $item_sql = "SELECT oi.*, j.name FROM order_items oi JOIN jewellery j ON oi.jewellery_id = j.id WHERE oi.order_id IN ($ids)";
    $item_result = mysqli_query($conn, $item_sql);
    while ($item = mysqli_fetch_assoc($item_result)) {
        $order_items_map[$item['order_id']][] = $item;
    }
}

// status badge color logic
function status_badge_class($status) {
    switch ($status) {
        case 'approved': return 'bg-success';
        case 'pending': return 'bg-warning';
        case 'declined': return 'bg-danger';
        case 'shipped': return 'bg-info';
        case 'delivered': return 'bg-primary';
        case 'cancelled': return 'bg-secondary';
        default: return 'bg-secondary';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Manager Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
        }
        h2 {
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 30px;
        }
        .card {
            border: none;
            border-radius: 18px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
            background: rgba(255,255,255,0.95);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card:hover {
            transform: translateY(-4px) scale(1.01);
            box-shadow: 0 16px 40px 0 rgba(31, 38, 135, 0.18);
        }
        .card-header {
            background: linear-gradient(90deg, #2c3e50 0%, #2980b9 100%);
            color: #fff;
            border-top-left-radius: 18px;
            border-top-right-radius: 18px;
            font-size: 1.1rem;
            font-weight: 500;
            padding: 18px 24px;
        }
        .badge.bg-secondary {
            background: #6c757d !important;
        }
        .badge.bg-success {
            background: #27ae60 !important;
        }
        .badge.bg-warning {
            background: #f39c12 !important;
            color: #fff;
        }
        .badge.bg-danger {
            background: #e74c3c !important;
        }
        .badge.bg-info {
            background: #2980b9 !important;
        }
        .badge.bg-primary {
            background: #2c3e50 !important;
        }
        .table {
            background: transparent;
            border-radius: 12px;
            overflow: hidden;
        }
        .table th {
            background: #2c3e50;
            color: #fff;
            border: none;
            font-weight: 600;
        }
        .table td {
            vertical-align: middle;
        }
        .card-body {
            padding: 24px;
        }
        .btn-primary {
            background: #2c3e50;
            border: none;
            border-radius: 8px;
            transition: background 0.2s;
        }
        .btn-primary:hover {
            background: #2980b9;
        }
        .form-select-sm {
            border-radius: 8px;
        }
        .mb-4 {
            margin-bottom: 2rem !important;
        }
        .mb-3 {
            margin-bottom: 1.2rem !important;
        }
        .text-end strong {
            color: #2980b9;
            font-size: 1.1rem;
        }
        @media (max-width: 600px) {
            .container { padding: 0 5px; }
            .card-body { padding: 10px; }
            .card-header { padding: 10px; font-size: 1rem; }
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <a href="dashboard.php" class="btn btn-secondary mb-3">Back to Dashboard</a>
    <h2>All Orders</h2>
    <!-- Search & Filter Form -->
    <form method="GET" class="row g-3 mb-4 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Search (Order ID, Name, Email)</label>
            <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="Order ID, Name, Email">
        </div>
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="">All Statuses</option>
                <?php foreach (["pending","approved","declined","shipped","delivered","cancelled"] as $status): ?>
                    <option value="<?php echo $status; ?>" <?php if($status_filter==$status) echo 'selected'; ?>><?php echo ucfirst($status); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">From</label>
            <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">To</label>
            <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </form>
    <?php if (empty($orders)): ?>
        <div class="alert alert-info">No orders found.</div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Order #<?php echo $order['id']; ?></strong> | Customer: <?php echo htmlspecialchars($order['customer_name']); ?> (<?php echo htmlspecialchars($order['email']); ?>) | Date: <?php echo $order['created_at']; ?> | Status: <span class="badge <?php echo status_badge_class($order['status']); ?>"><?php echo ucfirst($order['status']); ?></span>
                    </div>
                    <form method="POST" class="d-flex align-items-center" style="gap:10px;">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <select name="new_status" class="form-select form-select-sm" style="width:auto;">
                            <?php foreach (["pending","approved","declined","shipped","delivered","cancelled"] as $status): ?>
                                <option value="<?php echo $status; ?>" <?php if($order['status']==$status) echo 'selected'; ?>><?php echo ucfirst($status); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary">Update</button>
                    </form>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead><tr><th>Item</th><th>Price</th><th>Qty</th><th>Total</th></tr></thead>
                        <tbody>
                        <?php if (!empty($order_items_map[$order['id']])): foreach ($order_items_map[$order['id']] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                    <div class="text-end"><strong>Total: ₹<?php echo number_format($order['total_amount'], 2); ?></strong></div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Order pagination">
      <ul class="pagination justify-content-center mt-4">
        <li class="page-item <?php if($page==1) echo 'disabled'; ?>">
          <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page'=>$page-1])); ?>">Previous</a>
        </li>
        <?php for($i=1; $i<=$total_pages; $i++): ?>
          <li class="page-item <?php if($page==$i) echo 'active'; ?>">
            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page'=>$i])); ?>"><?php echo $i; ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item <?php if($page==$total_pages) echo 'disabled'; ?>">
          <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page'=>$page+1])); ?>">Next</a>
        </li>
      </ul>
    </nav>
    <?php endif; ?>
</div>
</body>
</html> 