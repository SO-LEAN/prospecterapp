/** Whether this browser shows our notifications: granted, refused, not asked yet, or unsupported. */
export type NotificationsPermission = NotificationPermission | 'unsupported'

const canNotify = () => typeof window !== 'undefined' && 'Notification' in window

export function notificationsPermission(): NotificationsPermission {
  return canNotify() ? Notification.permission : 'unsupported'
}

/** Asks the member once whether this browser may notify them. The browser remembers a refusal. */
export async function askToNotify(): Promise<NotificationsPermission> {
  return canNotify() ? Notification.requestPermission() : 'unsupported'
}

/** Shows a browser notification once the member allowed it. A click on it brings them back to the tab. */
export function notify(title: string, body: string, onClick?: () => void) {
  const notification = new Notification(title, { body })
  if (onClick) {
    notification.onclick = () => {
      window.focus()
      onClick()
      notification.close()
    }
  }
}
