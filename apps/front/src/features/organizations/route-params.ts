import { notFound } from '@tanstack/react-router'

/** `$organizationId` URL segment <-> numeric id; anything else is a 404. */
export const organizationIdParams = {
  parse: ({ organizationId }: { organizationId: string }) => {
    const id = Number(organizationId)
    if (!Number.isInteger(id) || id <= 0) {
      throw notFound()
    }
    return { organizationId: id }
  },
  stringify: ({ organizationId }: { organizationId: number }) => ({ organizationId: String(organizationId) }),
}
