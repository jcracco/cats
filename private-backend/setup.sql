-- ============================================================
-- CATS — Database Setup Script v2.0
-- Run once on a fresh install as MySQL root / admin user
-- ============================================================

-- 1. Create database and user
-- ============================================================
CREATE DATABASE IF NOT EXISTS cats_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'cats_user'@'localhost' IDENTIFIED BY 'cats_pass';  -- ← change password
GRANT ALL PRIVILEGES ON cats_db.* TO 'cats_user'@'localhost';
FLUSH PRIVILEGES;

USE cats_db;


-- 2. users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username            VARCHAR(100)                NOT NULL,
    password_hash       VARCHAR(255)                NOT NULL,
    is_admin            TINYINT                     NOT NULL DEFAULT 0,
    share_token         VARCHAR(64)                 DEFAULT NULL,  -- reserved for v3 read-only share
    created_at          TIMESTAMP                   NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 3. applications
-- ============================================================
CREATE TABLE IF NOT EXISTS applications (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED                NOT NULL,
    date_applied        DATE                        NOT NULL,

    -- Company / recruiting firm
    company             VARCHAR(255)                DEFAULT NULL,
    via_recruiting_firm TINYINT                     NOT NULL DEFAULT 0,
    recruiting_firm     VARCHAR(255)                DEFAULT NULL,

    -- Role
    job_title           VARCHAR(255)                NOT NULL,
    job_id              VARCHAR(100)                DEFAULT NULL,
    job_link            TEXT                        DEFAULT NULL,
    dashboard_link      TEXT                        DEFAULT NULL,

    -- Location
    location_type       ENUM('Remote','Hybrid')     NOT NULL DEFAULT 'Remote',
    hybrid_location     VARCHAR(255)                DEFAULT NULL,
    days_onsite         VARCHAR(50)                 DEFAULT NULL,

    -- Application channel
    source              VARCHAR(100)                DEFAULT NULL,
    applied_through     VARCHAR(100)                DEFAULT NULL,

    -- Resume
    resume_version      ENUM('TPM','SPO','TIM','Custom') DEFAULT NULL,

    -- Rating & status
    rating              TINYINT UNSIGNED            DEFAULT NULL,
    status              ENUM(
                            'Applied',
                            'Interviewing',
                            'Offer',
                            'Accepted',
                            'Rejected',
                            'Ghosted',
                            'Not Selected',
                            'No Answer',
                            'Withdrawn'
                        ) NOT NULL DEFAULT 'Applied',

    -- Compensation
    salary_requested    VARCHAR(50)                 DEFAULT NULL,
    salary_listed       VARCHAR(50)                 DEFAULT NULL,
    salary_type         ENUM('Yearly','Hourly')     NOT NULL DEFAULT 'Yearly',

    -- Application extras (v2)
    cover_letter        TINYINT                     NOT NULL DEFAULT 0,
    has_outreach        TINYINT                     NOT NULL DEFAULT 0,
    outreach_notes      VARCHAR(500)                DEFAULT NULL,

    -- People & notes
    contacts            VARCHAR(500)                DEFAULT NULL,
    notes               TEXT                        DEFAULT NULL,
    job_description     LONGTEXT                    DEFAULT NULL,

    -- Timeline link
    timeline_id         INT UNSIGNED                DEFAULT NULL,

    created_at          TIMESTAMP                   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP                   NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                    ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_user_id       (user_id),
    INDEX idx_date_applied  (date_applied),
    INDEX idx_status        (status),
    INDEX idx_rating        (rating),
    INDEX idx_timeline_id   (timeline_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 4. timeline_entries
-- Note: company, position, rating, date_applied are NOT stored here.
-- They are always read via JOIN with applications.
-- ============================================================
CREATE TABLE IF NOT EXISTS timeline_entries (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Recruiter contact
    date_recruiter      DATE                        DEFAULT NULL,
    recruiter_name      VARCHAR(255)                DEFAULT NULL,

    -- Screening
    date_screening      DATE                        DEFAULT NULL,
    screener_name       VARCHAR(255)                DEFAULT NULL,
    screening_type      ENUM(
                            'Phone','Async','Home Assessment',
                            'Zoom','MS Teams','Google Meet','On Site'
                        )                           DEFAULT NULL,

    -- Offer (v2)
    offer_date          DATE                        DEFAULT NULL,
    offer_notes         TEXT                        DEFAULT NULL,

    -- Status
    pending             TINYINT                     NOT NULL DEFAULT 1,
    date_closed         DATE                        DEFAULT NULL,  -- renamed from date_rejected in v2; NULL only for Ghosted

    -- Link to application (required — no orphan timeline entries)
    application_id      INT UNSIGNED                NOT NULL,

    created_at          TIMESTAMP                   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP                   NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                    ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_application_id (application_id),
    INDEX idx_pending        (pending)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 5. interview_rounds
-- ============================================================
CREATE TABLE IF NOT EXISTS interview_rounds (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    timeline_id         INT UNSIGNED                NOT NULL,
    round_order         TINYINT UNSIGNED            NOT NULL,

    interview_date      DATE                        DEFAULT NULL,
    interview_type      ENUM(
                            'Phone','Async','Home Assessment',
                            'Zoom','MS Teams','Google Meet','On Site'
                        )                           DEFAULT NULL,
    interviewer         VARCHAR(500)                DEFAULT NULL,
    notes               TEXT                        DEFAULT NULL,
    is_final_round      TINYINT                     NOT NULL DEFAULT 0,

    created_at          TIMESTAMP                   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP                   NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                    ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_timeline_id   (timeline_id),
    INDEX idx_final_round   (is_final_round),
    UNIQUE KEY uq_round     (timeline_id, round_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 6. Foreign keys
-- ============================================================
ALTER TABLE applications
    ADD CONSTRAINT fk_app_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE;

ALTER TABLE applications
    ADD CONSTRAINT fk_app_timeline
        FOREIGN KEY (timeline_id) REFERENCES timeline_entries(id)
        ON DELETE SET NULL;

ALTER TABLE timeline_entries
    ADD CONSTRAINT fk_timeline_app
        FOREIGN KEY (application_id) REFERENCES applications(id)
        ON DELETE CASCADE;

ALTER TABLE interview_rounds
    ADD CONSTRAINT fk_rounds_timeline
        FOREIGN KEY (timeline_id) REFERENCES timeline_entries(id)
        ON DELETE CASCADE;


-- ============================================================
-- After running this script, insert your first admin user:
--
-- INSERT INTO users (username, password_hash, is_admin)
-- VALUES ('your_username', '$2y$12$...bcrypt_hash...', 1);
--
-- Generate hash with: php -r "echo password_hash('your_password', PASSWORD_BCRYPT);"
-- ============================================================