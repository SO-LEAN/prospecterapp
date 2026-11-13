import { renderHook } from '@testing-library/react'
import { describe, expect, it, vi } from 'vitest'
import { shortcutOf, useCalendarShortcuts } from './shortcuts'

const pressed = (key: string, target: EventTarget | null = document.body, modifier = false) =>
  shortcutOf({ key, target, altKey: false, ctrlKey: modifier, metaKey: false })

describe('shortcutOf', () => {
  it('reads the keys of the calendar, in either case', () => {
    expect(pressed('t')).toEqual({ kind: 'today' })
    expect(pressed('J')).toEqual({ kind: 'view', view: 'day' })
    expect(pressed('m')).toEqual({ kind: 'view', view: 'month' })
    expect(pressed('s')).toEqual({ kind: 'view', view: 'week' })
    expect(pressed('a')).toEqual({ kind: 'view', view: 'agenda' })
    expect(pressed('ArrowLeft')).toEqual({ kind: 'step', step: -1 })
    expect(pressed('ArrowRight')).toEqual({ kind: 'step', step: 1 })
  })

  it('leaves the keys to a field being typed in', () => {
    expect(pressed('t', document.createElement('input'))).toBeUndefined()
    expect(pressed('m', document.createElement('textarea'))).toBeUndefined()
    expect(pressed('s', document.createElement('select'))).toBeUndefined()
    const editable = document.createElement('div')
    editable.setAttribute('contenteditable', 'true')
    expect(pressed('a', editable)).toBeUndefined()
  })

  it('leaves the keys combined with a modifier to the browser', () => {
    expect(pressed('t', document.body, true)).toBeUndefined()
  })

  it('knows no other key', () => {
    expect(pressed('x')).toBeUndefined()
  })
})

describe('useCalendarShortcuts', () => {
  const press = (key: string, alreadyHandled = false) => {
    const event = new KeyboardEvent('keydown', { key, cancelable: true })
    if (alreadyHandled) event.preventDefault()
    window.dispatchEvent(event)
    return event
  }

  it('takes the key of a shortcut for itself', () => {
    const onShortcut = vi.fn()
    renderHook(() => useCalendarShortcuts(onShortcut))

    const event = press('t')

    expect(onShortcut).toHaveBeenCalledWith({ kind: 'today' })
    expect(event.defaultPrevented).toBe(true)
  })

  it('leaves a key already handled, and any other key', () => {
    const onShortcut = vi.fn()
    renderHook(() => useCalendarShortcuts(onShortcut))

    press('t', true)
    expect(press('x').defaultPrevented).toBe(false)

    expect(onShortcut).not.toHaveBeenCalled()
  })

  it('calls the latest handler it was given', () => {
    const first = vi.fn()
    const latest = vi.fn()
    const { rerender } = renderHook(({ onShortcut }) => useCalendarShortcuts(onShortcut), {
      initialProps: { onShortcut: first },
    })
    rerender({ onShortcut: latest })

    press('t')

    expect(first).not.toHaveBeenCalled()
    expect(latest).toHaveBeenCalledWith({ kind: 'today' })
  })

  it('listens no more once gone', () => {
    const onShortcut = vi.fn()
    const { unmount } = renderHook(() => useCalendarShortcuts(onShortcut))
    unmount()

    press('t')

    expect(onShortcut).not.toHaveBeenCalled()
  })
})
