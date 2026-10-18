import type { FieldValues, Path, UseFormSetError } from 'react-hook-form'
import { i18n } from '@/shared/i18n'
import { fr } from '@/shared/i18n/fr'
import type { components } from './schema'

export type Problem = components['schemas']['ProblemResource']
type Violation = components['schemas']['ViolationResource']
type Reason = keyof typeof fr.refusals

const isWorded = (reason: string | null | undefined): reason is Reason =>
  typeof reason === 'string' && Object.hasOwn(fr.refusals, reason)

/** A refusal in the words of the user when its reason is known (ADR 0073), else as the API worded it. */
function wordingOf(
  reason: string | null | undefined,
  context: Violation['context'] | undefined,
  worded: string,
) {
  return isWorded(reason)
    ? i18n.t(`refusals.${reason}` as const, { ...context, defaultValue: worded })
    : worded
}

/** What to tell the user of a problem: its reason in their words, else its detail. */
export function messageOf(problem: Problem): string {
  const [violation] = problem.violations ?? []

  return wordingOf(problem.reason, violation?.context, problem.detail || problem.title)
}

/** API errors are RFC 9457 problem details; anything else (network...) is not. */
export function isProblem(error: unknown): error is Problem {
  return typeof error === 'object' && error !== null && 'status' in error && 'title' in error
}

/**
 * Shows the API validation errors on the form fields; returns the messages
 * that do not belong to a field (business rules on the whole payload).
 */
export function applyViolations<T extends FieldValues>(
  error: unknown,
  setError: UseFormSetError<T>,
  fields: readonly Path<T>[],
): string[] {
  if (!isProblem(error)) {
    return []
  }
  const global: string[] = []
  for (const violation of error.violations ?? []) {
    const field = fields.find((name) => name === violation.propertyPath)
    if (field) {
      setError(field, {
        type: 'server',
        message: wordingOf(violation.reason, violation.context, violation.title),
      })
    } else {
      global.push(wordingOf(violation.reason, violation.context, violation.title))
    }
  }
  if (global.length === 0 && (error.violations ?? []).length === 0) {
    global.push(messageOf(error))
  }
  return global
}
