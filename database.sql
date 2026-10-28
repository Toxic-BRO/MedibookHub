
DROP DATABASE IF EXISTS doctor_appointment;
CREATE DATABASE doctor_appointment
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE doctor_appointment;

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('patient','doctor','admin') NOT NULL DEFAULT 'patient',
    status        ENUM('active','pending','blocked') NOT NULL DEFAULT 'active',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role_status (role, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE doctors (
    user_id     INT UNSIGNED NOT NULL,
    specialty   VARCHAR(100) NOT NULL,
    fee         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    experience  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bio         TEXT NULL,
    location    VARCHAR(150) NOT NULL DEFAULT '',
    photo       VARCHAR(255) NULL,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (user_id),
    KEY idx_doctors_specialty (specialty),
    KEY idx_doctors_location (location),
    CONSTRAINT fk_doctors_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE patients (
    user_id INT UNSIGNED NOT NULL,
    age     TINYINT UNSIGNED NULL,
    gender  ENUM('male','female','other') NULL,
    phone   VARCHAR(30) NULL,
    address VARCHAR(255) NULL,
    PRIMARY KEY (user_id),
    CONSTRAINT fk_patients_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE availability (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    doctor_id    INT UNSIGNED NOT NULL,
    day_of_week  TINYINT UNSIGNED NOT NULL,
    start_time   TIME NOT NULL,
    end_time     TIME NOT NULL,
    slot_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    PRIMARY KEY (id),
    UNIQUE KEY uq_availability_slot (doctor_id, day_of_week, start_time),
    KEY idx_availability_doctor_day (doctor_id, day_of_week),
    CONSTRAINT fk_availability_doctor FOREIGN KEY (doctor_id)
        REFERENCES doctors (user_id) ON DELETE CASCADE,
    CONSTRAINT chk_availability_day CHECK (day_of_week BETWEEN 0 AND 6),
    CONSTRAINT chk_availability_order CHECK (end_time > start_time),
    CONSTRAINT chk_availability_slot CHECK (slot_minutes BETWEEN 5 AND 240)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE appointments (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    patient_id   INT UNSIGNED NOT NULL,
    doctor_id    INT UNSIGNED NOT NULL,
    `date`       DATE NOT NULL,
    `time`       TIME NOT NULL,
    status       ENUM('pending','confirmed','rejected','cancelled','completed')
                 NOT NULL DEFAULT 'pending',
    reason       VARCHAR(500) NULL,
    notes        TEXT NULL,
    prescription TEXT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    slot_lock    TINYINT GENERATED ALWAYS AS (
                     CASE WHEN status IN ('pending','confirmed','completed')
                          THEN 1 ELSE NULL END
                 ) STORED,
    PRIMARY KEY (id),
    UNIQUE KEY uq_appointment_slot (doctor_id, `date`, `time`, slot_lock),
    KEY idx_appointments_patient (patient_id, `date`),
    KEY idx_appointments_doctor (doctor_id, `date`),
    KEY idx_appointments_status (status),
    CONSTRAINT fk_appointments_patient FOREIGN KEY (patient_id)
        REFERENCES patients (user_id) ON DELETE CASCADE,
    CONSTRAINT fk_appointments_doctor FOREIGN KEY (doctor_id)
        REFERENCES doctors (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reviews (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    appointment_id INT UNSIGNED NOT NULL,
    rating         TINYINT UNSIGNED NOT NULL,
    comment        TEXT NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_reviews_appointment (appointment_id),
    CONSTRAINT fk_reviews_appointment FOREIGN KEY (appointment_id)
        REFERENCES appointments (id) ON DELETE CASCADE,
    CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE complaints (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    patient_id INT UNSIGNED NOT NULL,
    doctor_id  INT UNSIGNED NULL,
    message    TEXT NOT NULL,
    status     ENUM('open','in_review','resolved') NOT NULL DEFAULT 'open',
    admin_reply TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_complaints_status (status),
    CONSTRAINT fk_complaints_patient FOREIGN KEY (patient_id)
        REFERENCES patients (user_id) ON DELETE CASCADE,
    CONSTRAINT fk_complaints_doctor FOREIGN KEY (doctor_id)
        REFERENCES doctors (user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NOT NULL,
    message    VARCHAR(500) NOT NULL,
    link       VARCHAR(255) NULL,
    is_read    TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notifications_user (user_id, is_read),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (id, name, email, password_hash, role, status) VALUES
(1, 'System Admin', 'admin@medibook.test',
 '$2y$10$2qZE525Y8XEYLJzsd7swmOHvY54Gr74DWA6.ZhL2MNNP57QnDF6wG',
 'admin', 'active');

INSERT INTO users (id, name, email, password_hash, role, status) VALUES
(2, 'John Carter', 'patient@medibook.test',
 '$2y$10$uEXGcjNmmf6GPDv78Swd/OfqpDnN7IfoP0E59LA5kUR/T6LxNqWUm',
 'patient', 'active'),
(3, 'Emily Stone', 'emily@medibook.test',
 '$2y$10$uEXGcjNmmf6GPDv78Swd/OfqpDnN7IfoP0E59LA5kUR/T6LxNqWUm',
 'patient', 'active');

INSERT INTO patients (user_id, age, gender, phone, address) VALUES
(2, 34, 'male',   '+1 212 555 0147', '221B Baker Street, New York'),
(3, 28, 'female', '+1 212 555 0192', '88 Atlantic Ave, Brooklyn');

INSERT INTO users (id, name, email, password_hash, role, status) VALUES
(4, 'Dr. Sarah Mitchell', 'sarah@medibook.test',
 '$2y$10$9L6mGirIoUN2ZXY17WYnVuDIe5UyURYKEe.wKxwpKwebqGIhZP7MK',
 'doctor', 'active'),
(5, 'Dr. David Chen', 'david@medibook.test',
 '$2y$10$9L6mGirIoUN2ZXY17WYnVuDIe5UyURYKEe.wKxwpKwebqGIhZP7MK',
 'doctor', 'active'),
(6, 'Dr. Aisha Rahman', 'aisha@medibook.test',
 '$2y$10$9L6mGirIoUN2ZXY17WYnVuDIe5UyURYKEe.wKxwpKwebqGIhZP7MK',
 'doctor', 'active'),
(7, 'Dr. Mark Patel', 'mark@medibook.test',
 '$2y$10$9L6mGirIoUN2ZXY17WYnVuDIe5UyURYKEe.wKxwpKwebqGIhZP7MK',
 'doctor', 'pending');

INSERT INTO doctors (user_id, specialty, fee, experience, bio, location, photo, is_verified) VALUES
(4, 'Cardiology', 120.00, 12,
 'Board-certified cardiologist specialising in preventive heart care, arrhythmia and post-surgical follow-up. I take the time to explain every test result in plain language.',
 'New York, NY', NULL, 1),
(5, 'Dermatology', 90.00, 8,
 'Dermatologist focused on acne, eczema and skin-cancer screening. Fifteen years of combined clinical and cosmetic practice.',
 'Brooklyn, NY', NULL, 1),
(6, 'Pediatrics', 75.00, 6,
 'Paediatrician for newborns through teens. Calm with anxious first-timers, and happy to answer the two hundred questions parents bring along.',
 'Queens, NY', NULL, 1),
(7, 'General Medicine', 60.00, 4,
 'General practitioner covering everyday illness, chronic-condition management and annual check-ups.',
 'Jersey City, NJ', NULL, 0);

INSERT INTO availability (doctor_id, day_of_week, start_time, end_time, slot_minutes) VALUES
(4, 1, '09:00:00', '13:00:00', 30),
(4, 2, '09:00:00', '13:00:00', 30),
(4, 3, '09:00:00', '13:00:00', 30),
(4, 4, '09:00:00', '13:00:00', 30),
(4, 5, '09:00:00', '13:00:00', 30);

INSERT INTO availability (doctor_id, day_of_week, start_time, end_time, slot_minutes) VALUES
(5, 1, '14:00:00', '18:00:00', 30),
(5, 3, '14:00:00', '18:00:00', 30),
(5, 5, '14:00:00', '18:00:00', 30);

INSERT INTO availability (doctor_id, day_of_week, start_time, end_time, slot_minutes) VALUES
(6, 2, '10:00:00', '14:00:00', 20),
(6, 4, '10:00:00', '14:00:00', 20),
(6, 6, '10:00:00', '14:00:00', 20);

INSERT INTO appointments (id, patient_id, doctor_id, `date`, `time`, status, reason, notes, prescription, created_at) VALUES
(1, 2, 4, CURDATE() - INTERVAL 14 DAY, '10:00:00', 'completed',
 'Chest tightness after exercise',
 'ECG normal. Mild hypertension, likely stress related. Advised 30 min cardio 4x/week, reduce caffeine, recheck BP in 3 months.',
 'Lisinopril 5mg once daily (30 days)\nAspirin 75mg once daily (30 days)',
 NOW() - INTERVAL 14 DAY),
(2, 3, 5, CURDATE() - INTERVAL 7 DAY, '15:00:00', 'completed',
 'Persistent rash on both forearms',
 'Contact dermatitis, probably from a new detergent. No infection. Patch test recommended if it recurs.',
 'Hydrocortisone 1% cream twice daily for 10 days\nCetirizine 10mg at night as needed',
 NOW() - INTERVAL 7 DAY),
(3, 2, 6, CURDATE() + INTERVAL 2 DAY, '10:20:00', 'confirmed',
 'Annual check-up for my 6 year old',
 NULL, NULL, NOW() - INTERVAL 1 DAY),
(4, 3, 4, CURDATE() + INTERVAL 1 DAY, '09:30:00', 'pending',
 'Follow-up on blood pressure readings',
 NULL, NULL, NOW() - INTERVAL 3 HOUR),
(5, 3, 4, CURDATE() + INTERVAL 3 DAY, '11:00:00', 'cancelled',
 'Migraine consultation',
 NULL, NULL, NOW() - INTERVAL 2 DAY);

INSERT INTO reviews (appointment_id, rating, comment, created_at) VALUES
(1, 5, 'Dr. Mitchell actually listened. She explained the ECG line by line and the follow-up plan was clear. Front desk never entered the picture, which is exactly what I wanted.', NOW() - INTERVAL 13 DAY),
(2, 4, 'Rash cleared up in under a week. Slightly rushed at the end but the prescription worked.', NOW() - INTERVAL 6 DAY);

INSERT INTO complaints (patient_id, doctor_id, message, status, created_at) VALUES
(3, 5, 'The 15:00 appointment started about 25 minutes late and I was not told why.', 'open', NOW() - INTERVAL 5 DAY);

INSERT INTO notifications (user_id, message, link, is_read, created_at) VALUES
(2, CONCAT('Your appointment with Dr. Aisha Rahman is confirmed for ',
           DATE_FORMAT(CURDATE() + INTERVAL 2 DAY, '%a %d %b'), ' at 10:20.'),
    '/patient/appointments.php', 0, NOW() - INTERVAL 1 DAY),
(3, 'Dr. Sarah Mitchell has not responded to your booking request yet.', '/patient/appointments.php', 0, NOW() - INTERVAL 3 HOUR),
(4, CONCAT('New booking request from Emily Stone for ',
           DATE_FORMAT(CURDATE() + INTERVAL 1 DAY, '%a %d %b'), ' at 09:30.'),
    '/doctor/appointments.php', 0, NOW() - INTERVAL 3 HOUR);
