import { describe, expect, it } from 'vitest'
import { shortcutOf } from './shortcuts'

const pressed = (key: string, target: EventTarget | null = document.body, modifier = false) =>
  shortcutOf({ key, target, altKey: false, ctrlKey: modifier, metaKey: false })

describe('shortcutOf', () => {
  it('reads the keys of the calendar, in either case', () => {
    expect(pressed('t')).toEqual({ kind: 'today' })
    expect(pressed('J')).toEqual({ kind: 'view', view: 'day' })
    expect(pressed('ArrowRight')).toEqual({ kind: 'step', step: 1 })
  })

  it('leaves the keys to a field being typed in', () => {
    expect(pressed('t', document.createElement('input'))).toBeUndefined()
    expect(pressed('m', document.createElement('textarea'))).toBeUndefined()
  })

  it('leaves the keys combined with a modifier to the browser', () => {
    expect(pressed('t', document.body, true)).toBeUndefined()
  })

  it('knows no other key', () => {
    expect(pressed('x')).toBeUndefined()
  })
})
