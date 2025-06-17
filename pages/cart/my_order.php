<?php
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';

// Bật hiển thị lỗi để debug (chỉ dùng trong môi trường phát triển)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once $db_connect_url;
include $header_url;

// Debug session
error_log("Debug - User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null') . ", Session ID: " . session_id());

// Kiểm tra kết nối database
if (!$conn) {
    die('Lỗi kết nối database: ' . mysqli_connect_error());
}

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$session_id = !$user_id ? session_id() : null;
$identifier = $user_id ? 'user_id' : 'session_id';
$identifier_value = $user_id ?: $session_id;

// Lấy tổng số đơn hàng
$total_sql = "SELECT COUNT(*) as total FROM orders o WHERE o.$identifier = ?";
$total_stmt = mysqli_prepare($conn, $total_sql);
if (!$total_stmt) {
    die('Lỗi chuẩn bị truy vấn tổng: ' . mysqli_error($conn));
}
mysqli_stmt_bind_param($total_stmt, $user_id ? 'i' : 's', $identifier_value);
mysqli_stmt_execute($total_stmt);
$total_result = mysqli_stmt_get_result($total_stmt);
$total_row = mysqli_fetch_assoc($total_result);
$total_orders = $total_row['total'] ?? 0;
mysqli_stmt_close($total_stmt);

// Lấy danh sách đơn hàng (giới hạn 5 ban đầu)
$limit = 5;
$offset = 0;
$sql = "SELECT o.id, o.created_at, o.status, o.total_money, o.payment_status 
        FROM orders o 
        WHERE o.$identifier = ? 
        ORDER BY o.created_at ASC 
        LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    die('Lỗi chuẩn bị truy vấn: ' . mysqli_error($conn));
}

$param_type = $user_id ? 'iii' : 'isi';
mysqli_stmt_bind_param($stmt, $param_type, $identifier_value, $limit, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$orders = [];
while ($row = mysqli_fetch_assoc($result)) {
    $orders[] = $row;
}
mysqli_stmt_close($stmt);
?>
<div class="container mx-auto p-6">
    <h2 class="text-xl font-bold mb-4">Danh sách đơn hàng của tôi</h2>
    <?php if (empty($orders)): ?>
        <p class="text-gray-600">Bạn chưa có đơn hàng nào.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="table table-bordered" id="ordersTable">
                <thead>
                    <tr>
                        <th>Mã đơn hàng</th>
                        <th>Ngày đặt</th>
                        <th>Trạng thái</th>
                        <th>Tổng tiền</th>
                        <th>Thanh toán</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody id="ordersBody">
                    <?php foreach ($orders as $order): ?>
                        <tr data-order-id="<?php echo htmlspecialchars($order['id']); ?>" class="order-row">
                            <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                            <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($order['created_at']))); ?></td>
                            <td><?php echo htmlspecialchars($order['status']); ?></td>
                            <td><?php echo number_format($order['total_money'], 0, '', '.'); ?> $</td>
                            <td><?php echo htmlspecialchars($order['payment_status']); ?></td>
                            <td><button class="btn btn-info btn-view-details" data-order-id="<?php echo htmlspecialchars($order['id']); ?>">Xem chi tiết</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($total_orders > $limit): ?>
                <div id="toggleButtons" class="mt-3">
                    <button id="chevronUpBtn" class="btn btn-link" style="padding: 0; display: none;">
                        <i class="bi bi-chevron-up" style="font-size: 1.5rem;"></i>
                    </button>
                    <button id="chevronDownBtn" class="btn btn-link" style="padding: 0;">
                        <i class="bi bi-chevron-down" style="font-size: 1.5rem;"></i>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <div id="orderDetails" class="mt-4 p-4 border rounded bg-gray-50 hidden">
            <h3 class="text-lg font-semibold mb-2">Chi tiết đơn hàng #<span id="detailOrderId"></span></h3>
            <table class="table table-bordered w-full">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Số lượng</th>
                        <th>Giá</th>
                        <th>Tổng</th>
                    </tr>
                </thead>
                <tbody id="orderItems"></tbody>
            </table>
            <p class="mt-2"><strong>Tổng tiền:</strong> <span id="detailTotalMoney"></span> $</p>
            <p><strong>Trạng thái:</strong> <span id="detailStatus"></span></p>
            <p><strong>Thanh toán:</strong> <span id="detailPaymentStatus"></span></p>
            <p><strong>Ngày đặt:</strong> <span id="detailCreatedAt"></span></p>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="/Fanimation/assets/js/main.js"></script>
<script>
$(document).ready(function() {
    let offset = <?php echo $limit; ?>;
    const limit = 5;
    const totalOrders = <?php echo $total_orders; ?>;

    $('#ordersTable').on('click', '.btn-view-details', function() {
        var orderId = $(this).data('order-id');
        $('#detailOrderId').text(orderId);
        
        $.ajax({
            url: '/Fanimation/pages/cart/get_order_detail.php',
            type: 'GET',
            data: { order_id: orderId },
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    var items = response.items || [];
                    var totalMoney = response.total_money || 0;
                    var status = response.status || 'Không xác định';
                    var paymentStatus = response.payment_status || 'Không xác định';
                    var createdAt = response.created_at || 'Không xác định';

                    $('#orderItems').empty();

                    if (items.length > 0) {
                        $.each(items, function(index, item) {
                            $('#orderItems').append(
                                '<tr>' +
                                    '<td>' + (item.name || 'Không xác định') + '</td>' +
                                    '<td>' + (item.quantity || 0) + '</td>' +
                                    '<td>' + (item.price ? Number(item.price).toLocaleString('vi-VN') : 0) + ' $</td>' +
                                    '<td>' + (item.total_money ? Number(item.total_money).toLocaleString('vi-VN') : 0) + ' $</td>' +
                                '</tr>'
                            );
                        });
                    } else {
                        $('#orderItems').append('<tr><td colspan="4">Không có sản phẩm trong đơn hàng</td></tr>');
                    }

                    $('#detailTotalMoney').text(Number(totalMoney).toLocaleString('vi-VN'));
                    $('#detailStatus').text(status);
                    $('#detailPaymentStatus').text(paymentStatus);
                    $('#detailCreatedAt').text(createdAt);

                    $('#orderDetails').removeClass('hidden');
                } else {
                    alert('Không thể tải chi tiết đơn hàng: ' + (response.message || 'Phản hồi không hợp lệ'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Debug - AJAX Error: ', status, error);
                alert('Lỗi khi tải chi tiết đơn hàng: ' + xhr.status + ' - ' + (xhr.responseText || 'Không có phản hồi từ server.'));
            }
        });
    });

    $('#chevronDownBtn').click(function() {
        if (offset >= totalOrders) {
            $(this).hide();
            return;
        }

        $.ajax({
            url: '/Fanimation/pages/cart/load_more_order.php',
            type: 'GET',
            data: {
                identifier: '<?php echo $identifier; ?>',
                identifier_value: '<?php echo $identifier_value; ?>',
                limit: limit,
                offset: offset
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var newOrders = response.orders;
                    var ordersBody = $('#ordersBody');
                    $.each(newOrders, function(index, order) {
                        ordersBody.append(
                            '<tr data-order-id="' + order.id + '" class="order-row">' +
                                '<td>#' + order.id + '</td>' +
                                '<td>' + order.created_at_formatted + '</td>' +
                                '<td>' + order.status + '</td>' +
                                '<td>' + order.total_money.toLocaleString('vi-VN') + ' $</td>' +
                                '<td>' + order.payment_status + '</td>' +
                                '<td><button class="btn btn-info btn-view-details" data-order-id="' + order.id + '">Xem chi tiết</button></td>' +
                            '</tr>'
                        );
                    });
                    offset += limit;
                    if (offset >= totalOrders) {
                        $('#chevronDownBtn').hide();
                    }
                    $('#chevronUpBtn').show();
                } else {
                    alert('Không thể tải thêm đơn hàng: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Debug - Load More Error: ', status, error);
                alert('Lỗi khi tải thêm đơn hàng: ' + xhr.status + ' - ' + (xhr.responseText || 'Không có phản hồi từ server.'));
            }
        });
    });

    $('#chevronUpBtn').click(function() {
        const $rows = $('.order-row');
        if ($rows.length > limit) {
            $rows.slice(-limit).remove();
            offset -= limit;
            $('#chevronDownBtn').show();
            if (offset <= limit) {
                $(this).hide();
            }
        }
    });

    $(document).click(function(event) {
        if (!$(event.target).closest('#orderDetails, #ordersTable').length) {
            $('#orderDetails').addClass('hidden');
        }
    });
});
</script>

<?php
include $footer_url;
mysqli_close($conn);
?>
