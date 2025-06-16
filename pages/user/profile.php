<?php
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
// Bật hiển thị lỗi để debug (chỉ dùng trong môi trường phát triển)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Bắt đầu buffer để kiểm soát đầu ra
ob_start();

require_once $db_connect_url;
include $header_url;

// Kiểm tra xem người dùng đã đăng nhập chưa
if (isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}



// Lấy thông tin người dùng
$user_id = $_SESSION['user_id'];
$sql = "SELECT name, email, phone, address, city FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
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
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin người dùng']);
    ob_end_flush();
    exit;
}

// Xử lý yêu cầu cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Xóa mọi đầu ra trước khi gửi JSON
    ob_clean();
    
    $name = isset($_POST['name']) ? mysqli_real_escape_string($conn, trim($_POST['name'])) : $user['name'];
    $email = isset($_POST['email']) ? mysqli_real_escape_string($conn, trim($_POST['email'])) : $user['email'];
    $phone = isset($_POST['phone']) ? mysqli_real_escape_string($conn, trim($_POST['phone'])) : $user['phone'];
    $address = isset($_POST['address']) ? mysqli_real_escape_string($conn, trim($_POST['address'])) : $user['address'];
    $city = isset($_POST['city']) ? mysqli_real_escape_string($conn, trim($_POST['city'])) : $user['city'];
    $password = isset($_POST['password']) && !empty($_POST['password']) ? password_hash(trim($_POST['password']), PASSWORD_DEFAULT) : null;

    // Kiểm tra dữ liệu đầu vào
    if (empty($name) || empty($email)) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Tên và email không được để trống']);
        ob_end_flush();
        exit;
    }

    // Kiểm tra định dạng email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Email không hợp lệ']);
        ob_end_flush();
        exit;
    }

    // Kiểm tra độ dài mật khẩu nếu có nhập
    if (!empty($_POST['password']) && strlen($_POST['password']) < 8) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 8 ký tự']);
        ob_end_flush();
        exit;
    }

    // Kiểm tra email đã tồn tại (ngoại trừ email của chính người dùng)
    $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị truy vấn: ' . mysqli_error($conn)]);
        ob_end_flush();
        exit;
    }
    mysqli_stmt_bind_param($stmt, 'si', $email, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Email đã được sử dụng']);
        mysqli_stmt_close($stmt);
        ob_end_flush();
        exit;
    }
    mysqli_stmt_close($stmt);

    // Cập nhật thông tin người dùng
    $sql = "UPDATE users SET name = ?, email = ?, phone = ?, address = ?, city = ?";
    $params = [$name, $email, $phone, $address, $city];
    $types = 'sssss';

    if ($password) {
        $sql .= ", password = ?";
        $params[] = $password;
        $types .= 's';
    }

    $sql .= " WHERE id = ?";
    $params[] = $user_id;
    $types .= 'i';

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị truy vấn: ' . mysqli_error($conn)]);
        ob_end_flush();
        exit;
    }

    mysqli_stmt_bind_param($stmt, $types, ...$params);

    if (mysqli_stmt_execute($stmt)) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => true, 'message' => 'Cập nhật thông tin thành công!']);
    } else {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật: ' . mysqli_error($conn)]);
    }

    mysqli_stmt_close($stmt);
    ob_end_flush();
    exit;
}

// Lấy danh sách đơn hàng của người dùng
$sql = "SELECT o.id, o.created_at, o.status, o.total_money, o.payment_status 
        FROM orders o 
        WHERE o.user_id = ? 
        ORDER BY o.created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$orders_result = mysqli_stmt_get_result($stmt);
$orders = [];
while ($row = mysqli_fetch_assoc($orders_result)) {
    $orders[] = $row;
}
mysqli_stmt_close($stmt);

?>

<div class="container mx-auto p-6">
    <!-- Tab navigation -->
    <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="true">Thông tin cá nhân</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab" aria-controls="orders" aria-selected="false">Danh sách đơn hàng</button>
        </li>
    </ul>

    <!-- Tab content -->
    <div class="tab-content" id="profileTabsContent">
        <!-- Profile Tab -->
        <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
            <?php include 'edit_profile.php'; ?>
        </div>
        <!-- Orders Tab -->
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
<script src="assets/js/main.js"></script>
<?php 
include $footer_url;
mysqli_close($conn);
ob_end_flush();
?>