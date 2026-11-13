import { afterEach, describe, expect, it, vi } from 'vitest'
import { defaultView } from './view'

const aScreen = (phone: boolean) => (query: string) =>
  ({ matches: phone && query === '(max-width: 767px)' }) as MediaQueryList

describe('defaultView', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
  })

  it('opens a phone on the list of the agenda, a wider screen on the month', () => {
    vi.stubGlobal('matchMedia', aScreen(true))
    expect(defaultView()).toBe('agenda')

    vi.stubGlobal('matchMedia', aScreen(false))
    expect(defaultView()).toBe('month')
  })
})
