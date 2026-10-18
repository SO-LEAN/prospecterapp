import '@testing-library/jest-dom/vitest'
import { cleanup } from '@testing-library/react'
import { afterEach } from 'vitest'
import '@/shared/i18n'

// jsdom has no pointer capture, which the toasts of sonner take when pressed
Element.prototype.setPointerCapture ??= () => {}

afterEach(() => {
  cleanup()
})
