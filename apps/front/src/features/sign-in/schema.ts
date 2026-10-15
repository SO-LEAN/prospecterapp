import { z } from 'zod'
import { i18n } from '@/shared/i18n'

export const signInSchema = z.object({
  email: z.email({ error: () => i18n.t('validation.email') }),
  password: z.string().min(1, { error: () => i18n.t('validation.required') }),
})

export type SignInValues = z.infer<typeof signInSchema>
