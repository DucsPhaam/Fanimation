<?php
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
require_once $db_connect_url;

// Start session for message handling
session_start();

// Get product ID from URL
$product_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];
$success = false;

// Validate product ID
if ($product_id <= 0) {
    $errors[] = 'Invalid product ID.';
} else {
    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Check for related order items
        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM order_items oi JOIN product_variants pv ON oi.product_variant_id = pv.id WHERE pv.product_id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $product_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $order_count = mysqli_fetch_assoc($result)['count'];
        mysqli_stmt_close($stmt);
        if ($order_count > 0) {
            $errors[] = 'Cannot delete product because it has ' . $order_count . ' associated order(s).';
        }

        // Check for related carts
        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM carts c JOIN product_variants pv ON c.product_variant_id = pv.id WHERE pv.product_id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $product_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $cart_count = mysqli_fetch_assoc($result)['count'];
        mysqli_stmt_close($stmt);
        if ($cart_count > 0) {
            $errors[] = 'Cannot delete product because it has ' . $cart_count . ' item(s) in cart.';
        }

        // Check for related feedbacks
        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM feedbacks WHERE product_id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $product_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $feedback_count = mysqli_fetch_assoc($result)['count'];
        mysqli_stmt_close($stmt);
        if ($feedback_count > 0) {
            $errors[] = 'Cannot delete product because it has ' . $feedback_count . ' feedback(s).';
        }

        // If no errors, delete product variants, images, and product
        if (empty($errors)) {
            // Delete product variants
            $stmt = mysqli_prepare($conn, "DELETE FROM product_variants WHERE product_id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $product_id);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to delete product variants: ' . mysqli_error($conn));
            }
            mysqli_stmt_close($stmt);

            // Delete product images
            $stmt = mysqli_prepare($conn, "DELETE FROM product_images WHERE product_id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $product_id);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to delete product images: ' . mysqli_error($conn));
            }
            mysqli_stmt_close($stmt);

            // Delete product
            $stmt = mysqli_prepare($conn, "DELETE FROM products WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $product_id);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to delete product: ' . mysqli_error($conn));
            }
            mysqli_stmt_close($stmt);

            $success = true;
        }

        // Commit transaction if successful
        if ($success) {
            mysqli_commit($conn);
            $_SESSION['success'] = 'Product deleted successfully!';
        } else {
            mysqli_rollback($conn);
            $_SESSION['errors'] = $errors;
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $errors[] = 'Transaction failed: ' . $e->getMessage();
        $_SESSION['errors'] = $errors;
    }
}

// Redirect
header("Location: $base_url/pages/admin/products.php");
exit;
?>