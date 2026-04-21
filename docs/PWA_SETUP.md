# 📱 PWA Setup Guide — SCC School Management Portal

## What Was Added

| File | Purpose |
|------|---------|
| `manifest.json` | Makes site installable as an app |
| `sw.js` | Service Worker (caching + push notifications) |
| `js/pwa.js` | Install prompt, notification UI, offline indicator |
| `css/style.css` | Mobile-responsive + bottom nav CSS (appended) |

All HR and Admin pages now include:
- PWA manifest link
- Apple PWA meta tags
- Mobile bottom navigation bar
- `pwa.js` script

---

## 🚀 Deployment Steps

### 1. HTTPS is Required
PWAs only work on HTTPS. Make sure your server has an SSL certificate.
- Free option: [Let's Encrypt](https://letsencrypt.org/)

### 2. Serve sw.js from Root
The `sw.js` file must be at the **root** of your domain:
```
https://yourdomain.com/sw.js  ✅
https://yourdomain.com/js/sw.js  ❌
```

### 3. Push Notifications (Optional)
To enable push notifications:

**Step 1** — Generate VAPID keys:
```bash
npm install web-push -g
web-push generate-vapid-keys
```

**Step 2** — Replace in `js/pwa.js`:
```js
const VAPID_PUBLIC_KEY = 'YOUR_ACTUAL_PUBLIC_KEY_HERE';
```

**Step 3** — Create `php/api/save_push_subscription.php` to store subscriptions in your DB.

**Step 4** — Send push from PHP:
```php
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

$webPush = new WebPush(['VAPID' => [
  'subject' => 'mailto:admin@yourdomain.com',
  'publicKey' => VAPID_PUBLIC_KEY,
  'privateKey' => VAPID_PRIVATE_KEY,
]]);

$webPush->sendOneNotification(
  Subscription::create($subscriptionFromDB),
  json_encode(['title' => 'Leave Request', 'body' => 'New leave request submitted'])
);
```
Install: `composer require minishlink/web-push`

---

## 📲 How Users Install the App

### Android (Chrome)
1. Open the site in Chrome
2. A banner appears at the bottom → tap **Install**
3. Or: tap ⋮ menu → "Add to Home Screen"

### iOS (Safari)
1. Open the site in Safari
2. Tap the **Share** button (box with arrow)
3. Tap **"Add to Home Screen"**

---

## 🔔 Notification Types You Can Send

| Event | Trigger |
|-------|---------|
| Leave Request submitted | When HR submits leave |
| Leave Approved/Rejected | When admin reviews |
| New Announcement | When admin posts |
| Attendance reminder | Scheduled daily |

---

## 📡 Offline Support
- Static assets (CSS, JS, images) cached on first visit
- PHP pages cached after first load
- Offline fallback page shown when no connection
- API calls return graceful error messages when offline
