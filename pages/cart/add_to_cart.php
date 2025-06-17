<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
require_once $db_connect_url;
require_once $function_url;
ob_clean();
header('Content-Type: application/json');

error_log("Starting add_to_cart.php - " . date('Y-m-d H:i:s'));
error_log("Received POST data: " . print_r($_POST, true));

// Generate or retrieve session_id for guests
if (!isset($_SESSION['session_id'])) {
    $_SESSION['session_id'] = session_id();
}
$session_id = $_SESSION['session_id'];
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

$action = isset($_POST['action']) ? $_POST['action'] : 'add';

if ($action === 'add') {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $color_id = isset($_POST['color_id']) ? intval($_POST['color_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

    error_log("Add product: user_id=" . ($user_id ?? 'guest') . ", session_id=$session_id, product_id=$product_id, color_id=$color_id, quantity=$quantity");

    if ($product_id <= 0 || $color_id <= 0 || $quantity < 1) {
        error_log("Invalid product data: product_id=$product_id, color_id=$color_id, quantity=$quantity");
        echo json_encode(['status' => 'error', 'message' => 'Thông tin sản phẩm không hợp lệ']);
        exit;
    }

    if (!$conn) {
        error_log("Connection failed: No valid database connection");
        echo json_encode(['status' => 'error', 'message' => 'Lỗi kết nối cơ sở dữ liệu']);
        exit;
    }

    // Get product_variant_id
    $variant_query = "SELECT id, stock FROM product_variants WHERE product_id = ? AND color_id = ?";
    $variant_stmt = $conn->prepare($variant_query);
    if (!$variant_stmt) {
        error_log("Prepare variant query failed: " . $conn->error);
        echo json_encode(['status' => 'error', 'message' => 'Lỗi kiểm tra biến thể: ' . $conn->error]);
        exit;
    }
    $variant_stmt->bind_param('ii', $product_id, $color_id);
    $variant_stmt->execute();
    $variant_result = $variant_stmt->get_result()->fetch_assoc();
    $variant_stmt->close();

    if (!$variant_result) {
        error_log("No variant found: product_id=$product_id, color_id=$color_id");
        echo json_encode(['status' => 'error', 'message' => 'Biến thể sản phẩm không tồn tại']);
        exit;
    }

    $product_variant_id = $variant_result['id'];
    $stock = $variant_result['stock'];

    if ($stock < $quantity) {
        error_log("Insufficient stock: requested=$quantity, available=$stock");
        echo json_encode(['status' => 'error', 'message' => 'Số lượng yêu cầu vượt quá tồn kho!']);
        exit;
    }

    // Add product to cart
    $insert_query = "
        INSERT INTO carts (user_id, session_id, product_variant_id, quantity) 
        VALUES (?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE quantity = quantity + ?
    ";
    $stmt = $conn->prepare($insert_query);
    if (!$stmt) {
        error_log("Prepare insert query failed: " . $conn->error);
        echo json_encode(['status' => 'error', 'message' => 'Lỗi chuẩn bị truy vấn: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param('isiii', $user_id, $session_id, $product_variant_id, $quantity, $quantity);
    if ($stmt->execute()) {
        error_log("Insert successful: user_id=" . ($user_id ?? 'guest') . ", session_id=$session_id, product_variant_id=$product_variant_id");
        echo json_encode(['status' => 'success', 'message' => 'Đã thêm vào giỏ hàng!']);
    } else {
        error_log("Insert failed: " . $stmt->error);
        echo json_encode(['status' => 'error', 'message' => 'Lỗi khi thêm vào giỏ hàng: ' . $stmt->error]);
    }
    $stmt->close();
} elseif ($action === 'increase' || $action === 'decrease') {
    $cart_id = isset($_POST['cart_id']) ? intval($_POST['cart_id']) : 0;
    $delta = $action === 'increase' ? 1 : -1;

    error_log("Update cart: cart_id=$cart_id, delta=$delta");

    if ($cart_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Thông tin không hợp lệ']);
        exit;
    }

    // Check stock before increasing quantity
    if ($action === 'increase') {
        $stock_query = "
            SELECT pv.stock, c.quantity 
            FROM carts c 
            JOIN product_variants pv ON c.product_variant_id = pv.id 
            WHERE c.id = ? AND (c.user_id = ? OR c.session_id = ?)
        ";
        $stock_stmt = $conn->prepare($stock_query);
        $stock_stmt->bind_param('iis', $cart_id, $user_id, $session_id);
        $stock_stmt->execute();
        $stock_result = $stock_stmt->get_result()->fetch_assoc();
        $stock_stmt->close();

        if (!$stock_result) {
            echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy mục trong giỏ hàng']);
            exit;
        }

        $stock = $stock_result['stock'];
        $current_quantity = $stock_result['quantity'];

        if ($stock <= $current_quantity) {
            error_log("Insufficient stock for increase: cart_id=$cart_id, current=$current_quantity, stock=$stock");
            echo json_encode(['status' => 'error', 'message' => 'Số lượng vượt quá tồn kho!']);
            exit;
        }
    }

    $select_query = "SELECT quantity FROM carts WHERE id = ? AND (user_id = ? OR session_id = ?)";
    $stmt = $conn->prepare($select_query);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(['status' => 'error', 'message' => 'Lỗi chuẩn bị truy vấn: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param('iis', $cart_id, $user_id, $session_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if (!$result) {
        echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy mục trong giỏ hàng']);
        exit;
    }

    $current_quantity = $result['quantity'];
    $new_quantity = $current_quantity + $delta;

    if ($new_quantity <= 0) {
        $delete_query = "DELETE FROM carts WHERE id = ? AND (user_id = ? OR session_id = ?)";
        $stmt = $conn->prepare($delete_query);
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            echo json_encode(['status' => 'error', 'message' => 'Lỗi chuẩn bị truy vấn: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param('iis', $cart_id, $user_id, $session_id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Đã xóa sản phẩm khỏi giỏ hàng!']);
        } else {
            error_log("Delete failed: " . $stmt->error);
            echo json_encode(['status' => 'error', 'message' => 'Lỗi khi xóa sản phẩm: ' . $stmt->error]);
        }
    } else {
        $update_query = "UPDATE carts SET quantity = ? WHERE id = ? AND (user_id = ? OR session_id = ?)";
        $stmt = $conn->prepare($update_query);
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            echo json_encode(['status' => 'error', 'message' => 'Lỗi chuẩn bị truy vấn: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param('iiis', $new_quantity, $cart_id, $user_id, $session_id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Đã cập nhật số lượng!']);
        } else {
            error_log("Update failed: " . $stmt->error);
            echo json_encode(['status' => 'error', 'message' => 'Lỗi khi cập nhật số lượng: ' . $stmt->error]);
        }
    }
    $stmt->close();
} elseif ($action === 'remove') {
    $cart_id = isset($_POST['cart_id']) ? intval($_POST['cart_id']) : 0;

    error_log("Remove cart: cart_id=$cart_id");

    if ($cart_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Thông tin không hợp lệ']);
        exit;
    }

    $delete_query = "DELETE FROM carts WHERE id = ? AND (user_id = ? OR session_id = ?)";
    $stmt = $conn->prepare($delete_query);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(['status' => 'error', 'message' => 'Lỗi chuẩn bị truy vấn: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param('iis', $cart_id, $user_id, $session_id);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Đã xóa sản phẩm khỏi giỏ hàng!']);
    } else {
        error_log("Delete failed: " . $stmt->error);
        echo json_encode(['status' => 'error', 'message' => 'Lỗi khi xóa sản phẩm: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Hành động không hợp lệ']);
}

if (isset($conn)) {
    $conn->close();
}
?>
