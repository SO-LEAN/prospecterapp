import { notFound } from '@tanstack/react-router'
import { z } from 'zod'
import { isProblem } from '@/shared/api/problem'

const uuid = z.uuid()

/** A `$…Id` URL segment: a UUID, anything else is a 404 before any request. */
export const uuidParams = <Key extends string>(key: Key) => ({
  parse: (params: Record<Key, string>): Record<Key, string> => {
    if (!uuid.safeParse(params[key]).success) {
      throw notFound()
    }
    return params
  },
  stringify: (params: Record<Key, string>): Record<Key, string> => params,
})

/** A resource the API does not know (or does not show to me) is a missing page, like a malformed id. */
export const notFoundWhenTheApiSaysSo = (error: unknown): never => {
  if (isProblem(error) && error.status === 404) {
    throw notFound()
  }
  throw error
}
