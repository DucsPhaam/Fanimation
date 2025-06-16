<?php
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
require_once $db_connect_url;
include $admin_header_url;

session_start();
$errors = [];
$success = '';
$user = null;

// Get user ID from URL
$user_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch user data
if ($user_id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT id, name, email, phone, address, city, role FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    if (!$user) {
        $errors[] = 'User not found.';
    }
} else {
    $errors[] = 'Invalid user ID.';
}

// Valid email domains
$valid_domains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'aol.com'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $role = trim($_POST['role'] ?? 'customer');

    // Validation
    if (empty($name)) $errors[] = 'Name is required.';
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    } else {
        // Check email domain
        $domain = strtolower(substr(strrchr($email, '@'), 1));
        if (!in_array($domain, $valid_domains)) {
            $errors[] = 'Email domain must be one of: ' . implode(', ', $valid_domains);
        }
    }
    if (!empty($phone) && !preg_match('/^[0-9]{10,11}$/', $phone)) {
        $errors[] = 'Phone number must be 10-11 digits and contain only numbers.';
    }
    if (!in_array($role, ['customer', 'admin'])) $errors[] = 'Invalid role selected.';

    // Check if email exists for another user
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
    mysqli_stmt_bind_param($stmt, 'si', $email, $user_id);
    mysqli_stmt_execute($stmt);
    if (mysqli_stmt_get_result($stmt)->num_rows > 0) {
        $errors[] = 'Email already exists.';
    }
    mysqli_stmt_close($stmt);

    // Check if updating the last admin to non-admin
    if ($user['role'] === 'admin' && $role !== 'admin') {
        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND id != ?");
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $admin_count = mysqli_fetch_assoc($result)['count'];
        mysqli_stmt_close($stmt);
        if ($admin_count < 1) {
            $errors[] = 'Cannot change the last admin to a non-admin role.';
        }
    }

    // If no errors, update user
    if (empty($errors)) {
        $query = "UPDATE users SET name = ?, email = ?, phone = ?, address = ?, city = ?, role = ?";
        $params = [$name, $email, $phone, $address, $city, $role];
        $types = 'ssssss';

        // Update password if provided
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $query .= ", password = ?";
            $params[] = $hashed_password;
            $types .= 's';
        }

        $query .= " WHERE id = ?";
        $params[] = $user_id;
        $types .= 'i';

        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = 'User updated successfully!';
            header("Location: $base_url/pages/admin/users.php");
            exit;
        } else {
            $errors[] = 'Failed to update user: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fanimation - Edit User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/header.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/assets/fonts/font.php'; ?>
</head>
<body>
    <?php include $admin_sidebar_url; ?>
    
    <main class="p-4">
        <h1 class="mb-4">Edit User</h1>
        <div class="card">
            <div class="card-body">
                <?php if ($user): ?>
                    <form method="POST" action="" id="editUserForm">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="address" name="address" value="<?= htmlspecialchars($user['address']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control" id="city" name="city" value="<?= htmlspecialchars($user['city']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="customer" <?= $user['role'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Changes</button>
                        <a href="<?= $base_url; ?>/pages/admin/users.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
                    </form>
                <?php else: ?>
                    <p class="text-danger">Cannot edit user due to errors.</p>
                    <a href="<?= $base_url; ?>/pages/admin/users.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to Users</a>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('editUserForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'Do you want to save these changes?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, save it!',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            }

            <?php if (!empty($errors)): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: '<?= implode('<br>', array_map('htmlspecialchars', $errors)); ?>',
                    timer: 3000,
                    showConfirmButton: false
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>