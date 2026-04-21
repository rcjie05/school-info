# 🚂 Railway Deployment Guide — SCC School Management Portal

## Files Added for Railway

| File | Purpose |
|------|---------|
| `Dockerfile` | Builds PHP 8.2 + Apache container |
| `docker/apache.conf` | Apache virtual host with PWA headers |
| `docker/php.ini` | PHP production settings |
| `nixpacks.toml` | Alternative auto-deploy config (Railway) |
| `.htaccess` | HTTPS redirect, caching, security headers |

---

## 🚀 Step-by-Step Railway Deployment

### Step 1 — Push to GitHub

1. Create a new repo at [github.com](https://github.com)
2. Open terminal/command prompt in your project folder:

```bash
git init
git add .
git commit -m "Initial commit - SCC School Management PWA"
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO.git
git push -u origin main
```

---

### Step 2 — Deploy on Railway

1. Go to [railway.app](https://railway.app) and sign in with GitHub
2. Click **"New Project"**
3. Select **"Deploy from GitHub repo"**
4. Choose your repository
5. Railway will auto-detect the `Dockerfile` and start building ✅

---

### Step 3 — Add MySQL Database

1. In your Railway project dashboard, click **"+ New"**
2. Select **"Database"** → **"Add MySQL"**
3. Railway automatically injects these environment variables into your app:
   - `MYSQLHOST`
   - `MYSQLPORT`
   - `MYSQLUSER`
   - `MYSQLPASSWORD`
   - `MYSQLDATABASE`

Your `php/config.php` already reads these — no extra setup needed! ✅

---

### Step 4 — Import Your Database

After MySQL is added:

1. In Railway, click your **MySQL** service
2. Go to the **"Query"** tab (or connect via MySQL client)
3. Import your SQL file:

**Option A — Railway Query tab:**
- Copy the contents of `school_management.sql`
- Paste and run in the Query tab

**Option B — TablePlus / DBeaver (recommended):**
- Click MySQL service → **"Connect"** tab
- Copy the connection details
- Open TablePlus/DBeaver, connect, then import the `.sql` file

---

### Step 5 — Get Your Public URL

1. Click your **web service** in Railway
2. Go to **"Settings"** → **"Networking"**
3. Click **"Generate Domain"**
4. You'll get a free URL like: `https://your-app.up.railway.app` 🎉

---

### Step 6 — Test Your PWA

Open your Railway URL on your phone:
- **Android**: Chrome will show "Add to Home Screen" banner
- **iOS**: Safari → Share → "Add to Home Screen"

---

## 🔧 Environment Variables (Optional)

Set these in Railway → your service → **"Variables"** tab:

| Variable | Value | Purpose |
|----------|-------|---------|
| `SMTP_USERNAME` | your@gmail.com | Email sender |
| `SMTP_APP_PASSWORD` | your-app-password | Gmail app password |
| `VAPID_PUBLIC_KEY` | (from web-push) | Push notifications |
| `VAPID_PRIVATE_KEY` | (from web-push) | Push notifications |

---

## ⚠️ Notes

- **Dockerfile vs nixpacks.toml**: Railway will use the `Dockerfile` first. If you want Railway to auto-detect (nixpacks), delete the `Dockerfile`.
- **File uploads** (avatars, etc.) are stored in `/uploads/` — on Railway these reset on redeploy. For permanent storage, use **Railway Volumes** or **Cloudinary**.
- **SQL file**: Use `school_management.sql` (not the `(2)` copy) for import.

---

## 🆘 Troubleshooting

**"Build failed"**
→ Check Railway build logs. Usually a missing PHP extension — the Dockerfile covers all needed ones.

**"Database connection failed"**
→ Make sure MySQL service is added and variables are injected. Check Railway → Variables tab.

**"500 Internal Server Error"**
→ Check Railway → Logs tab for PHP errors.

**PWA install prompt not showing**
→ Make sure you're on the HTTPS Railway URL, not localhost.
