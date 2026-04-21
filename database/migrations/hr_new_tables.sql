-- ============================================================
-- HR NEW TABLES: Attendance & Payroll
-- Run this SQL in your school_management database
-- ============================================================

-- ── Attendance Table ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS hr_attendance (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    date          DATE NOT NULL,
    time_in       TIME NULL,
    time_out      TIME NULL,
    status        ENUM('present','absent','late','half_day','on_leave') NOT NULL DEFAULT 'present',
    remarks       VARCHAR(255) NULL,
    recorded_by   INT NOT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_attendance (user_id, date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id)
);

-- ── Payroll Table ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS hr_payroll (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    user_id             INT NOT NULL,
    payroll_month       DATE NOT NULL COMMENT 'Store as first day of the month (YYYY-MM-01)',
    basic_salary        DECIMAL(12,2) NOT NULL DEFAULT 0,
    days_worked         DECIMAL(5,2)  NOT NULL DEFAULT 0,
    days_absent         DECIMAL(5,2)  NOT NULL DEFAULT 0,
    overtime_hours      DECIMAL(6,2)  NOT NULL DEFAULT 0,
    overtime_pay        DECIMAL(12,2) NOT NULL DEFAULT 0,
    allowances          DECIMAL(12,2) NOT NULL DEFAULT 0,
    gross_pay           DECIMAL(12,2) NOT NULL DEFAULT 0,
    sss_deduction       DECIMAL(12,2) NOT NULL DEFAULT 0,
    philhealth_deduction DECIMAL(12,2) NOT NULL DEFAULT 0,
    pagibig_deduction   DECIMAL(12,2) NOT NULL DEFAULT 0,
    tax_deduction       DECIMAL(12,2) NOT NULL DEFAULT 0,
    other_deductions    DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_deductions    DECIMAL(12,2) NOT NULL DEFAULT 0,
    net_pay             DECIMAL(12,2) NOT NULL DEFAULT 0,
    status              ENUM('draft','released') NOT NULL DEFAULT 'draft',
    remarks             TEXT NULL,
    processed_by        INT NOT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_payroll (user_id, payroll_month),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id)
);
