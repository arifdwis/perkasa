import { precacheAndRoute } from 'workbox-precaching'
import { clientsClaim } from 'workbox-core'

clientsClaim()
precacheAndRoute(self.__WB_MANIFEST)

self.addEventListener('push', (event) => {
  if (!event.data) return

  try {
    const payload = event.data.json()
    const { title, body, icon, badge, data } = payload

    const options = {
      body: body || '',
      icon: icon || '/logo_unmul.png',
      badge: badge || '/logo_unmul.png',
      vibrate: [200, 100, 200],
      data: data || {},
      tag: 'perkasa-notif',
      renotify: true,
      requireInteraction: true,
    }

    event.waitUntil(self.registration.showNotification(title, options))
  } catch (e) {
    event.waitUntil(self.registration.showNotification('FEB Unmul Marketplace', {
      body: event.data.text(),
      icon: '/logo_unmul.png',
    }))
  }
})

self.addEventListener('notificationclick', (event) => {
  event.notification.close()

  const actionUrl = event.notification.data?.action_url

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
      const baseUrl = self.location.origin
      const target = actionUrl ? baseUrl + actionUrl : baseUrl

      for (const client of clients) {
        if (client.url.startsWith(baseUrl) && 'focus' in client) {
          return client.focus().then(() => client.navigate(target))
        }
      }

      if (self.clients.openWindow) {
        return self.clients.openWindow(target)
      }
    })
  )
})
