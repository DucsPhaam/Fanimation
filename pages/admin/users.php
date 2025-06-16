<?php
include $_SERVER['DOCUMENT_ROOT'] . '/Fanimation/includes/config.php';
require_once $db_connect_url;
include $admin_header_url;

// Start session for message handling

// Get pagination and filter parameters
$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role = isset($_GET['role']) ? trim($_GET['role']) : '';

// Fetch users with pagination and filters
$users_data = getAllUsers($conn, $records_per_page, $page, $search, $role);
$users = $users_data['users'];
$total_pages = $users_data['total_pages'];

// Handle session messages
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$errors = isset($_SESSION['errors']) ? $_SESSION['errors'] : [];
unset($_SESSION['success'], $_SESSION['errors']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fanimation - Manage Users</title>
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
        <h1 class="mb-4">Manage Users</h1>
        
        <div class="row mb-3">
            <div class="col-md-6">
                <a href="<?= $base_url; ?>/pages/admin/add_user.php" class="btn btn-success"><i class="bi bi-plus-circle"></i> Add New User</a>
            </div>
            <div class="col-md-6">
                <form class="d-flex flex-wrap gap-2" method="GET" action="">
                    <input type="text" name="search" class="form-control" style="max-width: 200px;" placeholder="Search by name, email, or ID" value="<?= htmlspecialchars($search); ?>">
                    <select name="role" class="form-select" style="max-width: 150px;">
                        <option value="">All Roles</option>
                        <option value="customer" <?= $role === 'customer' ? 'selected' : ''; ?>>Customer</option>
                        <option value="admin" <?= $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users && count($users) > 0): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['id']); ?></td>
                                    <td><?= htmlspecialchars($user['name']); ?></td>
                                    <td><?= htmlspecialchars($user['email']); ?></td>
                                    <td><?= htmlspecialchars($user['phone'] ?? '-'); ?></td>
                                    <td><?= htmlspecialchars(ucfirst($user['role'])); ?></td>
                                    <td>
                                        <a href="<?= $base_url; ?>/pages/admin/edit_user.php?id=<?= $user['id']; ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Edit</a>
                                        <button class="btn btn-sm btn-danger delete-user" data-id="<?= $user['id']; ?>"><i class="bi bi-trash"></i> Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No users found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?= $page - 1; ?>&search=<?= urlencode($search); ?>&role=<?= urlencode($role); ?>" aria-label="Previous">
                                    <span aria-hidden="true">«</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $page === $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?= $i; ?>&search=<?= urlencode($search); ?>&role=<?= urlencode($role); ?>"><?= $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?= $page + 1; ?>&search=<?= urlencode($search); ?>&role=<?= urlencode($role); ?>" aria-label="Next">
                                    <span aria-hidden="true">»</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle delete buttons
            document.querySelectorAll('.delete-user').forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'You will not be able to recover this user!',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete it!',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = '<?= $base_url; ?>/pages/admin/delete_user.php?id=' + userId;
                        }
                    });
                });
            });

            // Display session messages
            <?php if ($success): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: '<?= htmlspecialchars($success); ?>',
                    timer: 2000,
                    showConfirmButton: false
                });
            <?php endif; ?>
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