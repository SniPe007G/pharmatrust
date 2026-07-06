-- ===========================================
-- Create Database
-- ===========================================
CREATE DATABASE IF NOT EXISTS pharmatrust_db;
USE pharmatrust_db;

-- ===========================================
-- Customer Table
-- ===========================================
CREATE TABLE Customer (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20) UNIQUE,
    email VARCHAR(100) UNIQUE,
    dob DATE,
    address TEXT,
    registration_date DATE DEFAULT CURRENT_DATE
);

-- ===========================================
-- Employee Table
-- ===========================================
CREATE TABLE Employee (
    employee_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20) UNIQUE,
    email VARCHAR(100) UNIQUE,
    hire_date DATE NOT NULL,
    role VARCHAR(50) NOT NULL,
    salary DECIMAL(10,2),
    password VARCHAR(255) NOT NULL
);

-- ===========================================
-- Supplier Table
-- ===========================================
CREATE TABLE Supplier (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT
);

-- ===========================================
-- Medication Table
-- ===========================================
CREATE TABLE Medication (
    medication_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    generic_name VARCHAR(100),
    category VARCHAR(50),
    strength VARCHAR(50),
    unit_price DECIMAL(10,2),
    stock_quantity INT,
    expiry_date DATE,
    reorder_level INT DEFAULT 10,
    supplier_id INT,
    FOREIGN KEY (supplier_id)
        REFERENCES Supplier(supplier_id)
);

-- ===========================================
-- Prescription Table
-- ===========================================
CREATE TABLE Prescription (
    prescription_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    medication_id INT NOT NULL,
    issue_date DATE NOT NULL,
    refill_count INT DEFAULT 0,
    doctor_name VARCHAR(100),
    expiry_date DATE,
    FOREIGN KEY (customer_id)
        REFERENCES Customer(customer_id),
    FOREIGN KEY (medication_id)
        REFERENCES Medication(medication_id)
);

-- ===========================================
-- Sale Table
-- ===========================================
CREATE TABLE Sale (
    sale_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    employee_id INT NOT NULL,
    sale_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10,2),
    FOREIGN KEY (customer_id)
        REFERENCES Customer(customer_id),
    FOREIGN KEY (employee_id)
        REFERENCES Employee(employee_id)
);

-- ===========================================
-- SaleItem Table
-- ===========================================
CREATE TABLE SaleItem (
    sale_item_id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    medication_id INT NOT NULL,
    quantity INT,
    unit_price_at_sale DECIMAL(10,2),
    FOREIGN KEY (sale_id)
        REFERENCES Sale(sale_id)
        ON DELETE CASCADE,
    FOREIGN KEY (medication_id)
        REFERENCES Medication(medication_id)
);

-- ===========================================
-- Consultation Table
-- ===========================================
CREATE TABLE Consultation (
    consultation_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    employee_id INT NOT NULL,
    consultation_date DATETIME NOT NULL,
    notes TEXT,
    duration_minutes INT,
    FOREIGN KEY (customer_id)
        REFERENCES Customer(customer_id),
    FOREIGN KEY (employee_id)
        REFERENCES Employee(employee_id)
);

-- ===========================================
-- Sample Suppliers
-- ===========================================
INSERT INTO Supplier
(name, contact_person, phone, email, address)
VALUES
('MediSource Ltd', 'John Doe', '1234567890', 'john@medisource.com', '123 Health St, City'),
('PharmaDistributors', 'Jane Smith', '0987654321', 'jane@pharmadist.com', '456 Supply Ave, Town'),
('HealthPlus Supplies', 'Bob Johnson', '5551234567', 'bob@healthplus.com', '789 Medical Blvd, Metro');

-- ===========================================
-- Sample Medications
-- ===========================================
INSERT INTO Medication
(name, generic_name, category, strength, unit_price, stock_quantity, expiry_date, reorder_level, supplier_id)
VALUES
('Panadol', 'Paracetamol', 'Painkiller', '500mg', 5.99, 100, '2027-12-31', 10, 1),
('Amoxil', 'Amoxicillin', 'Antibiotic', '250mg', 12.50, 50, '2026-08-15', 5, 2),
('Advil', 'Ibuprofen', 'Anti-inflammatory', '200mg', 8.99, 75, '2027-06-30', 8, 1),
('Ventolin', 'Salbutamol', 'Asthma', '100mcg', 22.50, 30, '2026-12-31', 5, 3),
('Zyrtec', 'Cetirizine', 'Antihistamine', '10mg', 15.99, 60, '2027-09-15', 6, 2);

-- ===========================================
-- Employees
-- ===========================================
INSERT INTO Employee
(first_name, last_name, phone, email, hire_date, role, salary, password)
VALUES
('Charlie', 'Pharmacist', '7778889999', 'charlie@pharmatrust.com', '2022-06-01', 'Pharmacist', 75000.00, MD5('password123')),
('Diana', 'Assistant', '0001112222', 'diana@pharmatrust.com', '2023-09-15', 'Sales Assistant', 35000.00, MD5('password123')),
('Admin', 'User', '9998887777', 'admin@pharmatrust.com', '2024-01-01', 'Admin', 50000.00, MD5('admin123'));

-- ===========================================
-- Customers
-- ===========================================
INSERT INTO Customer
(first_name, last_name, phone, email, dob, address)
VALUES
('Alice', 'Wonder', '1112223333', 'alice@email.com', '1990-01-01', '789 Oak St, City'),
('Bob', 'Builder', '4445556666', 'bob@email.com', '1985-05-05', '321 Pine St, Town'),
('Carol', 'Davis', '7778880000', 'carol@email.com', '1992-07-15', '456 Maple Ave, Metro');

-- ===========================================
-- Sales (Correct Totals)
-- ===========================================
INSERT INTO Sale
(customer_id, employee_id, total_amount)
VALUES
(1, 1, 24.48),
(2, 2, 12.50),
(1, 1, 23.97);

-- ===========================================
-- Sale Items
-- ===========================================
INSERT INTO SaleItem
(sale_id, medication_id, quantity, unit_price_at_sale)
VALUES
(1, 1, 2, 5.99),
(1, 2, 1, 12.50),
(2, 2, 1, 12.50),
(3, 1, 1, 5.99),
(3, 3, 2, 8.99);

-- ===========================================
-- Prescriptions
-- ===========================================
INSERT INTO Prescription
(customer_id, medication_id, issue_date, refill_count, doctor_name, expiry_date)
VALUES
(1, 2, '2026-07-01', 2, 'Dr. House', '2026-10-01'),
(2, 1, '2026-07-02', 1, 'Dr. Wilson', '2026-09-15');

-- ===========================================
-- Consultations
-- ===========================================
INSERT INTO Consultation
(customer_id, employee_id, consultation_date, notes, duration_minutes)
VALUES
(1, 1, '2026-07-02 10:30:00',
'Discussed allergy medication options', 15),

(2, 1, '2026-07-03 14:00:00',
'Blood pressure medication review', 20);