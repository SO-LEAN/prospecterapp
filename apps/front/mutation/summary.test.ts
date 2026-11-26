import { describe, expect, it } from 'vitest'
import {
  type InfectionReport,
  mutationOfInfection,
  mutationOfStryker,
  type StrykerMutant,
  summaryOf,
  summaryWithoutReport,
} from './summary.ts'

const ROOT = '/var/www/src'

const infectionStats = {
  killedCount: 0,
  killedByStaticAnalysisCount: 0,
  notCoveredCount: 0,
  escapedCount: 0,
  errorCount: 0,
  syntaxErrorCount: 0,
  skippedCount: 0,
  timeOutCount: 0,
  msi: 0,
}

function infectionReport(report: Partial<InfectionReport>): InfectionReport {
  return { stats: infectionStats, escaped: [], uncovered: [], ...report }
}

describe('the summary of a mutation run of Infection', () => {
  it("gives Infection's score and counts", () => {
    const report = infectionReport({
      stats: {
        ...infectionStats,
        killedCount: 50,
        escapedCount: 4,
        notCoveredCount: 3,
        timeOutCount: 2,
        skippedCount: 1,
        msi: 86.89,
      },
    })

    expect(summaryOf('the core', mutationOfInfection(report, ROOT))).toBe(
      [
        '## Mutation of the core',
        '',
        'Score: 86.89 %',
        '',
        '- Killed: 50',
        '- Survived: 4',
        '- Not covered: 3',
        '- Timed out: 2',
        '- Skipped: 1',
        '',
      ].join('\n'),
    )
  })

  it('counts as killed the mutants Infection killed by static analysis or by an error, as its score does', () => {
    const report = infectionReport({
      stats: {
        ...infectionStats,
        killedCount: 5,
        killedByStaticAnalysisCount: 1,
        errorCount: 2,
        syntaxErrorCount: 3,
      },
    })

    expect(summaryOf('the core', mutationOfInfection(report, ROOT))).toContain('- Killed: 11\n')
  })

  it('lists each mutant Infection let survive, by its path from the root of the repository, its line and its mutator', () => {
    const report = infectionReport({
      escaped: [
        infectionMutant('GreaterThan', '/var/www/src/packages/core/src/Booking/Domain/Slot.php', 12),
        infectionMutant(
          'MethodCallRemoval',
          '/var/www/src/packages/phpstan-rules/EventStorming/Wall.php',
          40,
        ),
      ],
    })

    expect(summaryOf('the core', mutationOfInfection(report, ROOT))).toMatch(
      /\nSurvived:\n\n- packages\/core\/src\/Booking\/Domain\/Slot\.php:12 GreaterThan\n- packages\/phpstan-rules\/EventStorming\/Wall\.php:40 MethodCallRemoval\n$/,
    )
  })

  it('lists apart the mutants of lines no test covers, after the survivors', () => {
    const report = infectionReport({
      escaped: [infectionMutant('GreaterThan', '/var/www/src/packages/core/src/Booking/Domain/Slot.php', 12)],
      uncovered: [infectionMutant('Throw_', '/var/www/src/packages/core/src/Booking/Domain/Slot.php', 30)],
    })

    expect(summaryOf('the core', mutationOfInfection(report, ROOT))).toMatch(
      /\nSurvived:\n\n- packages\/core\/src\/Booking\/Domain\/Slot\.php:12 GreaterThan\n\nNot covered:\n\n- packages\/core\/src\/Booking\/Domain\/Slot\.php:30 Throw_\n$/,
    )
  })
})

describe('the summary of a mutation run of Stryker', () => {
  it('gives the score Stryker gives, the killed and the timed out mutants out of the valid ones, and its counts', () => {
    const report = {
      projectRoot: '/var/www/src/apps/front',
      files: {
        'src/a.ts': {
          mutants: [
            strykerMutant('Killed'),
            strykerMutant('Killed'),
            strykerMutant('Timeout'),
            strykerMutant('Survived'),
          ],
        },
        'src/b.ts': {
          mutants: [
            strykerMutant('Killed'),
            strykerMutant('NoCoverage'),
            strykerMutant('Ignored'),
            strykerMutant('CompileError'),
            strykerMutant('RuntimeError'),
          ],
        },
      },
    }

    expect(summaryOf('the front', mutationOfStryker(report, ROOT))).toMatch(
      /^## Mutation of the front\n\nScore: 66\.67 %\n\n- Killed: 3\n- Survived: 1\n- Not covered: 1\n- Timed out: 1\n- Skipped: 1\n/,
    )
  })

  it('gives no score when no mutant is valid', () => {
    const report = {
      projectRoot: '/var/www/src/apps/front',
      files: { 'src/a.ts': { mutants: [strykerMutant('Ignored')] } },
    }

    expect(summaryOf('the front', mutationOfStryker(report, ROOT))).toContain(
      '\nScore: none, no mutant to judge\n',
    )
  })

  it('lists the survivors, then the mutants no test covers, by their path from the root of the repository', () => {
    const report = {
      projectRoot: '/var/www/src/apps/front',
      files: {
        'event-storming/grid.ts': {
          mutants: [
            strykerMutant('Survived', 'StringLiteral', 27),
            strykerMutant('Killed', 'BooleanLiteral', 28),
            strykerMutant('NoCoverage', 'ArithmeticOperator', 31),
          ],
        },
        'event-storming/svg.ts': { mutants: [strykerMutant('Survived', 'EqualityOperator', 4)] },
      },
    }

    expect(summaryOf("the event storming's generator", mutationOfStryker(report, ROOT))).toMatch(
      /\nSurvived:\n\n- apps\/front\/event-storming\/grid\.ts:27 StringLiteral\n- apps\/front\/event-storming\/svg\.ts:4 EqualityOperator\n\nNot covered:\n\n- apps\/front\/event-storming\/grid\.ts:31 ArithmeticOperator\n$/,
    )
  })
})

describe('the summary of a mutation run without a report', () => {
  it('says that the branch adds no line to mutate, or that the tool stopped before it wrote its report', () => {
    expect(summaryWithoutReport('the core')).toBe(
      '## Mutation of the core\n\nNo report: the branch adds no line to mutate, or the tool stopped before it wrote its report.\n',
    )
  })
})

function strykerMutant(
  status: StrykerMutant['status'],
  mutatorName = 'ConditionalExpression',
  line = 1,
): StrykerMutant {
  return { status, mutatorName, location: { start: { line } } }
}

function infectionMutant(mutatorName: string, originalFilePath: string, originalStartLine: number) {
  return { mutator: { mutatorName, originalFilePath, originalStartLine } }
}
