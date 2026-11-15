import type { Exchange } from '../queries'
import { type Reminded, reminderOf } from './schedule'

/** Shared by the tabs of the browser: a reminder one of them showed, the others do not show it again. */
const STORAGE_KEY = 'prospecter.reminders'

/** What was reminded of an exchange is forgotten that long after it. */
const KEPT_MS = 24 * 60 * 60_000

type Reminder = Pick<Exchange, 'id' | 'occurredAt'>

/** When the browser keeps nothing (a private window, its storage refused), the tab remembers on its own. */
let keptByTheTab: Reminded = {}

export function readReminded(): Reminded {
  try {
    const written = localStorage.getItem(STORAGE_KEY)
    return written ? (JSON.parse(written) as Reminded) : {}
  } catch {
    return keptByTheTab
  }
}

/** Shown by this tab: no other shows it again, unless asked to later. */
export function markShown(exchange: Reminder, now: Date) {
  write(exchange, null, now)
}

/** Shown again at `again`, by whichever tab is open then. */
export function remindAgain(exchange: Reminder, again: Date, now: Date) {
  write(exchange, again.toISOString(), now)
}

/** Called when another tab showed a reminder or asked it again for later. */
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
    // the browser keeps nothing: the tab remembers on its own
  }
}
