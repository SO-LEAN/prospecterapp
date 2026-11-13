/** Whether this browser shows our notifications: granted, refused, not asked yet, or not able to. */
export type NotificationsPermission = NotificationPermission | 'unsupported'

const canNotify = () => typeof window !== 'undefined' && 'Notification' in window

export function notificationsPermission(): NotificationsPermission {
  return canNotify() ? Notification.permission : 'unsupported'
}

/** Asks the member, once, whether this browser may notify them; a refusal is kept by the browser. */
export async function askToNotify(): Promise<NotificationsPermission> {
  return canNotify() ? Notification.requestPermission() : 'unsupported'
}

/** Shows a notification of the browser, once the member allowed it. */
export function notify(title: string, body: string) {
  new Notification(title, { body })
}
