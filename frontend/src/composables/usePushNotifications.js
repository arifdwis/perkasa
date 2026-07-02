import { ref } from 'vue'
import axios from 'axios'

const vapidPublicKey = 'BKHCvec6EIPpw5j3dNR2qN25zzi1wml6Q2QsHNrFVZcGSOqoxOYLf_IrNH9CxLr6llpp8TgwKTUbDOx-0SMxGfQ'

const isSubscribed = ref(false)
const isDenied = ref(Notification.permission === 'denied')
const lastError = ref('')

export function usePushNotifications() {
  const urlB64ToUint8Array = (base64String) => {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4)
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')
    const rawData = window.atob(base64)
    return new Uint8Array([...rawData].map((char) => char.charCodeAt(0)))
  }

  const subscribe = async () => {
    lastError.value = ''

    if (!('serviceWorker' in navigator)) {
      lastError.value = 'Browser tidak mendukung Service Worker.'
      return false
    }
    if (!('PushManager' in window)) {
      lastError.value = 'Browser tidak mendukung Push API.'
      return false
    }

    try {
      const permission = await Notification.requestPermission()
      if (permission !== 'granted') {
        isDenied.value = true
        lastError.value = 'Izin notifikasi ditolak. Ubah di pengaturan browser.'
        return false
      }

      const registration = await navigator.serviceWorker.ready
      if (!registration) {
        lastError.value = 'Service worker belum siap. Coba refresh halaman.'
        return false
      }

      const existingSub = await registration.pushManager.getSubscription()
      if (existingSub) {
        isSubscribed.value = true
        isDenied.value = false
        return true
      }

      const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlB64ToUint8Array(vapidPublicKey),
      })

      await axios.post('/push/subscribe', subscription.toJSON())
      isSubscribed.value = true
      isDenied.value = false
      return true
    } catch (err) {
      console.error('Failed to subscribe to push', err)
      if (err.response?.status === 401) {
        lastError.value = 'Session habis, silakan login ulang.'
      } else if (err.message?.includes('userVisibleOnly')) {
        lastError.value = 'Push hanya bisa dengan userVisibleOnly=true.'
      } else {
        lastError.value = 'Gagal subscribe: ' + (err.message?.slice(0, 60) || 'unknown')
      }
      return false
    }
  }

  const unsubscribe = async () => {
    if (!('serviceWorker' in navigator)) return

    try {
      const registration = await navigator.serviceWorker.ready
      const subscription = await registration.pushManager.getSubscription()

      if (subscription) {
        await axios.post('/push/unsubscribe', subscription.toJSON())
        await subscription.unsubscribe()
      }

      isSubscribed.value = false
    } catch (err) {
      console.error('Failed to unsubscribe', err)
    }
  }

  const checkExisting = async () => {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) return

    try {
      const registration = await navigator.serviceWorker.ready
      const subscription = await registration.pushManager.getSubscription()
      isSubscribed.value = !!subscription
      isDenied.value = Notification.permission === 'denied'
    } catch (err) {
      // ignore
    }
  }

  return { isSubscribed, isDenied, lastError, subscribe, unsubscribe, checkExisting }
}
