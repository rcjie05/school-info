-- ============================================================
-- RECRUITMENT & ONBOARDING TABLES
-- Run this in your school_management database
-- ============================================================

-- ── Job Postings ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS hr_job_postings (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(255) NOT NULL,
    department_id   INT NULL,
    employment_type ENUM('full_time','part_time','contractual','probationary') NOT NULL DEFAULT 'full_time',
    slots           INT NOT NULL DEFAULT 1,
    description     TEXT NULL,
    requirements    TEXT NULL,
    status          ENUM('open','closed','cancelled') NOT NULL DEFAULT 'open',
    posted_by       INT NOT NULL,
    deadline        DATE NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (posted_by)     REFERENCES users(id)
);

-- ── Applicants ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS hr_applicants (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    job_id           INT NOT NULL,
    full_name        VARCHAR(255) NOT NULL,
    email            VARCHAR(255) NULL,
    phone            VARCHAR(50)  NULL,
    address          TEXT NULL,
    resume_notes     TEXT NULL,
    stage            ENUM('applied','screening','interview','job_offer','hired','rejected') NOT NULL DEFAULT 'applied',
    interview_date   DATE NULL,
    interview_notes  TEXT NULL,
    offer_date       DATE NULL,
    rejection_reason TEXT NULL,
    onboarded        TINYINT(1) NOT NULL DEFAULT 0,
    onboard_user_id  INT NULL,
    handled_by       INT NOT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id)          REFERENCES hr_job_postings(id) ON DELETE CASCADE,
    FOREIGN KEY (handled_by)      REFERENCES users(id),
    FOREIGN KEY (onboard_user_id) REFERENCES users(id) ON DELETE SET NULL
);
