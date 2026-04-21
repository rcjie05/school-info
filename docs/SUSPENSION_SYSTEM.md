# User Suspension System Documentation

## Overview
The enhanced user management system now includes a comprehensive **suspension/deactivation system** with time-based controls. This prevents suspended users from accessing the system and their role-specific functions until they are reactivated.

## Key Features

### 1. **Time-Based Suspension**
- **Temporary Suspension**: Set an expiration date - user is automatically reactivated
- **Permanent Suspension**: Indefinite deactivation until manually reactivated by admin

### 2. **Complete Access Blocking**
- Suspended users **cannot log in**
- Currently logged-in users are **immediately logged out** when suspended
- All role-specific functions are blocked (registrar can't add study loads, teachers can't submit grades, etc.)
- Every page checks user status before allowing access

### 3. **Reason Tracking**
- Admins must provide a reason for every suspension
- Reasons are stored in the database for audit purposes
- Helps track patterns and maintain accountability

### 4. **Automatic Reactivation**
- System automatically reactivates users when temporary suspension expires
- Happens on user's next login attempt or page load
- Logs the auto-reactivation in audit trail

### 5. **Visual Indicators**
- Status badges show "inactive" status
- Suspension info column displays:
  - "Until: [Date/Time]" for temporary suspensions
  - "Permanent" for indefinite suspensions
  - "Expired" for suspensions that have ended

## How It Works

### Suspending a User

1. **Navigate to User Management**
   - Admin Dashboard > User Management

2. **Click "Suspend" Button**
   - Located in the Actions column for active users
   - Opens the Suspension Modal

3. **Fill in Suspension Details**
   - **Suspension Type**: Choose Temporary or Permanent
   - **Suspend Until** (if temporary): Select date and time
   - **Reason**: Required field - explain why user is being suspended

4. **Confirm Suspension**
   - System validates all inputs
   - User status changes to "inactive"
   - User is immediately blocked from system access

### What Happens When a User is Suspended

**Immediate Effects:**
- User status changes to "inactive"
- If logged in, user will be logged out on next page navigation
- Login attempts are blocked with error message
- All API calls are rejected

**User Experience:**
- Login page shows: "Your account has been deactivated. Please contact the administrator."
- If currently using the system: Redirected to login page with error message
- No access to any system features or data

### Reactivating a User

**Manual Reactivation:**
1. Navigate to User Management
2. Find the suspended user
3. Click "Activate" button
4. User can immediately log in and use the system

**Automatic Reactivation (Temporary Suspensions):**
- System checks suspension expiration on every page load
- When expiration time passes, user is automatically reactivated
- Logged in audit trail for tracking

## Database Schema

### New Fields in `users` Table

```sql
deactivated_until DATETIME NULL
```
- Stores the suspension end date/time
- NULL for permanent suspensions or active users
- Used to determine if suspension has expired

```sql
deactivation_reason TEXT NULL
```
- Stores the reason provided by admin
- Required when deactivating a user
- Cleared when user is reactivated

## Technical Implementation

### Page-Level Protection

**Every page that requires authentication uses:**
```php
requireRole('role_name');
```

This function (in `config.php`) now:
1. Checks if user is logged in
2. Checks if user status is inactive
3. Checks if temporary suspension has expired
4. Auto-reactivates if suspension expired
5. Logs out and redirects if still suspended

### Login Protection

**In `login.php`:**
```php
if ($user['status'] === 'inactive') {
    // Check if suspension has expired
    if ($user['deactivated_until']) {
        $deactivated_until = strtotime($user['deactivated_until']);
        if (time() >= $deactivated_until) {
            // Auto-reactivate
        } else {
            // Block login
        }
    } else {
        // Permanent suspension - block login
    }
}
```

### API Protection

All API endpoints use `requireRole()` which includes status checking, ensuring:
- Registrars can't add study loads when suspended
- Teachers can't submit grades when suspended
- Students can't enroll in subjects when suspended
- Admins (except self) follow same rules

## Use Cases

### Example 1: Policy Violation (Temporary)
**Scenario:** Student violated academic integrity policy
- **Action:** 1-week suspension
- **Process:**
  1. Admin suspends student until [date + 7 days]
  2. Reason: "Academic integrity violation - Assignment plagiarism"
  3. Student cannot access system for 1 week
  4. After 7 days, automatically reactivated
  5. Student can resume normal access

### Example 2: Non-Payment (Permanent until resolved)
**Scenario:** Student hasn't paid tuition
- **Action:** Indefinite suspension
- **Process:**
  1. Admin suspends student (no end date)
  2. Reason: "Tuition payment overdue"
  3. Student blocked from system
  4. Once payment received, admin manually activates
  5. Student access restored immediately

### Example 3: Faculty Leave (Temporary)
**Scenario:** Teacher on medical leave for 2 months
- **Action:** Temporary suspension
- **Process:**
  1. Admin suspends teacher until [return date]
  2. Reason: "Medical leave - Scheduled return: [date]"
  3. Teacher cannot access gradebook or class management
  4. Automatically reactivated on return date
  5. Teacher resumes normal duties

### Example 4: Investigation (Temporary to Permanent)
**Scenario:** User under investigation
- **Action:** Initial temporary suspension, then permanent if needed
- **Process:**
  1. Admin suspends for 30 days during investigation
  2. If cleared: User auto-reactivates after 30 days
  3. If guilty: Admin changes to permanent suspension
  4. Account remains locked until HR/admin decision

## Security Considerations

### 1. Session Management
- Suspended users are logged out immediately
- Session is destroyed on status check
- Cannot bypass by keeping browser open

### 2. Direct URL Access
- All pages check authentication before rendering
- Even with valid session, suspended users are blocked
- Applies to main pages and API endpoints

### 3. API Access
- Every API call validates user status
- Suspended users cannot:
  - Submit grades
  - Add study loads
  - Enroll in courses
  - Post announcements
  - Access any protected data

### 4. Concurrent Sessions
- If user is logged in on multiple devices
- Suspension takes effect on next action
- All sessions become invalid

## Admin Best Practices

### When to Use Suspension

**Good Reasons:**
- Policy violations
- Academic integrity issues
- Payment issues
- Extended leave (medical, sabbatical)
- Investigation periods
- Security concerns
- Behavioral issues

**Not Recommended:**
- Account testing (use test accounts)
- Forgotten tasks (use notifications)
- Graduation (change role or status instead)

### Setting Suspension Duration

**Short Term (Hours/Days):**
- Minor policy violations
- Warning periods
- Temporary payment holds

**Medium Term (Weeks/Months):**
- Investigations
- Medical/personal leave
- Probation periods

**Long Term/Permanent:**
- Expulsion
- Termination
- Serious violations
- Unresolved payment issues

### Communication

**Always notify the user:**
1. Email notification of suspension
2. Explanation of reason
3. Duration (if temporary)
4. Contact information for appeals
5. Reactivation process

## Monitoring & Reporting

### Tracking Suspensions

**Audit Logs Include:**
- Who suspended the user
- When suspension occurred
- Reason for suspension
- Duration (if applicable)
- Auto-reactivation events

**View Suspended Users:**
1. User Management page
2. Filter by Status > Inactive
3. Shows suspension info in dedicated column

### Reports

**Useful queries for admins:**
```sql
-- All currently suspended users
SELECT name, email, role, deactivated_until, deactivation_reason 
FROM users 
WHERE status = 'inactive';

-- Suspensions expiring soon (next 7 days)
SELECT name, email, deactivated_until 
FROM users 
WHERE status = 'inactive' 
AND deactivated_until BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY);

-- Permanent suspensions
SELECT name, email, role, deactivation_reason 
FROM users 
WHERE status = 'inactive' 
AND deactivated_until IS NULL;
```

## Troubleshooting

### Issue: User says they're suspended but shouldn't be
1. Check user status in database
2. Check `deactivated_until` field
3. Manually activate if needed
4. Check audit logs for suspension history

### Issue: Temporary suspension not auto-reactivating
1. Verify `deactivated_until` date is in the past
2. Check server timezone settings
3. Have user try logging in (triggers check)
4. Check database datetime format

### Issue: Suspended user still accessing system
1. Verify all pages use `requireRole()`
2. Check if user has multiple accounts
3. Clear server-side sessions
4. Check API endpoints for missing auth checks

### Issue: Admin accidentally suspended themselves
1. Another admin must reactivate
2. Or direct database update:
```sql
UPDATE users 
SET status = 'active', deactivated_until = NULL, deactivation_reason = NULL 
WHERE id = [admin_id];
```

## Migration Instructions

### For Existing Databases

Run this SQL migration:
```sql
ALTER TABLE users 
MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'active', 'inactive') DEFAULT 'pending';

ALTER TABLE users 
ADD COLUMN deactivated_until DATETIME NULL AFTER status,
ADD COLUMN deactivation_reason TEXT NULL AFTER deactivated_until;
```

Or use the provided migration file:
```bash
mysql -u username -p school_management < migration_add_inactive_status.sql
```

### Files to Update

**Required:**
1. `database.sql` - Updated schema
2. `php/config.php` - Enhanced status checking
3. `php/login.php` - Suspension validation
4. `php/api/admin/toggle_user_status.php` - Suspension logic
5. `php/api/admin/get_users.php` - Include suspension fields
6. `admin/users.php` - Suspension UI
7. `css/style.css` - Status styling
8. `login.html` - Error messages

## API Reference

### Suspend User
**Endpoint:** `POST /php/api/admin/toggle_user_status.php`

**Request:**
```json
{
  "user_id": 123,
  "status": "inactive",
  "deactivated_until": "2026-03-15 23:59:59",
  "deactivation_reason": "Policy violation - Multiple plagiarism incidents"
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "User suspended until Mar 15, 2026 11:59 PM"
}
```

### Activate User
**Endpoint:** `POST /php/api/admin/toggle_user_status.php`

**Request:**
```json
{
  "user_id": 123,
  "status": "active"
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "User activated successfully"
}
```

## Future Enhancements

Potential improvements for future versions:
1. Email notifications to suspended users
2. Bulk suspension operations
3. Suspension templates (common reasons/durations)
4. Appeal system for users
5. Suspension history dashboard
6. Automated suspension rules (e.g., auto-suspend after X violations)
7. Grace period before suspension takes effect
8. Partial restrictions (read-only access during suspension)

## Support

For issues or questions:
1. Check this documentation
2. Verify database migration completed
3. Check browser console for errors
4. Review server error logs
5. Verify all files were updated
6. Test with non-admin account first
