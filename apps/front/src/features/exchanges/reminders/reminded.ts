import type { Exchange } from '../queries'
import { type Reminded, reminderOf } from './schedule'

/** The browser's tabs share it. When one tab has shown a reminder, the others do not show it again. */
const STORAGE_KEY = 'prospecter.reminders'

/** How long after an exchange its reminders are forgotten. */
const KEPT_MS = 24 * 60 * 60_000

type Reminder = Pick<Exchange, 'id' | 'occurredAt'>

/** When the browser keeps nothing (a private window, or its storage refused), the tab remembers on its own. */
let keptByTheTab: Reminded = {}

export function readReminded(): Reminded {
  try {
    const written = localStorage.getItem(STORAGE_KEY)
    return written ? (JSON.parse(written) as Reminded) : {}
  } catch {
    return keptByTheTab
  }
}

/** This tab showed the reminder. No other tab shows it again, unless the member asks for it later. */
export function markShown(exchange: Reminder, now: Date) {
  write(exchange, null, now)
}

/** The reminder shows again at `again`, in whichever tab is open then. */
export function remindAgain(exchange: Reminder, again: Date, now: Date) {
  write(exchange, again.toISOString(), now)
}

/** Called when another tab has shown a reminder or asked for it again later. */
export function whenRemindedInAnotherTab(changed: () => void): () => void {
  const listener = (event: StorageEvent) => {
    if (event.key === STORAGE_KEY) {
      changed()
    }
  }
  window.addEventListener('storage', listener)
  return () => window.removeEventListener('storage', listener)
}

function write(exchange: Reminder, again: string | null, now: Date) {
  const kept = Object.fromEntries(
    Object.entries(readReminded()).filter(
      ([, shown]) => new Date(shown.occurredAt).getTime() > now.getTime() - KEPT_MS,
    ),
  )
  keptByTheTab = { ...kept, [reminderOf(exchange)]: { occurredAt: exchange.occurredAt, again } }
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(keptByTheTab))
  } catch {
    // the browser keeps nothing, so the tab remembers on its own
  }
}
