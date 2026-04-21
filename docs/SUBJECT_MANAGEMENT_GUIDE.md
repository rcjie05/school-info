# Subject Management and Teacher Specialty System

## Overview
This feature allows administrators to manage subjects in the school system and assign teaching specialties to teachers. Teachers can view their assigned specialties and qualification levels.

## Features

### 1. Subject Management (Admin)
Administrators can perform full CRUD operations on subjects:

#### Create/Edit Subject
- **Subject Code**: Unique identifier (e.g., CS101, MATH201)
- **Subject Name**: Full name of the subject
- **Description**: Optional detailed description
- **Units**: Number of credit units (1-6)
- **Course**: Associated course/program
- **Year Level**: Target year level (1st-4th Year)
- **Prerequisites**: Required subjects before taking this one
- **Status**: Active or Inactive

#### View Subjects
- Filter by course, year level, and status
- Search by subject code or name
- View all subject details in a table format

#### Delete Subject
- Remove subjects from the system
- Automatically removes associated teacher specialties (cascading delete)

### 2. Teacher Specialty Management (Admin)

#### Assign Specialty
Administrators can assign subjects to teachers with:
- **Teacher Selection**: Choose from all registered teachers
- **Subject Selection**: Choose from all active subjects
- **Proficiency Level**:
  - Beginner: New to teaching this subject
  - Intermediate: Comfortable teaching this subject
  - Advanced: Highly experienced in this subject
  - Expert: Master level expertise
- **Primary Specialty**: Mark one subject as teacher's main specialty

#### View Specialties
- Filter by teacher or subject
- See proficiency levels and assignment dates
- Identify primary specialties

#### Remove Specialty
- Unassign subjects from teachers when needed

### 3. Teacher View

Teachers can view their assigned specialties showing:
- Subject code and name
- Description and units
- Course and year level
- Proficiency level
- Primary specialty designation
- Assignment date
- Prerequisites

## Database Schema

### New Table: teacher_specialties
```sql
CREATE TABLE teacher_specialties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    subject_id INT NOT NULL,
    proficiency_level ENUM('beginner', 'intermediate', 'advanced', 'expert'),
    is_primary BOOLEAN DEFAULT FALSE,
    assigned_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_teacher_subject (teacher_id, subject_id)
);
```

### Modified Table: subjects
New fields added:
- `description TEXT`: Detailed subject description
- `status ENUM('active', 'inactive')`: Subject availability status

## Installation

1. **Run Database Migration**:
   ```bash
   mysql -u [username] -p school_management < migration_add_subjects_and_specialties.sql
   ```

2. **Copy Files**:
   - Copy `admin/subjects.php` to your admin directory
   - Copy `teacher/specialties.php` to your teacher directory
   - Copy all API files to `php/api/admin/` and `php/api/teacher/`

3. **Update Navigation**:
   Add the following to admin navigation menus:
   ```html
   <a href="subjects.php" class="nav-item">
       <span class="nav-icon">📚</span>
       <span>Subjects</span>
   </a>
   ```

   Add to teacher navigation menus:
   ```html
   <a href="specialties.php" class="nav-item">
       <span class="nav-icon">📚</span>
       <span>My Specialties</span>
   </a>
   ```

## File Structure

```
admin/
  └── subjects.php              # Admin subject management interface

teacher/
  └── specialties.php           # Teacher specialty view

php/api/admin/
  ├── get_subjects.php          # Fetch subjects with filters
  ├── save_subject.php          # Create/update subjects
  ├── delete_subject.php        # Delete subjects
  ├── get_specialties.php       # Fetch teacher specialties
  ├── save_specialty.php        # Assign specialty to teacher
  └── delete_specialty.php      # Remove specialty assignment

php/api/teacher/
  └── get_my_specialties.php    # Teacher's own specialties

migration_add_subjects_and_specialties.sql  # Database migration
```

## Usage Workflow

### For Administrators:

1. **Add Subjects**:
   - Navigate to Admin > Subjects
   - Click "Add Subject"
   - Fill in subject details
   - Save

2. **Assign Teacher Specialties**:
   - Go to "Teacher Specialties" tab
   - Click "Assign Specialty"
   - Select teacher and subject
   - Choose proficiency level
   - Optionally mark as primary specialty
   - Save

3. **Manage Existing Data**:
   - Use filters to find specific subjects or specialties
   - Edit or delete as needed
   - Search functionality available for quick access

### For Teachers:

1. **View Specialties**:
   - Navigate to Teacher > My Specialties
   - View all assigned subjects
   - See proficiency levels and details
   - Primary specialty highlighted

## Key Features

### Data Validation
- Unique subject codes enforced
- Unit limits (1-6)
- Required fields validation
- Duplicate specialty prevention

### Security
- Role-based access control
- Admin-only CRUD operations
- Teachers can only view their own specialties
- Audit logging for all changes

### User Experience
- Responsive design
- Real-time filtering and search
- Clear visual indicators for status and proficiency
- Empty states with helpful messages

## Future Enhancements

Potential additions:
- Allow teachers to request specialty assignments
- Subject availability scheduling
- Integration with class assignment system
- Prerequisite validation when assigning student loads
- Analytics on teacher workload by specialty
- Bulk import/export of subjects

## Troubleshooting

**Issue**: Subjects not appearing
- Check subject status is set to "active"
- Verify database migration ran successfully

**Issue**: Cannot assign specialty
- Ensure teacher role is correctly set
- Verify subject exists and is active
- Check for duplicate assignments

**Issue**: Navigation links not showing
- Verify PHP files are in correct directories
- Check user role permissions in config.php

## Support

For issues or questions:
1. Check audit logs for error details
2. Verify database integrity
3. Review browser console for JavaScript errors
4. Check PHP error logs
