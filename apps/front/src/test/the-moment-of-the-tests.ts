import { vi } from 'vitest'

/**
 * The moment every test runs at, unless it moves the clock itself (vi.setSystemTime): no test reads the real clock,
 * so that it passes the same today and in a year (docs/architecture/tests.md). A Wednesday morning.
 */
export const THE_MOMENT_OF_THE_TESTS = new Date('2026-11-18T09:00:00Z')

/** The date of the test as it moved since: setup.ts fixes it before each test, the real one is never read. */
export function theMomentOfTheTest(): Date {
  const moment = vi.getMockedSystemTime()
  if (moment === null)
    throw new Error('The date of the test is not fixed: setup.ts fixes it before each test')
  return moment
}
