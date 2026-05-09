-- ============================================================
-- BULIGA – Volunteer Management Platform
-- Database Schema
-- IT26 Final Project
-- ============================================================

CREATE DATABASE IF NOT EXISTS buliga_webapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE buliga_webapp;

-- ============================================================
-- Drop tables in reverse dependency order (children before parents)
-- ============================================================
DROP TABLE IF EXISTS announcements;
DROP TABLE IF EXISTS registrations;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS users;

-- ============================================================
-- TABLE: users
-- Stores both Student Volunteers and Event Organizers
-- ============================================================
CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    full_name   VARCHAR(150)    NOT NULL,
    email       VARCHAR(180)    NOT NULL UNIQUE,
    password    VARCHAR(255)    NOT NULL,                    -- bcrypt hashed
    role        ENUM('student','organizer') NOT NULL DEFAULT 'student',
    avatar_url  VARCHAR(255)    DEFAULT NULL,
    bio         TEXT            DEFAULT NULL,
    created_at  DATETIME        DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- TABLE: events
-- Created by organizers; browsed by students
-- ============================================================
CREATE TABLE events (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    organizer_id    INT             NOT NULL,
    title           VARCHAR(220)    NOT NULL,
    description     TEXT            NOT NULL,
    location        VARCHAR(255)    NOT NULL,
    event_date      DATE            NOT NULL,
    start_time      TIME            NOT NULL,
    end_time        TIME            NOT NULL,
    slots           INT             NOT NULL DEFAULT 20,     -- max volunteers
    image_url       VARCHAR(255)    DEFAULT NULL,
    status          ENUM('open','closed','cancelled') NOT NULL DEFAULT 'open',
    created_at      DATETIME        DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_events_organizer
        FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE: registrations
-- Students register for events
-- ============================================================
CREATE TABLE registrations (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    student_id      INT             NOT NULL,
    event_id        INT             NOT NULL,
    status          ENUM('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
    hours_rendered  DECIMAL(5,2)    DEFAULT 0.00,
    registered_at   DATETIME        DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_student_event (student_id, event_id),
    CONSTRAINT fk_reg_student
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_reg_event
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE: announcements
-- Organizers broadcast messages to registered volunteers
-- ============================================================
CREATE TABLE announcements (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    event_id    INT             NOT NULL,
    author_id   INT             NOT NULL,                    -- organizer user id
    title       VARCHAR(220)    NOT NULL,
    body        TEXT            NOT NULL,
    created_at  DATETIME        DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ann_event
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_ann_author
        FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- SEED DATA – Demo accounts & sample events
-- ============================================================

-- Passwords are "password123" hashed with bcrypt cost=10
INSERT INTO users (full_name, email, password, role, bio) VALUES
('Admin Organizer',  'organizer@buliga.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'organizer', 'Campus volunteer coordinator.'),
('Juan Dela Cruz',   'juan@buliga.edu',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student',   'Passionate about community service.'),
('Maria Santos',     'maria@buliga.edu',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student',   'Environmental advocate.'),
('Carlos Reyes',     'carlos@buliga.edu',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student',   'IT student who loves outreach.');

INSERT INTO events (organizer_id, title, description, location, event_date, start_time, end_time, slots, status) VALUES
(1, 'Coastal Clean-Up Drive',        'Help clean our local beaches and waterways to protect marine life.',    'Macajalar Bay, CDO',       '2025-06-15', '07:00:00', '12:00:00', 30, 'open'),
(1, 'Tree Planting Activity',        'Plant 500 trees at the city watershed area to fight deforestation.',   'Cagayan de Oro Watershed',  '2025-06-22', '06:00:00', '11:00:00', 50, 'open'),
(1, 'Literacy for All Program',      'Teach basic reading and writing to out-of-school youth in Barangay.',  'Barangay Kauswagan, CDO',  '2025-07-05', '09:00:00', '15:00:00', 20, 'open'),
(1, 'Blood Donation Campaign',       'Donate blood and save lives. Open to all healthy individuals.',        'Northern Mindanao Medical', '2025-07-12', '08:00:00', '16:00:00', 100,'open'),
(1, 'Feeding Program – Brgy. Isla', 'Prepare and serve nutritious meals to underprivileged children.',      'Barangay Isla, CDO',        '2025-05-30', '10:00:00', '13:00:00', 25, 'closed');

INSERT INTO registrations (student_id, event_id, status, hours_rendered) VALUES
(2, 1, 'approved',   0.00),
(2, 2, 'pending',    0.00),
(2, 5, 'completed',  3.00),
(3, 1, 'approved',   0.00),
(3, 3, 'pending',    0.00),
(3, 5, 'completed',  3.00),
(4, 2, 'approved',   0.00),
(4, 4, 'pending',    0.00);

INSERT INTO announcements (event_id, author_id, title, body) VALUES
(1, 1, 'Reminder: Bring gloves and sunscreen', 'Please remember to bring protective gear. Gloves and trash bags will be provided on-site.'),
(1, 1, 'Meeting point update',                 'We will now meet at the Cogon Market terminal at 6:30 AM for a carpool to the site.'),
(5, 1, 'Thank you volunteers!',               'Thank you all for your incredible effort at the Feeding Program. We served 120 children today!');