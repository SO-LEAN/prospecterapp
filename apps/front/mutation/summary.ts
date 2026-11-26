/**
 * The summary of a mutation run, from the JSON report of its tool: Infection for the core, Stryker for the front and
 * the event storming's generator. The CI writes it in the job's summary and in the artifact beside the report
 * (`docs/architecture/tests.md`, "Mutation testing").
 */
import { join, relative } from 'node:path'

type InfectionMutant = {
  mutator: { mutatorName: string; originalFilePath: string; originalStartLine: number }
}

export type InfectionReport = {
  stats: {
    killedCount: number
    killedByStaticAnalysisCount: number
    notCoveredCount: number
    escapedCount: number
    errorCount: number
    syntaxErrorCount: number
    skippedCount: number
    timeOutCount: number
    msi: number
  }
  escaped: InfectionMutant[]
  uncovered: InfectionMutant[]
}

export type StrykerMutant = {
  status:
    | 'Killed'
    | 'Survived'
    | 'NoCoverage'
    | 'Timeout'
    | 'Ignored'
    | 'CompileError'
    | 'RuntimeError'
    | 'Pending'
  mutatorName: string
  location: { start: { line: number } }
}

export type StrykerReport = { projectRoot: string; files: Record<string, { mutants: StrykerMutant[] }> }

/** A mutant by the path of its file from the root of the repository, its line and its mutator. */
export type Mutant = { file: string; line: number; mutator: string }

export type Mutation = {
  score: number
  killed: number
  survived: number
  notCovered: number
  timedOut: number
  skipped: number
  survivors: Mutant[]
  uncovered: Mutant[]
}

export function mutationOfInfection({ stats, escaped, uncovered }: InfectionReport, root: string): Mutation {
  const mutantOf = ({ mutator }: InfectionMutant): Mutant => ({
    file: relative(root, mutator.originalFilePath),
    line: mutator.originalStartLine,
    mutator: mutator.mutatorName,
  })

  return {
    score: stats.msi,
    killed: stats.killedCount + stats.killedByStaticAnalysisCount + stats.errorCount + stats.syntaxErrorCount,
    survived: stats.escapedCount,
    notCovered: stats.notCoveredCount,
    timedOut: stats.timeOutCount,
    skipped: stats.skippedCount,
    survivors: escaped.map(mutantOf),
    uncovered: uncovered.map(mutantOf),
  }
}

export function mutationOfStryker({ projectRoot, files }: StrykerReport, root: string): Mutation {
  const mutants = Object.entries(files).flatMap(([file, { mutants }]) =>
    mutants.map(({ status, mutatorName, location }) => ({
      status,
      mutant: {
        file: relative(root, join(projectRoot, file)),
        line: location.start.line,
        mutator: mutatorName,
      },
    })),
  )
  const mutantsOf = (status: StrykerMutant['status']): Mutant[] =>
    mutants.filter((each) => each.status === status).map((each) => each.mutant)
  const killed = mutantsOf('Killed').length
  const timedOut = mutantsOf('Timeout').length
  const survivors = mutantsOf('Survived')
  const uncovered = mutantsOf('NoCoverage')

  return {
    score: scoreOf(killed + timedOut, killed + timedOut + survivors.length + uncovered.length),
    killed,
    survived: survivors.length,
    notCovered: uncovered.length,
    timedOut,
    skipped: mutantsOf('Ignored').length,
    survivors,
    uncovered,
  }
}

/** Stryker's mutation score: the detected mutants out of the valid ones, in percent with two decimals. */
function scoreOf(detected: number, valid: number): number {
  return Math.round((10_000 * detected) / valid) / 100
}

export function summaryOf(title: string, mutation: Mutation): string {
  return [
    headingOf(title),
    '',
    scoreLineOf(mutation),
    '',
    `- Killed: ${mutation.killed}`,
    `- Survived: ${mutation.survived}`,
    `- Not covered: ${mutation.notCovered}`,
    `- Timed out: ${mutation.timedOut}`,
    `- Skipped: ${mutation.skipped}`,
    ...listOf('Survived', mutation.survivors),
    ...listOf('Not covered', mutation.uncovered),
    '',
  ].join('\n')
}

export function summaryWithoutReport(title: string): string {
  return [
    headingOf(title),
    '',
    'No report: the branch adds no line to mutate, or the tool stopped before it wrote its report.',
    '',
  ].join('\n')
}

function headingOf(title: string): string {
  return `## Mutation of ${title}`
}

function scoreLineOf({ score, killed, survived, notCovered, timedOut }: Mutation): string {
  const judged = killed + survived + notCovered + timedOut
  return judged === 0 ? 'Score: none, no mutant to judge' : `Score: ${score} %`
}

function listOf(heading: string, mutants: Mutant[]): string[] {
  if (mutants.length === 0) return []
  return ['', `${heading}:`, '', ...mutants.map(({ file, line, mutator }) => `- ${file}:${line} ${mutator}`)]
}
