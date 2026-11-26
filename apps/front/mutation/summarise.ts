/**
 * Prints the summary of a mutation run from its tool's JSON report, its paths from the directory it runs in, the root
 * of the repository. This file only reads and prints; what it prints is decided and tested in `summary.ts`.
 * node apps/front/mutation/summarise.ts <infection|stryker> <report.json> <title> (make mutation-summary).
 */
import { existsSync, readFileSync } from 'node:fs'
import {
  type InfectionReport,
  mutationOfInfection,
  mutationOfStryker,
  type StrykerReport,
  summaryOf,
  summaryWithoutReport,
} from './summary.ts'

const [tool, reportFile, title] = process.argv.slice(2)
if ((tool !== 'infection' && tool !== 'stryker') || reportFile === undefined || title === undefined) {
  throw new Error('Usage: node apps/front/mutation/summarise.ts <infection|stryker> <report.json> <title>')
}
const root = process.cwd()

const mutationOf = (report: unknown) =>
  tool === 'infection'
    ? mutationOfInfection(report as InfectionReport, root)
    : mutationOfStryker(report as StrykerReport, root)

process.stdout.write(
  existsSync(reportFile)
    ? summaryOf(title, mutationOf(JSON.parse(readFileSync(reportFile, 'utf8'))))
    : summaryWithoutReport(title),
)
