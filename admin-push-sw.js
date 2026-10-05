'use strict';

const ADMIN_URL = '/admin/';
const ICON_URL = '/android-icon-192x192.png';

self.addEventListener('install', (event) => {
  event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
  const options = {
    body: 'C\u2019\u00e8 una nuova attivit\u00e0 nel backoffice. Tocca per aprire Lauco Experience.',
    icon: ICON_URL,
    badge: ICON_URL,
    tag: 'lauco-admin-activity',
    renotify: true,
    data: {url: ADMIN_URL}
  };

  event.waitUntil(
    self.registration.showNotification('Lauco Experience', options)
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const target = new URL(event.notification?.data?.url || ADMIN_URL, self.location.origin).href;

  event.waitUntil((async () => {
    const windows = await self.clients.matchAll({type: 'window', includeUncontrolled: true});
    for (const client of windows) {
      if (client.url.startsWith(self.location.origin + '/admin/')) {
        await client.focus();
        if ('navigate' in client && client.url !== target) {
          await client.navigate(target);
        }
        return;
      }
    }
    if (self.clients.openWindow) {
      await self.clients.openWindow(target);
    }
  })());
});
