import { useEffect, useEffectEvent } from 'react'
import type { View } from './dates'

export type Shortcut = { kind: 'today' } | { kind: 'step'; step: 1 | -1 } | { kind: 'view'; view: View }

/** The calendar's keys: T for today, ← → for the previous and the next period, M S J A for the views, in French. */
const SHORTCUTS: Record<string, Shortcut> = {
  t: { kind: 'today' },
  ArrowLeft: { kind: 'step', step: -1 },
  ArrowRight: { kind: 'step', step: 1 },
  m: { kind: 'view', view: 'month' },
  s: { kind: 'view', view: 'week' },
  j: { kind: 'view', view: 'day' },
  a: { kind: 'view', view: 'agenda' },
}

const FIELDS = ['INPUT', 'TEXTAREA', 'SELECT']
const EDITABLE = '[contenteditable=""], [contenteditable="true"]'
/** A dialog over the calendar. It excludes the small month's popover, which the calendar's keys also drive. */
const A_DIALOG = '[data-slot="dialog-content"], [data-slot="alert-dialog-content"]'

/**
 * No shortcut while the member types, or with a modifier, because those keys belong to the field or to the browser.
 * No shortcut in a dialog open over the calendar, such as an exchange or a planning, because its keys are its own.
 */
export function shortcutOf(event: Pick<KeyboardEvent, 'key' | 'target' | 'altKey' | 'ctrlKey' | 'metaKey'>) {
  if (event.altKey || event.ctrlKey || event.metaKey || isTyping(event.target) || inADialog(event.target))
    return undefined
  return SHORTCUTS[event.key.length === 1 ? event.key.toLowerCase() : event.key]
}

/** Listens once and calls the latest `onShortcut`. */
export function useCalendarShortcuts(onShortcut: (shortcut: Shortcut) => void) {
  const onShortcutNow = useEffectEvent(onShortcut)

  useEffect(() => {
    const listener = (event: KeyboardEvent) => {
      const shortcut = shortcutOf(event)
      if (!shortcut || event.defaultPrevented) return
      event.preventDefault()
      onShortcutNow(shortcut)
    }
    window.addEventListener('keydown', listener)
    return () => window.removeEventListener('keydown', listener)
  }, [])
}

function inADialog(target: EventTarget | null): boolean {
  return target instanceof Element && target.closest(A_DIALOG) !== null
}

function isTyping(target: EventTarget | null): boolean {
  return (
    target instanceof HTMLElement && (FIELDS.includes(target.tagName) || target.closest(EDITABLE) !== null)
  )
}
