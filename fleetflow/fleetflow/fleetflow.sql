-- FleetFlow Database
CREATE DATABASE IF NOT EXISTS fleetflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fleetflow;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('manager','dispatcher','safety_officer','financial_analyst') NOT NULL DEFAULT 'dispatcher',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Vehicles table
CREATE TABLE IF NOT EXISTS vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    license_plate VARCHAR(50) NOT NULL UNIQUE,
    type ENUM('Truck','Van','Bike') NOT NULL DEFAULT 'Van',
    max_capacity DECIMAL(10,2) NOT NULL COMMENT 'in kg',
    odometer DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'in km',
    acquisition_cost DECIMAL(12,2) DEFAULT 0,
    region VARCHAR(100) DEFAULT '',
    status ENUM('Available','On Trip','In Shop','Retired') NOT NULL DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Drivers table
CREATE TABLE IF NOT EXISTS drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150),
    phone VARCHAR(20),
    license_number VARCHAR(50) NOT NULL UNIQUE,
    license_category VARCHAR(50) DEFAULT 'Van',
    license_expiry DATE NOT NULL,
    safety_score DECIMAL(4,2) DEFAULT 100.00,
    trips_completed INT DEFAULT 0,
    trips_total INT DEFAULT 0,
    status ENUM('On Duty','Off Duty','Suspended') NOT NULL DEFAULT 'Off Duty',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Trips table
CREATE TABLE IF NOT EXISTS trips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    driver_id INT NOT NULL,
    origin VARCHAR(200) NOT NULL,
    destination VARCHAR(200) NOT NULL,
    cargo_description VARCHAR(255) DEFAULT '',
    cargo_weight DECIMAL(10,2) NOT NULL COMMENT 'in kg',
    distance_km DECIMAL(10,2) DEFAULT 0,
    revenue DECIMAL(12,2) DEFAULT 0,
    start_odometer DECIMAL(10,2) DEFAULT 0,
    end_odometer DECIMAL(10,2) DEFAULT 0,
    status ENUM('Draft','Dispatched','Completed','Cancelled') NOT NULL DEFAULT 'Draft',
    start_date DATETIME DEFAULT NULL,
    end_date DATETIME DEFAULT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE
);

-- Maintenance logs table
CREATE TABLE IF NOT EXISTS maintenance_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    service_type VARCHAR(150) NOT NULL,
    description TEXT,
    cost DECIMAL(12,2) DEFAULT 0,
    service_date DATE NOT NULL,
    completed_date DATE DEFAULT NULL,
    status ENUM('Pending','In Progress','Completed') DEFAULT 'In Progress',
    technician VARCHAR(100) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
);

-- Fuel logs table
CREATE TABLE IF NOT EXISTS fuel_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    trip_id INT DEFAULT NULL,
    liters DECIMAL(8,2) NOT NULL,
    cost_per_liter DECIMAL(8,2) NOT NULL,
    total_cost DECIMAL(12,2) NOT NULL,
    odometer_at_fill DECIMAL(10,2) DEFAULT 0,
    fuel_date DATE NOT NULL,
    station VARCHAR(150) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE SET NULL
);

-- Insert default admin user (password: admin123)
INSERT INTO users (name, email, password, role) VALUES
('Fleet Manager', 'manager@fleetflow.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager'),
('John Dispatcher', 'dispatcher@fleetflow.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dispatcher'),
('Safety Officer', 'safety@fleetflow.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'safety_officer'),
('Finance Analyst', 'finance@fleetflow.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'financial_analyst');

-- Insert sample vehicles
INSERT INTO vehicles (name, model, license_plate, type, max_capacity, odometer, acquisition_cost, region, status) VALUES
('Van-01', 'Toyota HiAce 2022', 'GJ-01-AA-1234', 'Van', 800, 12500, 1500000, 'North', 'Available'),
('Van-02', 'Ford Transit 2021', 'GJ-01-AB-5678', 'Van', 1000, 8300, 1800000, 'South', 'Available'),
('Truck-01', 'Tata 407 2020', 'GJ-01-AC-9012', 'Truck', 4000, 45200, 3500000, 'North', 'Available'),
('Truck-02', 'Ashok Leyland 2019', 'GJ-01-AD-3456', 'Truck', 7500, 98000, 5000000, 'East', 'In Shop'),
('Bike-01', 'Honda Activa 2023', 'GJ-01-AE-7890', 'Bike', 50, 3200, 80000, 'West', 'Available'),
('Van-05', 'Maruti Eeco 2022', 'GJ-01-AF-1111', 'Van', 500, 6700, 650000, 'South', 'Available');

-- Insert sample drivers
INSERT INTO drivers (name, email, phone, license_number, license_category, license_expiry, safety_score, trips_completed, trips_total, status) VALUES
('Alex Kumar', 'alex@fleetflow.com', '9876543210', 'DL-GJ-2019-001234', 'Van', '2026-12-31', 94.50, 45, 47, 'On Duty'),
('Raj Patel', 'raj@fleetflow.com', '9876543211', 'DL-GJ-2018-005678', 'Truck', '2025-06-30', 87.00, 102, 108, 'On Duty'),
('Suresh Mehta', 'suresh@fleetflow.com', '9876543212', 'DL-GJ-2020-009012', 'Van', '2027-03-15', 98.00, 30, 30, 'Off Duty'),
('Priya Shah', 'priya@fleetflow.com', '9876543213', 'DL-GJ-2021-003456', 'Bike', '2024-01-15', 75.00, 12, 15, 'On Duty'),
('Mohan Das', 'mohan@fleetflow.com', '9876543214', 'DL-GJ-2017-007890', 'Truck', '2026-08-20', 91.50, 200, 210, 'Off Duty');

-- Sample maintenance log
INSERT INTO maintenance_logs (vehicle_id, service_type, description, cost, service_date, status, technician) VALUES
(4, 'Engine Overhaul', 'Full engine check and overhaul required', 45000, '2025-02-15', 'In Progress', 'Ram Auto Works');

-- Sample fuel logs
INSERT INTO fuel_logs (vehicle_id, liters, cost_per_liter, total_cost, odometer_at_fill, fuel_date, station) VALUES
(1, 40, 96.50, 3860, 12450, '2025-02-10', 'HP Petrol Pump'),
(2, 55, 96.50, 5307.50, 8200, '2025-02-12', 'BPCL Station'),
(3, 80, 90.00, 7200, 45100, '2025-02-14', 'Indian Oil');
