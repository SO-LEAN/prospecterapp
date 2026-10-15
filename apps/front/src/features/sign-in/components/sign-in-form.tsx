import { zodResolver } from '@hookform/resolvers/zod'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { isProblem } from '@/shared/api/problem'
import { FormField } from '@/shared/forms/form-field'
import { Alert, AlertDescription } from '@/shared/ui/alert'
import { Button } from '@/shared/ui/button'
import { Input } from '@/shared/ui/input'
import { type SignInValues, signInSchema } from '../schema'

const TOO_MANY_ATTEMPTS = 429
const WRONG_CREDENTIALS = 401

type Props = {
  /** Signs in; a problem of the authorization server (wrong credentials, too many attempts) is shown here. */
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
    if (isProblem(error) && error.status === TOO_MANY_ATTEMPTS) {
      return t('signIn.tooManyAttempts')
    }
    return isProblem(error) && error.status === WRONG_CREDENTIALS
      ? t('signIn.wrongCredentials')
      : t('signIn.failed')
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
