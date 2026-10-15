import { notFound } from '@tanstack/react-router'
import { z } from 'zod'
import { isProblem } from '@/shared/api/problem'

const uuid = z.uuid()

/** `$organizationId` URL segment: a UUID, anything else is a 404. */
export const organizationIdParams = {
  parse: ({ organizationId }: { organizationId: string }) => {
    if (!uuid.safeParse(organizationId).success) {
      throw notFound()
    }
    return { organizationId }
  },
  stringify: ({ organizationId }: { organizationId: string }) => ({ organizationId }),
}

/** An organization the API does not know (or does not show to me) is a missing page, like a malformed id. */
export const notFoundWhenTheApiSaysSo = (error: unknown): never => {
  if (isProblem(error) && error.status === 404) {
    throw notFound()
  }
  throw error
}
