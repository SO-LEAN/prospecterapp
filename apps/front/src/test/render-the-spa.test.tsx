import { expect, it, vi } from 'vitest'
import '@/test/render-the-spa'

const theModulesLoaded = vi.hoisted(() => new Set<string>())

vi.mock('@/app/layout/app-shell', async (importOriginal) => {
  theModulesLoaded.add('app-shell')
  return importOriginal()
})

it('loads the code of the routes while the file of a test loads, before its first test', () => {
  expect(theModulesLoaded).toContain('app-shell')
})
