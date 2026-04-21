# School Management System

A comprehensive, modern school management system built with HTML, CSS, JavaScript, and PHP. Features a beautiful, responsive design inspired by modern dashboard aesthetics.

![School Management System](https://img.shields.io/badge/Version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange.svg)

## 🎯 Features

### Four User Roles

#### 🎓 Student
- Register and create account
- View enrollment status
- Access study load and class schedule
- View grades (midterm and final)
- Interactive campus map with room finder
- Faculty and department directory
- Receive announcements and notifications
- Submit feedback and inquiries
- AI chatbot assistance

#### 👨‍🏫 Teacher
- View assigned class schedule
- Access office location and hours
- Receive faculty-specific announcements
- Use campus map and search
- View profile information

#### 📋 Registrar
- Review and approve/reject student applications
- Assign study loads to students
- Create and manage class schedules
- Upload and manage grades
- Post announcements
- Respond to student feedback
- Generate enrollment reports
- Audit trail of all actions

#### 👑 Admin
- Full system access
- User management (add/edit/delete)
- Manage campus maps and buildings
- Update faculty directory
- Manage departments
- Configure system settings
- Monitor audit logs
- System-wide announcements

## 🎨 Design Features

- **Modern UI**: Clean, professional interface with custom color palette
- **Responsive Design**: Works seamlessly on desktop, tablet, and mobile
- **Smooth Animations**: Polished transitions and micro-interactions
- **Custom Typography**: Outfit font family for readability
- **Intuitive Navigation**: Clear sidebar navigation with role-based menus
- **Data Visualization**: Clean tables and stat cards
- **Status Indicators**: Color-coded badges for different statuses

## 🚀 Installation

### Prerequisites

- **Web Server**: Apache or Nginx
- **PHP**: Version 7.4 or higher
- **MySQL**: Version 5.7 or higher
- **phpMyAdmin** (optional, for database management)

### Step 1: Setup Database

1. Open phpMyAdmin or your MySQL client
2. Create a new database named `school_management`
3. Import the database schema:
   ```sql
   mysql -u your_username -p school_management < database.sql
   ```
   Or use phpMyAdmin's Import feature with the `database.sql` file

### Step 2: Configure Database Connection

1. Open `php/config.php`
2. Update the database credentials:
   ```php
   define('DB_HOST', 'localhost');      // Your database host
   define('DB_USER', 'your_username');  // Your database username
   define('DB_PASS', 'your_password');  // Your database password
   define('DB_NAME', 'school_management');
   ```

### Step 3: Deploy Files

1. Copy all files to your web server's document root:
   - For XAMPP: `C:/xampp/htdocs/school-management-system/`
   - For WAMP: `C:/wamp64/www/school-management-system/`
   - For Linux: `/var/www/html/school-management-system/`

2. Set proper permissions (Linux/Mac):
   ```bash
   chmod -R 755 /var/www/html/school-management-system/
   chmod -R 777 /var/www/html/school-management-system/uploads/
   ```

### Step 4: Access the System

1. Start your web server (Apache) and MySQL
2. Open your browser and navigate to:
   ```
   http://localhost/school-management-system/login.html
   ```

## 🔐 Default Login Credentials

### Admin Account
- **Email**: admin@school.edu
- **Password**: admin123

**⚠️ Important**: Change the admin password immediately after first login!

## 📁 Project Structure

```
school-management-system/
├── css/
│   └── style.css                 # Main stylesheet
├── js/
│   └── main.js                   # JavaScript utilities
├── php/
│   ├── config.php               # Database configuration
│   ├── login.php                # Login handler
│   ├── register.php             # Registration handler
│   ├── logout.php               # Logout handler
│   └── api/                     # API endpoints
│       ├── student/             # Student APIs
│       ├── teacher/             # Teacher APIs
│       ├── registrar/           # Registrar APIs
│       └── admin/               # Admin APIs
├── student/
│   ├── dashboard.php            # Student dashboard
│   ├── schedule.php             # Class schedule
│   ├── subjects.php             # Study load
│   └── grades.php               # Grades view
├── teacher/
│   └── dashboard.php            # Teacher dashboard
├── registrar/
│   ├── dashboard.php            # Registrar dashboard
│   ├── applications.php         # Review applications
│   ├── manage_loads.php         # Assign study loads
│   └── manage_schedules.php     # Create schedules
├── admin/
│   └── dashboard.php            # Admin dashboard
├── uploads/                     # File uploads directory
├── login.html                   # Login page
├── register.html                # Registration page
├── database.sql                 # Database schema
└── README.md                    # This file
```

## 💡 Usage Guide

### For Students

1. **Registration**
   - Navigate to registration page
   - Fill in personal and academic information
   - Create account with email and password
   - Wait for registrar approval

2. **After Approval**
   - Login with your credentials
   - View your dashboard
   - Access study load once assigned by registrar
   - Check class schedule
   - View grades when available

### For Registrars

1. **Review Applications**
   - Login to registrar dashboard
   - View pending applications
   - Review student information
   - Approve or reject applications

2. **Assign Study Loads**
   - Select approved student
   - Add subjects based on course and year level
   - Save and finalize study load

3. **Create Schedules**
   - For each enrolled subject
   - Assign section, time, room, and teacher
   - Check for conflicts
   - Publish schedule

### For Admins

1. **User Management**
   - Add new users (students, teachers, registrars)
   - Edit user information
   - Deactivate accounts

2. **System Configuration**
   - Update campus maps
   - Manage buildings and rooms
   - Update faculty directory
   - Configure system settings

## 🛠️ Development

### Adding New Features

1. **Create Database Tables**: Add to `database.sql`
2. **Create API Endpoint**: Add to `php/api/[role]/`
3. **Create Frontend Page**: Add to appropriate role folder
4. **Update Navigation**: Add menu item in dashboard

### Code Style

- **PHP**: Follow PSR-12 coding standards
- **JavaScript**: Use ES6+ features
- **CSS**: Use CSS custom properties for theming
- **HTML**: Semantic HTML5

### Security Best Practices

- ✅ Password hashing with bcrypt
- ✅ Prepared statements for SQL queries
- ✅ Input sanitization
- ✅ Session management
- ✅ Role-based access control
- ✅ CSRF protection recommended

## 🎨 Customization

### Change Color Scheme

Edit CSS variables in `css/style.css`:

```css
:root {
    --primary-purple: #5B4E9B;      /* Primary color */
    --secondary-pink: #E89BA7;       /* Secondary color */
    --background-main: #F5F5FA;      /* Background */
    /* ... more variables */
}
```

### Change School Name

Update in multiple places:
1. Database: `system_settings` table
2. Login page: `login.html`
3. All dashboard headers

## 📊 Database Schema

### Main Tables

- **users**: All user accounts (students, teachers, registrars, admins)
- **subjects**: Available subjects/courses
- **study_loads**: Student enrollments
- **schedules**: Class schedules
- **grades**: Student grades
- **buildings**: Campus buildings
- **rooms**: Classrooms and facilities
- **departments**: Academic departments
- **announcements**: System announcements
- **notifications**: User notifications
- **feedback**: Student feedback/inquiries
- **audit_logs**: System activity logs

## 🔧 Troubleshooting

### Common Issues

**Database Connection Failed**
- Check database credentials in `php/config.php`
- Ensure MySQL server is running
- Verify database exists

**Login Not Working**
- Clear browser cache and cookies
- Check if session support is enabled in PHP
- Verify user exists in database

**404 Errors**
- Check file paths and server configuration
- Ensure mod_rewrite is enabled (Apache)

**Permission Denied**
- Set proper file permissions
- Check uploads directory is writable

## 📝 License

This project is created for educational purposes. Feel free to modify and use for your institution.

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create feature branch
3. Commit your changes
4. Push to the branch
5. Open a Pull Request

## 📧 Support

For support and questions:
- Create an issue in the repository
- Email: support@school.edu

## 🎓 Credits

Designed and developed for Other Level's Smart Schools
- Modern UI/UX design
- Full-stack PHP/MySQL implementation
- Responsive and accessible

---

**Version**: 1.0.0  
**Last Updated**: February 2025

Made with ❤️ for better education management
