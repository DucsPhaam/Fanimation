<?php
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
require_once $db_connect_url;

// Start session for message handling
session_start();

// Get user ID from URL
$user_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];
$success = false;

// Validate user ID
if ($user_id <= 0) {
    $errors[] = 'Invalid user ID.';
} else {
    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Check if user exists and get role
        $stmt = mysqli_prepare($conn, "SELECT role FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($user = mysqli_fetch_assoc($result)) {
            // Check for related orders
            $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $order_count = mysqli_fetch_assoc($result)['count'];
            mysqli_stmt_close($stmt);
            if ($order_count > 0) {
                $errors[] = 'Cannot delete user because they have ' . $order_count . ' associated order(s).';
            }

            // Check for related carts
            $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM carts WHERE user_id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $cart_count = mysqli_fetch_assoc($result)['count'];
            mysqli_stmt_close($stmt);
            if ($cart_count > 0) {
                $errors[] = 'Cannot delete user because they have ' . $cart_count . ' item(s) in cart.';
            }

            // Check for related feedbacks
            $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM feedbacks WHERE user_id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $feedback_count = mysqli_fetch_assoc($result)['count'];
            mysqli_stmt_close($stmt);
            if ($feedback_count > 0) {
                $errors[] = 'Cannot delete user because they have ' . $feedback_count . ' feedback(s).';
            }

            // Check for related contacts
            $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM contacts WHERE user_id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $contact_count = mysqli_fetch_assoc($result)['count'];
            mysqli_stmt_close($stmt);
            if ($contact_count > 0) {
                $errors[] = 'Cannot delete user because they have ' . $contact_count . ' contact record(s).';
            }

            // Check if deleting the last admin
            if ($user['role'] === 'admin' && empty($errors)) {
                $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND id != ?");
                mysqli_stmt_bind_param($stmt, 'i', $user_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $admin_count = mysqli_fetch_assoc($result)['count'];
                mysqli_stmt_close($stmt);
                if ($admin_count < 1) {
                    $errors[] = 'Cannot delete the last admin user.';
                }
            }

            // If no errors, delete user
            if (empty($errors)) {
                $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
                mysqli_stmt_bind_param($stmt, 'i', $user_id);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception('Failed to delete user: ' . mysqli_error($conn));
                }
                mysqli_stmt_close($stmt);
                $success = true;
            }

            // Commit transaction
            if ($success) {
                mysqli_commit($conn);
                $_SESSION['success'] = 'User deleted successfully!';
            } else {
                mysqli_rollback($conn);
                $_SESSION['errors'] = $errors;
            }
        } else {
            $errors[] = 'User not found.';
            $_SESSION['errors'] = $errors;
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $errors[] = 'Transaction failed: ' . $e->getMessage();
        $_SESSION['errors'] = $errors;
    }
}

// Redirect
header("Location: $base_url/pages/admin/users.php");
exit;
?>