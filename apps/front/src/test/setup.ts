import '@testing-library/jest-dom/vitest'
import { cleanup } from '@testing-library/react'
import { afterEach, beforeEach, vi } from 'vitest'
import '@/shared/i18n'
import { THE_MOMENT_OF_THE_TESTS } from './the-moment-of-the-tests'

// jsdom has no pointer capture, which the toasts of sonner take when pressed
Element.prototype.setPointerCapture ??= () => {}

// nor does it tell whether pointer capture is held, nor scroll: the select of Radix needs both once open
Element.prototype.hasPointerCapture ??= () => false
Element.prototype.scrollIntoView ??= () => {}

// jsdom has no ResizeObserver, which Radix uses to place a tooltip next to what it explains
globalThis.ResizeObserver ??= class {
  observe() {}
  unobserve() {}
  disconnect() {}
}

// the date of every test fixed: a test moves it by vi.setSystemTime; its timers stay real unless it fakes them
beforeEach(() => {
  vi.setSystemTime(THE_MOMENT_OF_THE_TESTS)
})

afterEach(() => {
  cleanup()
  vi.useRealTimers()
})
