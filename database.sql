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

