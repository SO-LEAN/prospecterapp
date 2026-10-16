import { zodResolver } from '@hookform/resolvers/zod'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import type { SignInRefusal } from '@/shared/auth/session'
import { FormField } from '@/shared/forms/form-field'
import { Alert, AlertDescription } from '@/shared/ui/alert'
import { Button } from '@/shared/ui/button'
import { Input } from '@/shared/ui/input'
import { type SignInValues, signInSchema } from '../schema'

const WRONG_CREDENTIALS = ['NotAuthorizedException', 'UserNotFoundException']
const TOO_MANY_ATTEMPTS = ['TooManyRequestsException', 'LimitExceededException']
const ATTEMPTS_EXCEEDED = /attempts exceeded/i

const isRefusal = (error: unknown): error is SignInRefusal =>
  typeof error === 'object' && error !== null && 'code' in error && typeof error.code === 'string'

type Props = {
  /** Signs in; a refusal of the user pool (wrong credentials, too many attempts) is shown here. */
  onSubmit: (values: SignInValues) => Promise<unknown>
}

export function SignInForm({ onSubmit }: Props) {
  const { t } = useTranslation()
  const [refusal, setRefusal] = useState<string | null>(null)
  const form = useForm<SignInValues>({
    resolver: zodResolver(signInSchema),
    defaultValues: { email: '', password: '' },
  })

  const refusalOf = (error: unknown) => {
    if (!isRefusal(error)) {
      return t('signIn.failed')
    }
    if (TOO_MANY_ATTEMPTS.includes(error.code) || ATTEMPTS_EXCEEDED.test(error.message)) {
      return t('signIn.tooManyAttempts')
    }
    return WRONG_CREDENTIALS.includes(error.code) ? t('signIn.wrongCredentials') : t('signIn.failed')
  }

  const submit = form.handleSubmit(async (values) => {
    setRefusal(null)
    try {
      await onSubmit(values)
    } catch (error) {
      setRefusal(refusalOf(error))
    }
  })

  return (
    <form onSubmit={submit} noValidate className="grid gap-4">
      {refusal && (
        <Alert variant="destructive">
          <AlertDescription>{refusal}</AlertDescription>
        </Alert>
      )}
      <FormField form={form} name="email" label={t('signIn.email')}>
        <Input type="email" autoComplete="username" autoFocus {...form.register('email')} />
      </FormField>
      <FormField form={form} name="password" label={t('signIn.password')}>
        <Input type="password" autoComplete="current-password" {...form.register('password')} />
      </FormField>
      <Button type="submit" className="w-full" disabled={form.formState.isSubmitting}>
        {form.formState.isSubmitting ? t('signIn.submitting') : t('signIn.submit')}
      </Button>
    </form>
  )
}
