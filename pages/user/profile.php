<?php
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
require_once $db_connect_url;
include $header_url;

// Bật debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ob_start();

// Kiểm tra kết nối cơ sở dữ liệu
if (!$conn) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu: ' . mysqli_connect_error()]);
    ob_end_flush();
    exit;
}

// Kiểm tra phiên đăng nhập
if (!isset($_SESSION['user_id'])) {
    ob_clean();
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Lấy thông tin người dùng
$sql = "SELECT name, email, phone, address, city FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị truy vấn: ' . mysqli_error($conn)]);
    ob_end_flush();
    exit;
}
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin người dùng']);
    ob_end_flush();
    exit;
}

// Lấy danh sách đơn hàng
$sql = "SELECT o.id, o.created_at, o.status, o.total_money, o.payment_status 
        FROM orders o 
        WHERE o.user_id = ? 
        ORDER BY o.created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    error_log('Lỗi chuẩn bị truy vấn đơn hàng: ' . mysqli_error($conn));
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn đơn hàng']);
    ob_end_flush();
    exit;
}
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$orders_result = mysqli_stmt_get_result($stmt);
$orders = [];
while ($row = mysqli_fetch_assoc($orders_result)) {
    $orders[] = $row;
}
mysqli_stmt_close($stmt);
?>
<style>
    .nav-tabs .nav-link {
        background-color: white;
        color: #374151;
        font-weight: 500;
        margin-right: 0.5rem;
    }
    .nav-tabs .nav-link:hover {
        background-color: #e0e7ff;
        color: #1e3a8a;
    }
    .nav-tabs .nav-link.active {
        background-color: #2563eb;
        color: white;
        font-weight: 600;
        border-color: #2563eb #2563eb #fff;
    }
</style>
<div class="container mx-auto p-6">
    <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="true">
                Thông tin cá nhân
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab" aria-controls="orders" aria-selected="false">
                Danh sách đơn hàng
            </button>
        </li>
    </ul>
    <div class="tab-content" id="profileTabsContent">
        <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
            <?php include 'edit_profile.php'; ?>
        </div>
        <div class="tab-pane fade" id="orders" role="tabpanel" aria-labelledby="orders-tab">
            <h2 class="text-xl font-bold mb-4">Danh sách đơn hàng</h2>
            <?php if (empty($orders)): ?>
                <p class="text-gray-600">Bạn chưa có đơn hàng nào.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Mã đơn hàng</th>
                                <th>Ngày đặt</th>
                                <th>Trạng thái</th>
                                <th>Tổng tiền</th>
                                <th>Thanh toán</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                                    <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($order['created_at']))); ?></td>
                                    <td><?php echo htmlspecialchars($order['status']); ?></td>
                                    <td><?php echo number_format($order['total_money'], 2); ?> VNĐ</td>
                                    <td><?php echo htmlspecialchars($order['payment_status']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="/Fanimation/assets/js/main.js"></script>
<?php
include $footer_url;
mysqli_close($conn);
ob_end_flush();
?>
