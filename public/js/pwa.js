/**
 * SCC School Management — PWA Helper
 * Handles: SW registration, install prompt, push notification subscription
 * Include this script in every page (before closing </body>)
 */

(function () {
  'use strict';

  // ─── 1. Register Service Worker ─────────────────────────────────────────
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/sw.js', { scope: '/' })
        .then(reg => {
          console.log('[PWA] Service Worker registered:', reg.scope);
          // Check for updates every 60 seconds
          setInterval(() => reg.update(), 60000);
        })
        .catch(err => console.error('[PWA] SW registration failed:', err));
    });
  }

  // ─── 2. Install Prompt (Add to Home Screen) ──────────────────────────────
  let deferredPrompt = null;

  window.addEventListener('beforeinstallprompt', e => {
    e.preventDefault();
    deferredPrompt = e;
    showInstallBanner();
  });

  window.addEventListener('appinstalled', () => {
    console.log('[PWA] App installed!');
    hideInstallBanner();
    deferredPrompt = null;
    // Show a thank you toast
    showToastPWA('✅ SCC Portal installed on your device!', 'success');
  });

  function showInstallBanner() {
    // Don't show if already installed (standalone mode)
    if (window.matchMedia('(display-mode: standalone)').matches) return;
    if (sessionStorage.getItem('pwa-banner-dismissed')) return;

    const banner = document.createElement('div');
    banner.id = 'pwa-install-banner';
    banner.innerHTML = `
      <div style="
        position: fixed; bottom: 1rem; left: 50%; transform: translateX(-50%);
        background: #1E3352; color: white; border-radius: 14px;
        padding: 1rem 1.25rem; display: flex; align-items: center; gap: 1rem;
        box-shadow: 0 8px 32px rgba(0,0,0,0.3); z-index: 99999;
        max-width: 360px; width: calc(100vw - 2rem);
        animation: slideUp 0.4s ease;
        font-family: -apple-system, BlinkMacSystemFont, 'Outfit', sans-serif;
      ">
        <img src="/images/logo.png" alt="SCC" style="width:42px;height:42px;border-radius:10px;object-fit:cover;flex-shrink:0;">
        <div style="flex:1;min-width:0;">
          <div style="font-weight:700;font-size:0.9rem;">Install SCC Portal</div>
          <div style="font-size:0.78rem;opacity:0.75;margin-top:0.1rem;">Add to your home screen for quick access</div>
        </div>
        <div style="display:flex;flex-direction:column;gap:0.4rem;flex-shrink:0;">
          <button id="pwa-install-btn" style="
            background:#3D6B9F;color:white;border:none;padding:0.45rem 0.9rem;
            border-radius:8px;font-size:0.8rem;font-weight:700;cursor:pointer;white-space:nowrap;
          ">Install</button>
          <button id="pwa-dismiss-btn" style="
            background:transparent;color:rgba(255,255,255,0.6);border:none;
            font-size:0.75rem;cursor:pointer;text-align:center;padding:0.1rem;
          ">Not now</button>
        </div>
      </div>
      <style>
        @keyframes slideUp { from { opacity:0; transform: translateX(-50%) translateY(20px); } to { opacity:1; transform: translateX(-50%) translateY(0); } }
      </style>
    `;

    document.body.appendChild(banner);

    document.getElementById('pwa-install-btn').addEventListener('click', async () => {
      if (!deferredPrompt) return;
      deferredPrompt.prompt();
      const { outcome } = await deferredPrompt.userChoice;
      console.log('[PWA] Install prompt outcome:', outcome);
      deferredPrompt = null;
      hideInstallBanner();
    });

    document.getElementById('pwa-dismiss-btn').addEventListener('click', () => {
      sessionStorage.setItem('pwa-banner-dismissed', '1');
      hideInstallBanner();
    });
  }

  function hideInstallBanner() {
    const banner = document.getElementById('pwa-install-banner');
    if (banner) banner.remove();
  }

  // ─── 3. Push Notification Subscription ──────────────────────────────────
  // VAPID public key — replace with your own from web-push library
  const VAPID_PUBLIC_KEY = 'YOUR_VAPID_PUBLIC_KEY_HERE';

  window.PWA = {
    /**
     * Request push notification permission and subscribe.
     * Call this on user action (e.g. a "Enable Notifications" button).
     */
    async enablePushNotifications() {
      if (!('PushManager' in window)) {
        showToastPWA('Push notifications not supported in this browser.', 'error');
        return false;
      }

      const permission = await Notification.requestPermission();
      if (permission !== 'granted') {
        showToastPWA('Notification permission denied.', 'error');
        return false;
      }

      try {
        const reg = await navigator.serviceWorker.ready;
        const subscription = await reg.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY),
        });

        // Send subscription to your backend
        await fetch('/php/api/save_push_subscription.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(subscription),
        });

        showToastPWA('🔔 Push notifications enabled!', 'success');
        console.log('[PWA] Push subscription:', JSON.stringify(subscription));
        return true;
      } catch (err) {
        console.error('[PWA] Push subscription failed:', err);
        showToastPWA('Failed to enable notifications. Try again.', 'error');
        return false;
      }
    },

    /**
     * Show a local (non-push) notification — useful for in-app alerts.
     */
    async showLocalNotification(title, body, url = '/') {
      if (Notification.permission !== 'granted') return;
      const reg = await navigator.serviceWorker.ready;
      reg.showNotification(title, {
        body,
        icon: '/images/logo.png',
        badge: '/images/logo.png',
        data: { url },
        vibrate: [200, 100, 200],
      });
    },

    /** Check if app is running in standalone (installed) mode */
    isInstalled() {
      return window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;
    },
  };

  // ─── 4. Auto-prompt notifications after login ────────────────────────────
  // If the user is on an authenticated page and hasn't been asked yet
  window.addEventListener('DOMContentLoaded', () => {
    const isAuthPage = window.location.pathname.includes('/hr/') || window.location.pathname.includes('/admin/');
    const alreadyAsked = localStorage.getItem('pwa-notif-asked');

    if (isAuthPage && !alreadyAsked && 'Notification' in window && Notification.permission === 'default') {
      setTimeout(() => {
        showNotifPrompt();
      }, 5000); // Ask after 5 seconds on first authenticated page load
    }
  });

  function showNotifPrompt() {
    if (localStorage.getItem('pwa-notif-asked')) return;
    localStorage.setItem('pwa-notif-asked', '1');

    const prompt = document.createElement('div');
    prompt.id = 'pwa-notif-prompt';
    prompt.innerHTML = `
      <div style="
        position: fixed; top: 1rem; right: 1rem;
        background: white; border-radius: 14px; padding: 1rem 1.25rem;
        box-shadow: 0 8px 32px rgba(30,51,82,0.18); z-index: 99999;
        max-width: 300px; border-left: 4px solid #3D6B9F;
        animation: slideIn 0.4s ease;
        font-family: -apple-system, BlinkMacSystemFont, 'Outfit', sans-serif;
      ">
        <div style="font-weight:700;color:#1C2C42;margin-bottom:0.25rem;">🔔 Enable Notifications</div>
        <div style="font-size:0.82rem;color:#5A6B80;margin-bottom:0.85rem;line-height:1.5;">
          Stay updated on leave requests, announcements, and more.
        </div>
        <div style="display:flex;gap:0.5rem;">
          <button id="notif-allow-btn" style="
            flex:1;background:#1E3352;color:white;border:none;padding:0.5rem;
            border-radius:8px;font-size:0.82rem;font-weight:700;cursor:pointer;
          ">Allow</button>
          <button id="notif-skip-btn" style="
            flex:1;background:#EEF2F7;color:#5A6B80;border:none;padding:0.5rem;
            border-radius:8px;font-size:0.82rem;font-weight:600;cursor:pointer;
          ">Skip</button>
        </div>
      </div>
      <style>
        @keyframes slideIn { from { opacity:0; transform:translateX(20px); } to { opacity:1; transform:translateX(0); } }
      </style>
    `;

    document.body.appendChild(prompt);

    document.getElementById('notif-allow-btn').addEventListener('click', () => {
      prompt.remove();
      window.PWA.enablePushNotifications();
    });
    document.getElementById('notif-skip-btn').addEventListener('click', () => {
      prompt.remove();
    });
  }

  // ─── 5. Online/Offline indicator ─────────────────────────────────────────
  function updateOnlineStatus() {
    const existing = document.getElementById('pwa-offline-bar');
    if (!navigator.onLine) {
      if (!existing) {
        const bar = document.createElement('div');
        bar.id = 'pwa-offline-bar';
        bar.innerHTML = '📡 You are offline — some features may not work';
        bar.style.cssText = `
          position: fixed; top: 0; left: 0; right: 0;
          background: #ef4444; color: white; text-align: center;
          padding: 0.5rem; font-size: 0.82rem; font-weight: 600;
          z-index: 999999; font-family: -apple-system, sans-serif;
        `;
        document.body.prepend(bar);
      }
    } else {
      if (existing) existing.remove();
    }
  }

  window.addEventListener('online', updateOnlineStatus);
  window.addEventListener('offline', updateOnlineStatus);
  window.addEventListener('DOMContentLoaded', updateOnlineStatus);

  // ─── Helpers ─────────────────────────────────────────────────────────────
  function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = atob(base64);
    return Uint8Array.from([...rawData].map(c => c.charCodeAt(0)));
  }

  function showToastPWA(message, type = 'info') {
    // Use existing showToast if available, otherwise create our own
    if (typeof showToast === 'function') {
      showToast(message, type);
      return;
    }
    const toast = document.createElement('div');
    toast.textContent = message;
    toast.style.cssText = `
      position:fixed;bottom:5rem;left:50%;transform:translateX(-50%);
      background:${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#1E3352'};
      color:white;padding:0.75rem 1.5rem;border-radius:10px;font-size:0.875rem;
      font-weight:600;z-index:999999;white-space:nowrap;
      box-shadow:0 4px 16px rgba(0,0,0,0.2);
      font-family:-apple-system,BlinkMacSystemFont,'Outfit',sans-serif;
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
  }

})();
