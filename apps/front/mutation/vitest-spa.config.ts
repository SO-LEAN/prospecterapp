import config, { THE_SPA_TESTS } from '../vite.config'

/**
 * The tests the SPA's mutation runs: the `spa` project only. The tools' tests cover no line of the SPA, and each
 * static mutant would run them all (docs/architecture/tests.md).
 */
export default { ...config, test: { ...config.test, projects: [THE_SPA_TESTS] } }
