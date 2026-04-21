# Quick Integration Guide

## Step-by-Step Integration

### 1. Database Setup (5 minutes)

Run the migration file to add the new table and modify the subjects table:

```bash
mysql -u your_username -p school_management < migration_add_subjects_and_specialties.sql
```

Or via phpMyAdmin:
1. Open phpMyAdmin
2. Select `school_management` database
3. Go to SQL tab
4. Copy and paste contents of `migration_add_subjects_and_specialties.sql`
5. Click "Go"

### 2. Update Admin Navigation (2 minutes)

Edit ALL admin pages to add the Subjects link. Files to update:
- `admin/dashboard.php`
- `admin/users.php`
- `admin/buildings.php`
- `admin/departments.php`
- `admin/audit_logs.php`
- `admin/settings.php`
- `admin/account_settings.php`

Add this line after the Departments menu item:

```html
<a href="subjects.php" class="nav-item"><span class="nav-icon">📚</span><span>Subjects</span></a>
```

Example location in departments.php (around line 31):
```html
<a href="departments.php" class="nav-item active"><span class="nav-icon">🏛️</span><span>Departments</span></a>
<a href="subjects.php" class="nav-item"><span class="nav-icon">📚</span><span>Subjects</span></a>
```

### 3. Update Teacher Navigation (2 minutes)

Edit ALL teacher pages to add the Specialties link. Files to update:
- `teacher/dashboard.php`
- `teacher/classes.php`
- `teacher/schedule.php`

Add this line after the Schedule menu item:

```html
<a href="specialties.php" class="nav-item"><span class="nav-icon">📚</span><span>My Specialties</span></a>
```

Example:
```html
<a href="schedule.php" class="nav-item"><span class="nav-icon">📅</span><span>Schedule</span></a>
<a href="specialties.php" class="nav-item"><span class="nav-icon">📚</span><span>My Specialties</span></a>
```

### 4. Verify File Placement

Make sure these files are in the correct locations:

```
school-management-system/
├── admin/
│   └── subjects.php                           ✓ NEW
├── teacher/
│   └── specialties.php                        ✓ NEW
├── php/
│   └── api/
│       ├── admin/
│       │   ├── get_subjects.php               ✓ NEW
│       │   ├── save_subject.php               ✓ NEW
│       │   ├── delete_subject.php             ✓ NEW
│       │   ├── get_specialties.php            ✓ NEW
│       │   ├── save_specialty.php             ✓ NEW
│       │   └── delete_specialty.php           ✓ NEW
│       └── teacher/
│           └── get_my_specialties.php         ✓ NEW
├── migration_add_subjects_and_specialties.sql ✓ NEW
└── SUBJECT_MANAGEMENT_GUIDE.md                ✓ NEW
```

### 5. Test the Feature (10 minutes)

1. **Login as Admin**
   - Go to Admin > Subjects
   - Click "Add Subject"
   - Create a test subject (e.g., TEST101 - Test Subject)
   - Verify it appears in the list

2. **Assign Specialty**
   - Click "Teacher Specialties" tab
   - Click "Assign Specialty"
   - Select a teacher and the test subject
   - Choose proficiency level
   - Save and verify it appears

3. **Login as Teacher**
   - Navigate to Teacher > My Specialties
   - Verify you can see your assigned subjects
   - Check that details display correctly

4. **Test Filters**
   - As admin, try filtering subjects by course, year, status
   - Try searching by subject code
   - Filter specialties by teacher

5. **Test Delete**
   - Delete a test subject
   - Verify cascade deletion of specialties

### 6. Production Checklist

Before deploying to production:

- [ ] Database migration completed successfully
- [ ] All navigation links updated
- [ ] Admin can create/edit/delete subjects
- [ ] Admin can assign/remove teacher specialties
- [ ] Teachers can view their specialties
- [ ] Filters and search work correctly
- [ ] No console errors in browser
- [ ] No PHP errors in logs
- [ ] Audit logs recording changes
- [ ] Tested on actual production data (if available)

## Common Issues and Solutions

### Issue: "Table doesn't exist" error
**Solution**: Run the migration file. Check that your database name is correct.

### Issue: Navigation link shows but page is blank
**Solution**: 
1. Check file permissions (should be 644)
2. Verify file is in correct directory
3. Check PHP error logs

### Issue: API returns 403 Forbidden
**Solution**: Check that requireRole() in config.php is working correctly

### Issue: Changes not saving
**Solution**: 
1. Check browser console for JavaScript errors
2. Verify API endpoint paths are correct
3. Check database user has write permissions

## Rollback Plan

If you need to remove this feature:

1. **Remove files**:
   ```bash
   rm admin/subjects.php
   rm teacher/specialties.php
   rm php/api/admin/get_subjects.php
   rm php/api/admin/save_subject.php
   rm php/api/admin/delete_subject.php
   rm php/api/admin/get_specialties.php
   rm php/api/admin/save_specialty.php
   rm php/api/admin/delete_specialty.php
   rm php/api/teacher/get_my_specialties.php
   ```

2. **Remove navigation links** from all updated files

3. **Rollback database** (optional - only if needed):
   ```sql
   DROP TABLE teacher_specialties;
   ALTER TABLE subjects DROP COLUMN description;
   ALTER TABLE subjects DROP COLUMN status;
   ```

## Next Steps

After successful integration:

1. Add subjects for all courses in your curriculum
2. Assign specialties to all teachers
3. Consider enhancing the registrar load management to use teacher specialties
4. Add specialty information to teacher profiles
5. Generate reports on teacher coverage by subject
