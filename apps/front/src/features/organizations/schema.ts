import type { Path } from 'react-hook-form'
import { z } from 'zod'
import { i18n } from '@/shared/i18n'
import type { Organization, OrganizationInput } from './queries'

const text = (max: number) =>
  z
    .string()
    .trim()
    .max(max, { error: () => i18n.t('validation.max', { max }) })

/** Same shape and constraints as the API (OrganizationInput) so most errors are caught before sending. */
export const organizationSchema = z
  .object({
    corporateName: text(150),
    form: text(50),
    type: text(50),
    email: z.union([z.literal(''), z.email({ error: () => i18n.t('validation.email') })]),
    phoneNumber: text(255),
    language: z.string().nullable(),
    address: z.object({
      street: text(255),
      postalCode: text(255),
      city: text(255),
      country: z.string().nullable(),
    }),
    observations: text(255),
  })
  .refine((values) => values.corporateName !== '' || values.email !== '', {
    path: ['corporateName'],
    error: () => i18n.t('organizations.validation.nameOrEmail'),
  })

export type OrganizationFormValues = z.infer<typeof organizationSchema>

/** Field paths, as the API reports them in its violations. */
export const organizationFields = [
  'corporateName',
  'form',
  'type',
  'email',
  'phoneNumber',
  'language',
  'address.street',
  'address.postalCode',
  'address.city',
  'address.country',
  'observations',
] as const satisfies readonly Path<OrganizationFormValues>[]

export type OrganizationField = (typeof organizationFields)[number]

export function toFormValues(organization?: Organization): OrganizationFormValues {
  return {
    corporateName: organization?.corporateName ?? '',
    form: organization?.form ?? '',
    type: organization?.type ?? '',
    email: organization?.email ?? '',
    phoneNumber: organization?.phoneNumber ?? '',
    language: organization?.language ?? null,
    address: {
      street: organization?.address?.street ?? '',
      postalCode: organization?.address?.postalCode ?? '',
      city: organization?.address?.city ?? '',
      country: organization?.address?.country ?? null,
    },
    observations: organization?.observations ?? '',
  }
}

/** Empty strings become null: the API stores "not provided", not an empty value. */
export function toInput(values: OrganizationFormValues, holdBy?: number | null): OrganizationInput {
  const orNull = (value: string) => (value === '' ? null : value)

  return {
    corporateName: orNull(values.corporateName),
    form: orNull(values.form),
    type: orNull(values.type),
    email: orNull(values.email),
    phoneNumber: orNull(values.phoneNumber),
    language: values.language,
    address: {
      street: orNull(values.address.street),
      postalCode: orNull(values.address.postalCode),
      city: orNull(values.address.city),
      country: values.address.country,
    },
    observations: orNull(values.observations),
    holdBy: holdBy ?? null,
  }
}
