import type { FieldValues, Path, UseFormSetError } from 'react-hook-form'
import type { components } from './schema'

export type Problem = components['schemas']['ProblemResource']

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
      setError(field, { type: 'server', message: violation.title })
    } else {
      global.push(violation.title)
    }
  }
  if (global.length === 0 && (error.violations ?? []).length === 0) {
    global.push(error.detail)
  }
  return global
}
