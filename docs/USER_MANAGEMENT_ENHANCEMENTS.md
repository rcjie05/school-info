# User Management Enhancements

## New Features Added

### 1. User Deactivation/Activation

**Purpose:** Allow administrators to temporarily disable user accounts without deleting them.

**Features:**
- **Deactivate Button:** Appears for users with "active" status
- **Activate Button:** Appears for users with "inactive" status
- **Status Badge:** Visual indicator showing inactive status with gray color
- **Confirmation Dialog:** Prompts admin to confirm before changing status

**Usage:**
1. Navigate to Admin > User Management
2. Find the user you want to deactivate/activate
3. Click the "Deactivate" or "Activate" button in the Actions column
4. Confirm the action in the dialog box
5. The user's status will be updated immediately

**Status Options:**
- **Active:** User can log in and use the system normally
- **Inactive:** User account is disabled, cannot log in
- **Pending:** New account waiting for approval
- **Approved:** Account has been approved
- **Rejected:** Account application was rejected

### 2. Department Selection for Teachers

**Purpose:** Streamline teacher creation by providing a dropdown list of available departments.

**Features:**
- **Dynamic Department Loading:** Automatically fetches departments from the database
- **Dropdown Selection:** Easy-to-use select menu instead of manual text input
- **Real-time Updates:** Department list refreshes from the database
- **Validation:** Ensures valid department selection

**Usage:**
1. Navigate to Admin > User Management
2. Click "Add New User" or edit an existing teacher
3. Select "Teacher" or "Registrar" from the Role dropdown
4. The Department field will appear as a dropdown menu
5. Select the appropriate department from the list
6. Complete the rest of the form and save

**For Registrars:**
The department field also appears for registrars, allowing proper department assignment.

## Technical Details

### Database Changes

**Users Table - Status Enum Update:**
```sql
ALTER TABLE users 
MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'active', 'inactive') DEFAULT 'pending';
```

### New API Endpoint

**File:** `/php/api/admin/toggle_user_status.php`

**Method:** POST

**Request Body:**
```json
{
    "user_id": 123,
    "status": "inactive"
}
```

**Response:**
```json
{
    "success": true,
    "message": "User deactivated successfully"
}
```

**Allowed Status Values:**
- active
- inactive
- pending
- approved
- rejected

### Modified Files

1. **`admin/users.php`**
   - Added "Inactive" status filter option
   - Changed department input to select dropdown
   - Added deactivate/activate buttons
   - Added function to load departments
   - Added function to toggle user status
   - Updated status badge display

2. **`css/style.css`**
   - Added `.status-inactive` style (gray color)
   - Added `.status-active` style (green color)

3. **`database.sql`**
   - Updated users table status enum to include 'inactive'

4. **New: `php/api/admin/toggle_user_status.php`**
   - API endpoint for activating/deactivating users

5. **New: `migration_add_inactive_status.sql`**
   - Migration script for existing databases

## Installation Instructions

### For New Installations:
1. Run the updated `database.sql` file - it includes the inactive status

### For Existing Installations:
1. Run the `migration_add_inactive_status.sql` file to update your database:
   ```bash
   mysql -u your_username -p school_management < migration_add_inactive_status.sql
   ```

2. Replace the following files with the updated versions:
   - `admin/users.php`
   - `css/style.css`
   - Add new file: `php/api/admin/toggle_user_status.php`

3. Clear browser cache and refresh the page

## User Interface

### Status Badge Colors:
- **Active:** Green background (#67C4A7)
- **Inactive:** Gray background (#A8A4B8)
- **Pending:** Yellow background (#F4C96B)
- **Approved:** Green background (same as active)
- **Rejected:** Red/Pink background (#E89BA7)

### Button Colors:
- **Deactivate Button:** Yellow/Orange (warning color)
- **Activate Button:** Green (success color)
- **Edit Button:** Default blue
- **Delete Button:** Red (danger color)

## Security Considerations

1. **Role-Based Access:** Only administrators can activate/deactivate users
2. **Confirmation Dialogs:** Prevents accidental status changes
3. **Audit Trail:** Consider logging status changes in audit_logs table
4. **Session Management:** Inactive users should be logged out immediately

## Best Practices

1. **Use Deactivation Instead of Deletion:** 
   - Preserve data integrity
   - Maintain historical records
   - Can reactivate accounts if needed

2. **Department Management:**
   - Keep department list up to date
   - Use consistent department names
   - Coordinate with department creation in Departments module

3. **Status Management:**
   - Document reason for deactivation
   - Review inactive accounts periodically
   - Clean up old rejected/pending accounts

## Troubleshooting

### Department Dropdown is Empty:
1. Ensure departments exist in the database
2. Check that the `get_departments.php` API is working
3. Verify user has admin permissions

### Status Not Updating:
1. Check browser console for JavaScript errors
2. Verify `toggle_user_status.php` API endpoint exists
3. Ensure database connection is working
4. Check that user_id is valid

### Migration Errors:
If you get "Column doesn't exist" or similar errors:
1. Check your current database schema
2. Verify you're connected to the correct database
3. Try running the ALTER TABLE command manually

## Future Enhancements

Potential improvements for future versions:
1. Bulk activate/deactivate functionality
2. Scheduled deactivation (e.g., end of semester)
3. Deactivation reason tracking
4. Email notifications for status changes
5. Activity history for inactive accounts
6. Advanced filtering (inactive for X days, etc.)

## Support

For issues or questions:
1. Check this documentation first
2. Verify all files are updated correctly
3. Check browser console and server logs
4. Ensure database migration was successful
