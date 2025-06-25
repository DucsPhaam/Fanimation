# Fanimation
for eProject 1

# Fanimation - Online Fan Store Management System
Fanimation is a web-based application designed to manage an online store specializing in fans. It includes an admin dashboard for product and user management, a user interface for shopping and profile management, and a structured backend with database integration.

# Project Overview
Purpose: Manage products (e.g., fans with various colors, sizes, and specifications), user accounts, orders, and payments.
Technologies: PHP, MySQL, HTML, CSS, JavaScript, Bootstrap.
Structure: Organized into assets, includes, pages, and API directories for scalability and maintainability.

# Installation
Prerequisites:
- Web server (e.g., Apache/Nginx)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer (optional for dependency management)

# Step
1.Clone the Repository
git clone https://github.com/DucsPhaam/Fanimation.git
cd Fanimation

2.Configure the Database
Update includes/db_connect.php with your database credentials:
$host = "localhost";
$username = "your_username";
$password = "your_password";
$database = "fanimation";

3.Set Up Permissions
Ensure the assets/images/products/ directory is writable by the web server

4.Start the Web Server
- Place the project in your web server’s root directory (e.g., /var/www/html/fanimation).
- Access the site via http://localhost/fanimation.

# Usage
I.Admin Features
- Add Product: Use pages/admin/add_product.php to add new fan products with details, colors, stock, and images.
- Manage Products: View and edit products via pages/admin/products.php.
- User Management: Add, edit, or delete users through pages/admin/users.php.
- Order Management: Handle orders and update statuses via pages/admin/orders.php.
II.User Features
- Registration/Login: Register or log in via pages/user/register.php and pages/user/login.php.
- Shopping: Add items to cart (pages/cart/add_to_cart.php), view cart (pages/cart/cart.php), and checkout (pages/cart/checkout.php)
- Profile: Manage profile details via pages/user/profile.php.
III.Configuration
- Edit includes/config.php to set the base URL and database connection details.
- Adjust .htaccess for URL rewriting if needed (e.g., remove index.php from URLs).
IV.Troubleshooting
- Errors Adding Products: Check server logs (/var/log/php_errors.log) for details. Ensure images are under 5MB and in supported formats (jpg, jpeg, png, gif).
- Database Issues: Verify foreign key constraints and data integrity in categories, brands, and colors tables.
- CSS/JS Not Loading: Confirm internet access for CDN resources (e.g., Bootstrap, Font Awesome) or host them locally.
V.Contributing
1.Fork the repository.
2.Create a feature branch (git checkout -b feature-name).
3.Commit changes (git commit -m "Description").
4.Push to the branch (git push origin feature-name).
5.Open a pull request.
# Contact
For support or questions, contact ducsphaam@gmail.com.
