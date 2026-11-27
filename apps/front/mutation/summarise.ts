/**
 * Prints the summary of a mutation run from its tool's JSON reports, one per shard, its paths from the directory it
 * runs in, the root of the repository. A report the tool did not write is passed over. This file only reads and prints;
 * what it prints is decided and tested in `summary.ts`.
 * node apps/front/mutation/summarise.ts <infection|stryker> <title> <report.json>... (make mutation-summary).
 */
import { existsSync, readFileSync } from 'node:fs'
import {
  type InfectionReport,
  mutationOfInfection,
  mutationOfShards,
  mutationOfStryker,
  type StrykerReport,
  summaryOf,
  summaryWithoutReport,
} from './summary.ts'

const [tool, title, ...reportFiles] = process.argv.slice(2)
if ((tool !== 'infection' && tool !== 'stryker') || title === undefined) {
  throw new Error('Usage: node apps/front/mutation/summarise.ts <infection|stryker> <title> <report.json>...')
}
const root = process.cwd()

const mutationOf = (report: unknown) =>
  tool === 'infection'
    ? mutationOfInfection(report as InfectionReport, root)
    : mutationOfStryker(report as StrykerReport, root)

const shards = reportFiles
  .filter((file) => existsSync(file))
  .map((file) => mutationOf(JSON.parse(readFileSync(file, 'utf8'))))

process.stdout.write(
  shards.length > 0 ? summaryOf(title, mutationOfShards(shards)) : summaryWithoutReport(title),
)
