<?php
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
require_once $db_connect_url;
include $header_url;

// Generate or retrieve session_id for guests
if (!isset($_SESSION['session_id'])) {
    $_SESSION['session_id'] = session_id();
}
$session_id = $_SESSION['session_id'];
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

error_log("Fetching cart for user_id: " . ($user_id ?? 'guest') . ", session_id: $session_id");
$cart_query = "
    SELECT c.id, c.product_variant_id, c.quantity, pv.product_id, pv.color_id, p.name, p.price, co.hex_code
    FROM carts c
    JOIN product_variants pv ON c.product_variant_id = pv.id
    JOIN products p ON pv.product_id = p.id
    LEFT JOIN colors co ON pv.color_id = co.id
    WHERE c.user_id = ? OR c.session_id = ?
";
$stmt = $conn->prepare($cart_query);
if (!$stmt) {
    error_log("Prepare cart query failed: " . $conn->error);
    echo "<div class='alert alert-danger'>Lỗi truy vấn giỏ hàng: " . htmlspecialchars($conn->error) . "</div>";
    include $footer_url;
    exit;
}
$stmt->bind_param('is', $user_id, $session_id);
if (!$stmt->execute()) {
    error_log("Execute cart query failed: " . $stmt->error);
    echo "<div class='alert alert-danger'>Lỗi thực thi truy vấn: " . htmlspecialchars($stmt->error) . "</div>";
    include $footer_url;
    exit;
}
$cart_result = $stmt->get_result();

$_SESSION['cart'] = [];
while ($row = $cart_result->fetch_assoc()) {
    $key = $row['product_variant_id'];
    $cart_id = $row['id'];
    error_log("Cart item: key=$key, cart_id=$cart_id, product_variant_id={$row['product_variant_id']}, product_id={$row['product_id']}, color_id={$row['color_id']}, quantity={$row['quantity']}");
    $_SESSION['cart'][$key] = [
        'cart_id' => $cart_id,
        'product_variant_id' => $row['product_variant_id'],
        'product_id' => $row['product_id'],
        'color' => $row['hex_code'] ?? '#000000',
        'size' => 'N/A', // Size is not supported in the carts table
        'quantity' => $row['quantity'],
        'name' => $row['name'],
        'price' => $row['price']
    ];
}
$stmt->close();

function getColorHex($color_id) {
    global $conn;
    if (!$color_id) return '#000000';
    $query = "SELECT hex_code FROM colors WHERE id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) return '#000000';
    $stmt->bind_param('i', $color_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['hex_code'] ?? '#000000';
}
?>
<style>
.body-container {
    height: 60vh;
    /* display: flex; */
    justify-content: center;
    align-items: flex-start; /* Để bảng nằm trên cùng, không giữa dọc */
    padding-top: 20px; /* Khoảng cách từ đầu trang */
}

.table-bordered {
    width: 70%;
    margin: 0 auto; /* Căn giữa theo chiều ngang */
    border-collapse: collapse; /* Đảm bảo viền liền mạch */
}


</style>
<div class="body-container mt-4">
    <h2 class="text-center">Giỏ hàng</h2>
    <?php if (empty($_SESSION['cart'])): ?>
        <p class="text-center">Giỏ hàng của bạn đang trống.</p>
    <?php else: ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th>Màu</th>
                    <th>Kích thước</th>
                    <th>Số lượng</th>
                    <th>Tổng</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total = 0;
                foreach ($_SESSION['cart'] as $key => $item):
                    $cart_id = $item['cart_id'];
                    $product_variant_id = $item['product_variant_id'];
                    $color = $item['color'];
                    $size = $item['size'];
                    $quantity = $item['quantity'];
                    $name = $item['name'];
                    $price = floatval($item['price']);

                    $subtotal = $price * $quantity;
                    $total += $subtotal;
                ?>
                    <tr data-cart-id="<?php echo htmlspecialchars($cart_id); ?>" data-price="<?php echo htmlspecialchars($price); ?>">
                        <td><?php echo htmlspecialchars($name); ?></td>
                        <td style="background-color: <?php echo htmlspecialchars($color); ?>;"></td>
                        <td><?php echo htmlspecialchars($size); ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary decrease-btn" data-cart-id="<?php echo htmlspecialchars($cart_id); ?>" <?php echo $quantity <= 1 ? 'disabled' : ''; ?>>-</button>
                            <span id="qty-<?php echo htmlspecialchars($cart_id); ?>"><?php echo intval($quantity); ?></span>
                            <button class="btn btn-sm btn-primary increase-btn" data-cart-id="<?php echo htmlspecialchars($cart_id); ?>">+</button>
                        </td>
                        <td id="subtotal-<?php echo htmlspecialchars($cart_id); ?>"><?php echo number_format($subtotal, 0, '', '.'); ?> $</td>
                        <td>
                            <button class="btn btn-danger remove-btn" data-cart-id="<?php echo htmlspecialchars($cart_id); ?>">Xóa</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="4"><b>Tổng cộng:</b></td>
                    <td id="total"><?php echo number_format($total, 0, '', '.'); ?> $</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
        <div class="text-center mt-3"> <!-- Căn giữa nút Thanh toán -->
            <a href="checkout.php?items=<?php echo htmlspecialchars(implode(',', array_column($_SESSION['cart'], 'cart_id'))); ?>" class="btn btn-success">Thanh toán</a>
        </div>
        <?php if ($user_id === null): ?>
            <p class="text-center mt-3">
                <a href="login.php?redirect=cart.php">Đăng nhập</a> hoặc <a href="register.php">Đăng ký</a> để lưu giỏ hàng và theo dõi đơn hàng dễ dàng hơn!
            </p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    $('.increase-btn').click(function() {
        var cart_id = $(this).data('cart-id');
        updateCart(cart_id, 'increase');
    });

    $('.decrease-btn').click(function() {
        var cart_id = $(this).data('cart-id');
        updateCart(cart_id, 'decrease');
    });

    $('.remove-btn').click(function() {
        var cart_id = $(this).data('cart-id');
        Swal.fire({
            title: 'Bạn có chắc chắn?',
            text: 'Bạn có chắc chắn muốn xóa sản phẩm này khỏi giỏ hàng?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Xác nhận',
            cancelButtonText: 'Hủy',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                removeCart(cart_id);
            }
        });
    });

    function updateCart(cart_id, action) {
        $.ajax({
            url: '<?php echo $add_to_cart_url; ?>',
            method: 'POST',
            data: {
                action: action,
                cart_id: cart_id
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    var qtyElement = $('#qty-' + cart_id);
                    var currentQty = parseInt(qtyElement.text());
                    var newQty = action === 'increase' ? currentQty + 1 : currentQty - 1;
                    qtyElement.text(newQty);

                    if (newQty <= 1) {
                        $('.decrease-btn[data-cart-id="' + cart_id + '"]').prop('disabled', true);
                    } else {
                        $('.decrease-btn[data-cart-id="' + cart_id + '"]').prop('disabled', false);
                    }

                    var price = parseFloat($('tr[data-cart-id="' + cart_id + '"]').data('price'));
                    var subtotalElement = $('#subtotal-' + cart_id);
                    var newSubtotal = price * newQty;
                    subtotalElement.text(newSubtotal.toLocaleString('vi-VN') + ' $');

                    var totalElement = $('#total');
                    var currentTotal = parseFloat(totalElement.text().replace(/[^0-9.-]+/g, ""));
                    var delta = action === 'increase' ? price : -price;
                    var newTotal = currentTotal + delta;
                    totalElement.text(newTotal.toLocaleString('vi-VN') + ' $');
                } else {
                    Swal.fire({
                        title: 'Lỗi',
                        text: response.message,
                        icon: 'error',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#d33'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', { status: status, error: error, responseText: xhr.responseText });
                Swal.fire({
                    title: 'Lỗi',
                    text: 'Lỗi khi gửi yêu cầu: ' + (xhr.responseText || error),
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#d33'
                });
            }
        });
    }

    function removeCart(cart_id) {
        $.ajax({
            url: '<?php echo $add_to_cart_url; ?>',
            method: 'POST',
            data: {
                action: 'remove',
                cart_id: cart_id
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    var qty = parseInt($('#qty-' + cart_id).text());
                    var price = parseFloat($('tr[data-cart-id="' + cart_id + '"]').data('price'));
                    $('tr[data-cart-id="' + cart_id + '"]').remove();

                    var totalElement = $('#total');
                    var currentTotal = parseFloat(totalElement.text().replace(/[^0-9.-]+/g, ""));
                    var newTotal = currentTotal - (price * qty);
                    totalElement.text(newTotal.toLocaleString('vi-VN') + ' $');

                    if ($('tbody tr').length === 1) {
                        $('.container.mt-4').html('<p class="text-center">Giỏ hàng của bạn đang trống.</p>');
                    }

                    Swal.fire({
                        title: 'Thành công!',
                        text: response.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        title: 'Lỗi',
                        text: response.message,
                        icon: 'error',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#d33'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', { status: status, error: error, responseText: xhr.responseText });
                Swal.fire({
                    title: 'Lỗi',
                    text: 'Lỗi khi gửi yêu cầu: ' + (xhr.responseText || error),
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#d33'
                });
            }
        });
    }
});
</script>

<?php
mysqli_close($conn);
include $footer_url;
?>