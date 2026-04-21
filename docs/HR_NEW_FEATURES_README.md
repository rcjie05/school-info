# HR New Features — Setup Guide
## Features Added
1. **Attendance Tracking** (`hr/attendance.php`) — Daily log and monthly summary for faculty/staff
2. **Payroll** (`hr/payroll.php`) — Generate and manage monthly payslips per employee

---

## Step 1 — Run the SQL
Open phpMyAdmin (or MySQL console) and run:
```
school-mgmt-fixed/sql/hr_new_tables.sql
```
This creates two new tables: `hr_attendance` and `hr_payroll`.

---

## Step 2 — Copy the Files
Copy files into your existing project, maintaining the folder structure:

```
school-mgmt-fixed/
├── hr/
│   ├── attendance.php          ← NEW
│   └── payroll.php             ← NEW
└── php/
    └── api/
        └── hr/
            ├── get_attendance.php      ← NEW
            ├── save_attendance.php     ← NEW
            ├── export_attendance.php   ← NEW
            ├── get_payroll.php         ← NEW
            └── save_payroll.php        ← NEW
```

---

## Step 3 — Update Existing Sidebar (Optional)
To add the new links to your existing HR pages (dashboard, employees, leaves, announcements, floorplan, profile),
find the HR Management nav section in each file and add these two lines:

```html
<a href="attendance.php" class="nav-item"><span class="nav-icon">🕐</span><span>Attendance</span></a>
<a href="payroll.php" class="nav-item"><span class="nav-icon">💰</span><span>Payroll</span></a>
```

Place them after the `leaves.php` nav item.

---

## Features Overview

### Attendance Tracking
- **Daily Log** — Select a date, set each employee's status (Present / Absent / Late / Half Day / On Leave), log time in/out and remarks, then save all at once
- **Monthly Summary** — Switch to see a per-employee attendance summary with attendance rate and progress bar
- **Export CSV** — Download attendance data for any day or month
- Filter by role (Teacher / Registrar / Admin)

### Payroll
- **Payslip Generation** — Select a payroll month, click an employee, fill in earnings and deductions, auto-computes gross pay, total deductions, and net pay
- **Draft / Released** — Save payslips as draft while working, mark as Released when ready to distribute
- **Print Payslip** — Built-in print button for each payslip
- Filter by role and status (Draft / Released / No Payslip)
- Auto-fills basic salary from the employee's HR profile if set
