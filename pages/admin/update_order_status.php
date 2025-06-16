<?php
     header('Content-Type: application/json');
     ini_set('display_errors', 0); // Disable display errors in production
     error_reporting(E_ALL);

     include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
     require_once $db_connect_url; // Assumes this initializes $conn
     require_once $function_url; // Includes updateOrderStatus

     // Check database connection
     if (!$conn) {
         echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . mysqli_connect_error()]);
         exit;
     }

     // Check if required parameters are provided
     if (!isset($_POST['order_id']) || !isset($_POST['status'])) {
         echo json_encode(['success' => false, 'error' => 'Missing order ID or status']);
         exit;
     }

     $order_id = (int)$_POST['order_id'];
     $new_status = trim($_POST['status']);
     
     // Log input for debugging
     error_log("Updating order: order_id=$order_id, status=$new_status");

     // Update order status
     $result = updateOrderStatus($conn, $order_id, $new_status);

     // Return JSON response
     echo json_encode([
         'success' => $result['success'],
         'error' => isset($result['message']) ? $result['message'] : null
     ]);

     // Close database connection
     mysqli_close($conn);
     ?>