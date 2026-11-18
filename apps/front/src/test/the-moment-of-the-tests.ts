/**
 * The moment every test runs at, unless it moves the clock itself (vi.setSystemTime): no test reads the real clock,
 * so that it passes the same today and in a year (docs/architecture/tests.md). A Wednesday morning.
 */
export const THE_MOMENT_OF_THE_TESTS = new Date('2026-11-18T09:00:00Z')
