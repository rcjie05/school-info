# Quick Installation Guide

## 📋 Requirements Checklist

- [ ] XAMPP/WAMP/LAMP installed
- [ ] PHP 7.4 or higher
- [ ] MySQL 5.7 or higher
- [ ] Web browser (Chrome, Firefox, Edge, Safari)

## 🚀 5-Minute Setup

### Step 1: Extract Files
Extract the `school-management-system` folder to:
- **XAMPP**: `C:\xampp\htdocs\`
- **WAMP**: `C:\wamp64\www\`
- **Linux**: `/var/www/html/`

### Step 2: Create Database
1. Open **phpMyAdmin**: `http://localhost/phpmyadmin`
2. Click **New** to create a database
3. Name it: `school_management`
4. Click **Create**

### Step 3: Import Database
1. Select the `school_management` database
2. Click **Import** tab
3. Choose file: `database.sql`
4. Click **Go**

### Step 4: Configure Database
1. Open: `php/config.php`
2. Update credentials if needed:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');           // Your MySQL username
define('DB_PASS', '');                // Your MySQL password
define('DB_NAME', 'school_management');
```

### Step 5: Start Servers
1. Start **Apache** and **MySQL** in XAMPP/WAMP
2. Open browser: `http://localhost/school-management-system/login.html`

## 🔐 Login

**Admin Account**
- Email: `admin@school.edu`
- Password: `admin123`

**Test Student Account**
- Register at: `http://localhost/school-management-system/register.html`
- Wait for admin/registrar approval

## ✅ Verify Installation

1. Can you see the login page? ✓
2. Can you login as admin? ✓
3. Do you see the dashboard? ✓

**Success!** Your school management system is ready! 🎉

## 🆘 Troubleshooting

**"Database connection failed"**
- Check if MySQL is running
- Verify credentials in `php/config.php`

**"404 Not Found"**
- Check folder is in correct location
- Verify Apache is running

**"Can't login"**
- Clear browser cache
- Check database was imported correctly
- Try default admin credentials

## 📚 Next Steps

1. Change admin password (recommended!)
2. Add buildings and rooms
3. Add teachers
4. Create departments
5. Configure system settings
6. Post first announcement

## 🎓 User Guide

**For Admins**: Manage users, buildings, and system settings  
**For Registrars**: Approve students, assign loads and schedules  
**For Teachers**: View schedules and classes  
**For Students**: View schedule, grades, and campus info  

---

**Need help?** Check the full `README.md` for detailed documentation!
