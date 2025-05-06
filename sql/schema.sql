-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS employee_management;
USE employee_management;

-- Table for users (admin/staff)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
);

-- Table for employees
CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    department VARCHAR(50) NOT NULL,
    position VARCHAR(50) NOT NULL,
    hire_date DATE NOT NULL,
    salary DECIMAL(10, 2),
    address TEXT,
    profile_image VARCHAR(255) DEFAULT 'default.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
);

-- Table for activity logging
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action TEXT NOT NULL,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default admin user (password: admin123)
INSERT IGNORE INTO users (username, password, email, full_name, role) 
VALUES ('admin', 'admin123', 'admin@example.com', 'Admin User', 'admin');

-- Insert sample employees
INSERT IGNORE INTO employees (name, email, phone, department, position, hire_date, salary, address) VALUES
('John Doe', 'john@example.com', '555-1234', 'Engineering', 'Senior Developer', '2020-01-15', 85000.00, '123 Main St, Anytown, USA'),
('Jane Smith', 'jane@example.com', '555-5678', 'Marketing', 'Marketing Manager', '2019-06-10', 75000.00, '456 Oak Ave, Somewhere, USA'),
('Michael Brown', 'michael@example.com', '555-9012', 'HR', 'HR Specialist', '2021-03-22', 65000.00, '789 Pine Rd, Nowhere, USA'),
('Sarah Johnson', 'sarah@example.com', '555-3456', 'Finance', 'Financial Analyst', '2018-11-05', 72000.00, '101 Elm St, Everywhere, USA'),
('David Wilson', 'david@example.com', '555-7890', 'Engineering', 'Junior Developer', '2022-02-01', 60000.00, '202 Maple Dr, Anywhere, USA');

-- Create indexes for better performance
CREATE INDEX idx_employees_department ON employees(department);
CREATE INDEX idx_employees_position ON employees(position);
CREATE INDEX idx_employees_hire_date ON employees(hire_date);
CREATE INDEX idx_activity_log_user_id ON activity_log(user_id);
CREATE INDEX idx_activity_log_created_at ON activity_log(created_at);
