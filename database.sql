DROP DATABASE IF EXISTS fanimation;

CREATE DATABASE fanimation
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE fanimation;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address VARCHAR(255),
    city VARCHAR(255),
    role ENUM('customer', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    remember_token VARCHAR(255)
)AUTO_INCREMENT = 1;

-- Tạo bảng brands
CREATE TABLE brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
)AUTO_INCREMENT = 1;

-- Tạo bảng categories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
)AUTO_INCREMENT = 1;

-- Tạo bảng colors
CREATE TABLE colors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    hex_code VARCHAR(100)
)AUTO_INCREMENT = 1;

-- Tạo bảng products
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category_id INT,
    brand_id INT,
    price INT NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (brand_id) REFERENCES brands(id)
)AUTO_INCREMENT = 1;

-- Tạo bảng product_details
CREATE TABLE product_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    size VARCHAR(100), -- Kích thước (ví dụ: "52 inches", "44 inches")
    material VARCHAR(255), -- Chất liệu (ví dụ: "Wood", "Metal", "Plastic")
    motor_type VARCHAR(100), -- Loại động cơ (ví dụ: "AC", "DC")
    blade_count INT, -- Số lượng cánh quạt
    light_kit_included TINYINT(1) DEFAULT 0, -- Có bao gồm bộ đèn không (0: Không, 1: Có)
    remote_control TINYINT(1) DEFAULT 0, -- Có điều khiển từ xa không (0: Không, 1: Có)
    airflow_cfm INT, -- Lưu lượng gió (Cubic Feet per Minute)
    power_consumption INT, -- Công suất tiêu thụ (Watt)
    warranty_years INT, -- Thời gian bảo hành (năm)
    additional_info TEXT, -- Thông tin bổ sung
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
)AUTO_INCREMENT = 1;

-- Tạo bảng product_variants
CREATE TABLE product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    color_id INT,
    stock INT NOT NULL DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (color_id) REFERENCES colors(id),
    UNIQUE KEY unique_variant (product_id, color_id)
)AUTO_INCREMENT = 1;

-- Tạo bảng product_images
CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    color_id INT,
    image_url VARCHAR(255) NOT NULL,
    u_primary TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (color_id) REFERENCES colors(id)
)AUTO_INCREMENT = 1;

-- Tạo bảng orders
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    status ENUM('pending', 'processing', 'shipped', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20),
    address TEXT NOT NULL,
    note TEXT,
    total_money DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'completed') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id)
)AUTO_INCREMENT = 1;

-- Tạo bảng order_items
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_variant_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    total_money DECIMAL(10,2) NOT NULL,
    payment_method ENUM('online', 'cash', 'cod'),
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id)
)AUTO_INCREMENT = 1;

-- Tạo bảng payments
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    payment_method ENUM('credit_card', 'paypal', 'bank_transfer', 'cod'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id)
)AUTO_INCREMENT = 1;

-- Tạo bảng carts
CREATE TABLE carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    session_id VARCHAR(255),
    product_variant_id INT,
    quantity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id)
)AUTO_INCREMENT = 1;

-- Tạo bảng feedbacks
CREATE TABLE feedbacks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    product_id INT,
    message TEXT,
    rating INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
)AUTO_INCREMENT = 1;

-- Tạo bảng contacts
CREATE TABLE contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    user_id INT NOT NULL,
    phone varchar(20),
	address text,
	product_name varchar(100),
    file_path varchar(255),
	description text,
    FOREIGN KEY (user_id) REFERENCES users(id)
)AUTO_INCREMENT = 1;

ALTER TABLE Carts MODIFY COLUMN session_id VARCHAR(255) DEFAULT NULL;

ALTER TABLE Carts MODIFY COLUMN user_id INT DEFAULT NULL;

-- Đảm bảo session_id là VARCHAR(255) và cho phép NULL


-- Cập nhật ràng buộc khóa ngoại để cho phép NULL (nếu cần)
ALTER TABLE Carts DROP FOREIGN KEY carts_ibfk_1;
ALTER TABLE Carts ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;



ALTER TABLE orders ADD COLUMN session_id VARCHAR(255) DEFAULT NULL;

ALTER TABLE orders DROP FOREIGN KEY orders_ibfk_1;
ALTER TABLE orders ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

-- Insert into
INSERT INTO users (name, email, password, phone, address, city, role)
VALUES (
    'Admin User',
    'admin@example.com',
    '$2y$10$Rwy/q8vZtOXsu5BQJGqHGOCilB60LEViQy8KReiC8LsI9jU6PK9Ia', -- Password: admin123
    '0123456789',
    '123 Admin Street',
    'Admin City',
    'admin'
), (
    'John Doe',
    'john.doe@example.com',
    '$2y$10$Z3q8z6X9Y7W5V4U3T2R1S.uK8J9H6G5F4E3D2C1B0A9Z8Y7X6W5V4', -- Password: password123
    '0987654321',
    '456 Elm Street',
    'Sample City',
    'customer'
), (
    'Jane Smith',
    'jane.smith@example.com',
    '$2y$10$A1B2C3D4E5F6G7H8I9J0K.L9M8N7O6P5Q4R3S2T1U0V9W8X7Y6Z5A', -- Password: password123
    '0912345678',
    '789 Oak Avenue',
    'Test Town',
    'customer'
);

INSERT INTO brands (name)
VALUES 
	('Fanimation');

INSERT INTO categories(name)
VALUES
	('Ceiling fans'),
    ('Pedestal fans'),
    ('Wall fans'),
    ('Exhaust fans'),
    ('Accessories');

INSERT INTO products (name, description, brand_id, category_id, price, slug)
VALUES
    ('Amped', 'A modern ceiling fan with a dynamic design, featuring integrated LED lighting, perfect for lively and energetic living spaces.', 1, 1, 220.00, 'amped-ceiling-fan'),
    ('Aviara', 'A sleek ceiling fan with thin blades, offering a minimalist style, ideal for elegant living rooms or bedrooms.', 1, 1, 240.00, 'aviara-ceiling-fan'),
    ('Barlow', 'A classic ceiling fan with a powerful motor, combined with decorative lighting, perfect for traditional settings.', 1, 1, 210.00, 'barlow-ceiling-fan'),
    ('Brawn', 'A robust industrial ceiling fan with a rugged design, suitable for garages or large open spaces.', 1, 1, 260.00, 'brawn-ceiling-fan'),
    ('Edgewood', 'A versatile ceiling fan available in sizes from 44 to 72 inches, with various color options, fitting all interior styles.', 1, 1, 230.00, 'edgewood-ceiling-fan'),
    ('Influencer', 'A unique ceiling fan with a distinctive design, incorporating smart technology for a modern and convenient experience.', 1, 1, 300.00, 'influencer-ceiling-fan'),
    ('Islander', 'A tropical-inspired ceiling fan with natural wood blades, creating a relaxing beach-like ambiance.', 1, 1, 250.00, 'islander-ceiling-fan'),
    ('Kerring', 'A minimalist ceiling fan with sharp lines, featuring LED lighting, ideal for offices or dining areas.', 1, 1, 225.00, 'kerring-ceiling-fan'),
    ('Klear', 'A transparent ceiling fan with an innovative design, providing an airy and modern feel to any space.', 1, 1, 270.00, 'klear-ceiling-fan'),
    ('Klinch', 'A compact ceiling fan with a high-performance motor, perfect for small rooms or children''s spaces.', 1, 1, 190.00, 'klinch-ceiling-fan'),
    ('Klout', 'A powerful ceiling fan with an angular design, combined with lighting, suitable for commercial settings.', 1, 1, 280.00, 'klout-ceiling-fan'),
    ('Kute', 'An elegant ceiling fan available in 44-52 inch sizes, offering a balance of style and efficiency.', 1, 1, 210.00, 'kute-ceiling-fan'),
    ('Kwartet', 'A unique four-blade ceiling fan with integrated LED lighting, ideal for artistic spaces or large living rooms.', 1, 1, 260.00, 'kwartet-ceiling-fan');

INSERT INTO product_details (product_id, size, material, motor_type, blade_count, light_kit_included, remote_control, airflow_cfm, power_consumption, warranty_years, additional_info)
VALUES
    (1, '52 inches', 'Metal, ABS Plastic', 'DC', 3, 1, 1, 5500, 35, 5, 'Integrated LED light, smart control compatible'), -- Amped
    (2, '44 inches', 'Wood, Metal', 'AC', 5, 0, 1, 4500, 60, 3, 'Minimalist design for modern interiors'), -- Aviara
    (3, '60 inches', 'Wood', 'AC', 5, 1, 0, 6000, 70, 5, 'Classic design with decorative lighting'), -- Barlow
    (4, '72 inches', 'Metal', 'DC', 6, 0, 1, 8000, 50, 7, 'Industrial-grade for large spaces'), -- Brawn
    (5, '52 inches', 'Wood, Metal', 'DC', 5, 0, 1, 5200, 40, 5, 'Versatile size options for all interiors'), -- Edgewood
    (6, '56 inches', 'ABS Plastic, Metal', 'DC', 4, 1, 1, 5800, 45, 5, 'Smart technology integration'), -- Influencer
    (7, '60 inches', 'Natural Wood', 'AC', 5, 0, 1, 6200, 65, 3, 'Tropical-inspired design'), -- Islander
    (8, '48 inches', 'Metal', 'DC', 3, 1, 1, 5000, 38, 5, 'Sharp lines, ideal for offices'), -- Kerring
    (9, '52 inches', 'Transparent ABS', 'DC', 4, 0, 1, 5400, 42, 5, 'Innovative transparent blade design'), -- Klear
    (10, '36 inches', 'Metal, ABS Plastic', 'DC', 3, 0, 1, 4000, 30, 3, 'Compact for small rooms'), -- Klinch
    (11, '60 inches', 'Metal', 'DC', 5, 1, 1, 6500, 55, 5, 'Angular design for commercial spaces'), -- Klout
    (12, '48 inches', 'Wood, Metal', 'DC', 4, 0, 1, 4800, 35, 5, 'Elegant and efficient'), -- Kute
    (13, '56 inches', 'Metal, Wood', 'DC', 4, 1, 1, 5700, 40, 5, 'Unique four-blade design with LED'); -- Kwartet

INSERT INTO colors (name, hex_code)
VALUES 
    ('Matte White', '#F4F4F4'),
    ('Black', '#000000'),
    ('Brushed Nickel', '#C0C0C0'),
    ('Dark Bronze', '#3B2F2F'),
    ('Matte Greige', '#D6D1C4'),
    ('Driftwood', '#A39E9E'),
    ('Brushed Satin Brass', '#D4AF37'),
    ('Galvanized', '#BDC3C7');
    
INSERT INTO product_variants (product_id, color_id, stock)
VALUES
    (1, 1, 20),
    (1, 2, 15),
    (1, 3, 15),
    (1, 7, 15),
    (2, 6, 1),
    (2, 1, 16),
    (2, 2, 10),
    (2, 7, 13),
    (3, 1, 8),
    (3, 2, 23),
    (3, 3, 6),
    (3, 7, 2),
    (3, 5, 14),
    (4, 1, 21),
    (4, 2, 12),
    (4, 7, 8),
    (5, 1, 6),
    (5, 2, 12),
    (6, 1, 12),
    (6, 8, 20),
    (7, 1, 12),
    (7, 4, 16),
    (7, 6, 2),
    (7, 7, 1),
    (8, 1, 19),
    (8, 7, 12),
    (9, 2, 1),
    (9, 1, 16),
    (9, 7, 5),
    (10, 1, 2),
    (10, 2, 30),
    (10, 3, 6),
    (11, 1, 20),
    (11, 7, 24),
    (12, 1, 6),
    (12, 2, 15),
    (12, 3, 25),
    (12, 7, 4),
    (12, 5, 3),
    (13, 2, 5),
    (13, 3, 5),
    (13, 7, 5);
    
    

-- Amped (id = 1)
INSERT INTO product_images (product_id,color_id , image_url, u_primary) VALUES
(1,2, '../assets/images/products/amped_1.jpg', 1),
(1,7, '../assets/images/products/amped_2.jpg', 0),
(1,3, '../assets/images/products/amped_3.jpg', 0),
(1,1, '../assets/images/products/amped_4.jpg', 0),
(1, 7,'../assets/images/products/amped_5.jpg', 0);

-- Aviara (id = 2)
INSERT INTO product_images (product_id,color_id , image_url, u_primary) VALUES
(2,6, '../assets/images/products/aviara_1.jpg', 1),
(2,6, '../assets/images/products/aviara_2.jpg', 0),
(2,6, '../assets/images/products/aviara_3.jpg', 0),
(2, 7,'../assets/images/products/aviara_4.jpg', 0),
(2, 1,'../assets/images/products/aviara_5.jpg', 0),
(2,2, '../assets/images/products/aviara_6.jpg', 0);

-- Barlow (id = 3)
INSERT INTO product_images (product_id,color_id , image_url, u_primary) VALUES
(3, 5,'../assets/images/products/barlow_1.jpg', 1),
(3,2, '../assets/images/products/barlow_2.jpg', 0),
(3, 3,'../assets/images/products/barlow_3.jpg', 0),
(3,7, '../assets/images/products/barlow_4.jpg', 0),
(3, 7,'../assets/images/products/barlow_5.jpg', 0),
(3,1, '../assets/images/products/barlow_6.jpg', 0);

-- Brawn (id = 4)
INSERT INTO product_images (product_id,color_id , image_url, u_primary) VALUES
(4, 7,'../assets/images/products/brawn_1.jpg', 1),
(4, 2,'../assets/images/products/brawn_2.jpg', 0),
(4, 1,'../assets/images/products/brawn_3.jpg', 0);

-- Edgewood (id = 5)
INSERT INTO product_images (product_id,color_id , image_url, u_primary) VALUES
(5,2, '../assets/images/products/edgewood_1.jpg', 1),
(5,2, '../assets/images/products/edgewood_2.jpg', 0),
(5,2, '../assets/images/products/edgewood_3.jpg', 0),
(5, 1,'../assets/images/products/edgewood_4.jpg', 0);

-- Influencer (id = 6)
INSERT INTO product_images (product_id,color_id , image_url, u_primary) VALUES
(6, 8,'../assets/images/products/influencer_1.jpg', 1),
(6,1, '../assets/images/products/influencer_2.jpg', 0);

-- Islander (id = 7)
INSERT INTO product_images (product_id,color_id , image_url, u_primary) VALUES
(7,6, '../assets/images/products/islander_1.jpg', 1),
(7,7, '../assets/images/products/islander_2.jpg', 0),
(7, 4,'../assets/images/products/islander_3.jpg', 0),
(7, 7,'../assets/images/products/islander_4.jpg', 0),
(7,1, '../assets/images/products/islander_5.jpg', 0);

-- Kerring (id = 8)
INSERT INTO product_images (product_id,color_id , image_url, u_primary) VALUES
(8, 7,'../assets/images/products/kerring_1.jpg', 1),
(8, 1,'../assets/images/products/kerring_2.jpg', 0);

-- Klear (id = 9)
INSERT INTO product_images (product_id,color_id , image_url, u_primary) VALUES
(9,2, '../assets/images/products/klear_1.jpg', 1),
(9, 7,'../assets/images/products/klear_2.jpg', 0),
(9, 7,'../assets/images/products/klear_3.jpg', 0),
(9, 1,'../assets/images/products/klear_4.jpg', 0);

-- Klich (id = 10)
INSERT INTO product_images (product_id,color_id , image_url, u_primary) VALUES
(10, 2,'../assets/images/products/klinch_1.jpg', 1),
(10, 3,'../assets/images/products/klinch_2.jpg', 0),
(10, 1,'../assets/images/products/klinch_3.jpg', 0);

-- Klout (id = 11)
INSERT INTO product_images (product_id,color_id , image_url, u_primary) VALUES
(11, 7,'../assets/images/products/klout_1.jpg', 1),
(11, 1,'../assets/images/products/klout_2.jpg', 0);

-- Kute (id = 12)
INSERT INTO product_images (product_id,color_id , image_url, u_primary) VALUES
(12, 5,'../assets/images/products/kute_1.jpg', 1),
(12,2, '../assets/images/products/kute_2.jpg', 0),
(12, 3,'../assets/images/products/kute_3.jpg', 0),
(12, 7,'../assets/images/products/kute_4.jpg', 0),
(12, 2,'../assets/images/products/kute_5.jpg', 0),
(12, 1,'../assets/images/products/kute_6.jpg', 0);

-- Kwartet (id = 13)
INSERT INTO product_images (product_id,color_id , image_url, u_primary) VALUES
(13,2, '../assets/images/products/kwartet_1.jpg', 1),
(13, 3,'../assets/images/products/kwartet_2.jpg', 0),
(13, 7,'../assets/images/products/kwartet_3.jpg', 0);

-- Insert sample orders
INSERT INTO orders (user_id, status, created_at, fullname, email, phone_number, address, note, total_money, payment_status)
VALUES
    (1, 'completed', '2025-06-01 10:00:00', 'Admin User', 'admin@example.com', '0123456789', '123 Admin Street, Admin City', 'Please deliver before noon.', 450.00, 'completed'),
    (1, 'pending', '2025-06-10 15:30:00', 'Admin User', 'admin@example.com', '0123456789', '123 Admin Street, Admin City', NULL, 300.50, 'pending'),
    (2, 'processing', '2025-06-05 09:15:00', 'John Doe', 'john.doe@example.com', '0987654321', '456 Elm Street, Sample City', 'Include installation guide.', 720.75, 'pending'),
    (3, 'shipped', '2025-05-20 14:00:00', 'Jane Smith', 'jane.smith@example.com', '0912345678', '789 Oak Avenue, Test Town', 'Urgent delivery.', 250.00, 'completed'),
    (1, 'cancelled', '2025-05-15 11:45:00', 'Admin User', 'admin@example.com', '0123456789', '123 Admin Street, Admin City', 'Cancelled due to wrong item.', 180.25, 'pending');

-- Insert into order_items
INSERT INTO order_items (order_id, product_variant_id, quantity, price, total_money, payment_method)
VALUES
    (1, 1, 2, 220.00, 440.00, 'online'), -- Order 1: 2x Amped (Matte White)
    (2, 3, 1, 300.00, 300.00, 'cash'),   -- Order 2: 1x Influencer (Matte White)
    (3, 5, 3, 240.00, 720.00, 'online'), -- Order 3: 3x Aviara (Driftwood)
    (4, 7, 1, 250.00, 250.00, 'cod'),    -- Order 4: 1x Islander
    (5, 7, 1, 250.00, 250.00, 'online'); -- Order 5: 1x Islander
