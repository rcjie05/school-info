# School Management System

A PHP-based school management system with multi-role access (Admin, Teacher, Student, Registrar, HR).

## Project Structure

```
school-mgmt/
├── README.md
├── docs/                        # All documentation & guides
│   ├── INSTALL.md
│   ├── PROJECT_OVERVIEW.md
│   ├── PWA_SETUP.md
│   ├── INTEGRATION_GUIDE.md
│   ├── RAILWAY_DEPLOY.md
│   ├── HR_NEW_FEATURES_README.md
│   ├── SUBJECT_MANAGEMENT_GUIDE.md
│   ├── SUSPENSION_SYSTEM.md
│   └── USER_MANAGEMENT_ENHANCEMENTS.md
│
├── database/                    # SQL files
│   ├── school_management.sql    # Main schema + seed data
│   ├── school_management_v2.sql # Updated schema
│   └── migrations/              # Incremental migration scripts
│
├── public/                      # Web root (serve this directory)
│   ├── index.html
│   ├── login.html
│   ├── register.html
│   ├── enrollment.html
│   ├── floorplan.html
│   ├── manifest.json
│   ├── css/                     # Stylesheets
│   ├── js/                      # Client-side scripts + PWA sw.js
│   └── images/                  # Static images & logos
│
├── src/
│   ├── php/                     # Core PHP (config, routing, auth, mailer)
│   │   ├── config.php
│   │   ├── routes.php
│   │   ├── login.php / logout.php / register.php
│   │   ├── session.php
│   │   ├── smtp_mailer.php
│   │   └── ...
│   ├── views/                   # Role-based UI pages
│   │   ├── admin/
│   │   ├── teacher/
│   │   ├── student/
│   │   ├── registrar/
│   │   └── hr/
│   └── api/                     # REST API endpoints
│       ├── admin/
│       ├── teacher/
│       ├── student/
│       ├── registrar/
│       ├── hr/
│       └── shared/              # Shared utilities (upload_avatar, test)
│
├── config/                      # App configuration files
│   ├── .htaccess
│   ├── composer.json
│   └── nixpacks.toml
│
├── docker/                      # Docker / deployment config
│   ├── Dockerfile
│   ├── apache.conf
│   ├── php.ini
│   └── start.sh
│
└── uploads/                     # Runtime user uploads (gitignore this)
    ├── avatars/
    ├── announcements/
    ├── feedback/
    ├── grade_sheets/
    └── rooms/
```

## Quick Start

See [`docs/INSTALL.md`](docs/INSTALL.md) for setup instructions.  
For Docker/Railway deployment, see [`docs/RAILWAY_DEPLOY.md`](docs/RAILWAY_DEPLOY.md).
