-- Campus Lost-and-Found Portal Schema
-- Database: lost_and_found_db

-- Drop tables if they already exist (in reverse dependency order)
DROP TABLE IF EXISTS activity_logs CASCADE;
DROP TABLE IF EXISTS claims CASCADE;
DROP TABLE IF EXISTS items CASCADE;
DROP TABLE IF EXISTS categories CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- 1. Users Table
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role VARCHAR(20) NOT NULL DEFAULT 'user' CHECK (role IN ('user', 'admin')),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 2. Categories Table
CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(60) UNIQUE NOT NULL,
    icon VARCHAR(60) DEFAULT 'fa-box',
    description VARCHAR(255)
);

-- 3. Items Table (Lost or Found)
CREATE TABLE items (
    id SERIAL PRIMARY KEY,
    item_type VARCHAR(10) NOT NULL CHECK (item_type IN ('lost', 'found')),
    title VARCHAR(150) NOT NULL,
    category_id INT NOT NULL REFERENCES categories(id) ON DELETE RESTRICT,
    location VARCHAR(150) NOT NULL,
    event_date DATE NOT NULL,
    description TEXT NOT NULL,
    image_url VARCHAR(255),
    status VARCHAR(20) NOT NULL DEFAULT 'Open' CHECK (status IN ('Open', 'Claim Requested', 'Resolved')),
    user_id INT REFERENCES users(id) ON DELETE SET NULL,
    contact_name VARCHAR(100) NOT NULL,
    contact_email VARCHAR(150) NOT NULL,
    contact_phone VARCHAR(20),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Index for searching and filtering performance
CREATE INDEX idx_items_type_status ON items (item_type, status);
CREATE INDEX idx_items_category ON items (category_id);
CREATE INDEX idx_items_event_date ON items (event_date);
CREATE INDEX idx_items_title_location ON items (title, location);

-- 4. Claims Table
CREATE TABLE claims (
    id SERIAL PRIMARY KEY,
    item_id INT NOT NULL REFERENCES items(id) ON DELETE CASCADE,
    claimant_name VARCHAR(100) NOT NULL,
    claimant_email VARCHAR(150) NOT NULL,
    claimant_phone VARCHAR(20),
    proof_description TEXT NOT NULL,
    proof_image_url VARCHAR(255),
    status VARCHAR(20) NOT NULL DEFAULT 'Pending' CHECK (status IN ('Pending', 'Approved', 'Rejected')),
    admin_notes TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP WITH TIME ZONE
);

CREATE INDEX idx_claims_item ON claims (item_id);
CREATE INDEX idx_claims_status ON claims (status);

-- 5. Activity Logs (Audit Trail for status transitions)
CREATE TABLE activity_logs (
    id SERIAL PRIMARY KEY,
    item_id INT REFERENCES items(id) ON DELETE CASCADE,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Seed Initial Categories
INSERT INTO categories (name, icon, description) VALUES
('Electronics', 'fa-laptop', 'Laptops, phones, chargers, earphones, smartwatches, etc.'),
('Student IDs & Cards', 'fa-id-card', 'Campus ID cards, driver licenses, ATM/credit cards, etc.'),
('Wallets & Purses', 'fa-wallet', 'Wallets, purses, pouches, coin bags, etc.'),
('Keys & Keychains', 'fa-key', 'Room keys, bike/car keys, locker keys, etc.'),
('Books & Stationery', 'fa-book', 'Notebooks, textbooks, calculators, pencil pouches, etc.'),
('Bags & Backpacks', 'fa-backpack', 'Backpacks, laptop sleeves, tote bags, etc.'),
('Clothing & Accessories', 'fa-tshirt', 'Jackets, hoodies, caps, glasses, umbrellas, etc.'),
('Others', 'fa-shapes', 'Bottles, sports items, misc campus personal belongings');

-- Seed Default Admin User (Password: admin123, hashed using PHP password_hash PASSWORD_BCRYPT)
-- $2y$10$wE0v7x6o2q0z4fD5jG.3ceN1s3Zl0UuW7d6QyEaN.a.F5H9Z8zB0m corresponds to 'admin123'
INSERT INTO users (name, email, password_hash, phone, role) VALUES
('Campus Admin', 'admin@campus.edu', '$2y$10$dYGYce77NupaLTYM55BwO.5Tl8I52m8uKH.1pYfg1oajPgQOX82/W', '+1-555-0199', 'admin'),
('Demo Student', 'student@campus.edu', '$2y$10$dYGYce77NupaLTYM55BwO.5Tl8I52m8uKH.1pYfg1oajPgQOX82/W', '+1-555-0144', 'user');

-- Seed Sample Items
INSERT INTO items (item_type, title, category_id, location, event_date, description, status, contact_name, contact_email, contact_phone) VALUES
('lost', 'Blue Hydro Flask Water Bottle', 8, 'Central Library 2nd Floor', CURRENT_DATE - INTERVAL '2 days', '32oz wide-mouth blue hydro flask with campus stickers on the side.', 'Open', 'John Doe', 'john@campus.edu', '+1-555-0101'),
('found', 'Casio FX-991EX Scientific Calculator', 5, 'Engineering Block Room 204', CURRENT_DATE - INTERVAL '1 day', 'Black scientific calculator found on desk row 3 after calculus lecture.', 'Open', 'Sarah Smith', 'sarah@campus.edu', '+1-555-0102'),
('lost', 'AirPods Pro with Black Silicon Case', 1, 'Student Dining Hall / Cafeteria', CURRENT_DATE - INTERVAL '3 days', 'White AirPods Pro 2nd Gen inside a matte black silicone case.', 'Claim Requested', 'Michael Lee', 'mlee@campus.edu', '+1-555-0103'),
('found', 'Brown Leather RFID Wallet', 3, 'Campus Sports Complex', CURRENT_DATE - INTERVAL '4 days', 'Brown bifold leather wallet containing cash and a student discount card.', 'Resolved', 'Security Desk', 'security@campus.edu', '+1-555-0100');

