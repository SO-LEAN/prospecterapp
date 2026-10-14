import type { Path } from 'react-hook-form'
import { z } from 'zod'
import { i18n } from '@/shared/i18n'
import type { Account, AccountInput } from './mutations'

const text = (max: number) =>
  z
    .string()
    .trim()
    .max(max, { error: () => i18n.t('validation.max', { max }) })

export const accountSchema = z.object({
  userName: text(50).min(1, { error: () => i18n.t('validation.required') }),
  firstName: text(100),
  lastName: text(100),
  email: z.union([z.literal(''), z.email({ error: () => i18n.t('validation.email') })]),
  phoneNumber: text(255),
  language: z.string().nullable(),
  password: z.union([
    z.literal(''),
    z
      .string()
      .min(8, { error: () => i18n.t('validation.min', { min: 8 }) })
      .max(4096),
  ]),
  organization: z.object({
    corporateName: text(150),
    form: text(50),
  }),
})

export type AccountFormValues = z.infer<typeof accountSchema>

/** Field paths, as the API reports them in its violations. */
export const accountFields = [
  'userName',
  'firstName',
  'lastName',
  'email',
  'phoneNumber',
  'language',
  'password',
  'organization.corporateName',
  'organization.form',
] as const satisfies readonly Path<AccountFormValues>[]

export type AccountField = (typeof accountFields)[number]

export function toFormValues(account: Account): AccountFormValues {
  return {
    userName: account.userName,
    firstName: account.firstName ?? '',
    lastName: account.lastName ?? '',
    email: account.email ?? '',
    phoneNumber: account.phoneNumber ?? '',
    language: account.language ?? null,
    password: '',
    organization: {
      corporateName: account.organization.corporateName ?? '',
      form: account.organization.form ?? '',
    },
  }
}

export function toInput(values: AccountFormValues): AccountInput {
  const orNull = (value: string) => (value === '' ? null : value)

  return {
    userName: values.userName,
    firstName: orNull(values.firstName),
    lastName: orNull(values.lastName),
    email: orNull(values.email),
    phoneNumber: orNull(values.phoneNumber),
    language: values.language,
    password: orNull(values.password),
    organization: {
      corporateName: orNull(values.organization.corporateName),
      form: orNull(values.organization.form),
    },
  }
}
