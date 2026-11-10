import { zodResolver } from '@hookform/resolvers/zod'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'
import { applyViolations } from '@/shared/api/problem'
import { FormField } from '@/shared/forms/form-field'
import { text } from '@/shared/forms/text'
import { i18n } from '@/shared/i18n'
import { Alert, AlertDescription } from '@/shared/ui/alert'
import { Button } from '@/shared/ui/button'
import { Input } from '@/shared/ui/input'
import { type Account, useCompleteMyProfile } from '../mutations'

const required = (max: number) => text(max).min(1, { error: () => i18n.t('validation.required') })

const myProfileSchema = z.object({
  firstName: required(100),
  lastName: required(100),
  email: z.email({ error: () => i18n.t('validation.email') }),
})

type MyProfileValues = z.infer<typeof myProfileSchema>

const myProfileFields = ['firstName', 'lastName', 'email'] as const

type Props = {
  account: Account
  /** Proposed for the profile: the email the member has just signed in with. */
  loginEmail: string
  onCompleted: () => void
}

/** The name of a member signed in for the first time, and the email of their profile, proposed, theirs to change. */
export function CompleteMyProfileForm({ account, loginEmail, onCompleted }: Props) {
  const { t } = useTranslation()
  const complete = useCompleteMyProfile()
  const [globalErrors, setGlobalErrors] = useState<string[]>([])
  const form = useForm<MyProfileValues>({
    resolver: zodResolver(myProfileSchema),
    defaultValues: { firstName: '', lastName: '', email: account.contactDetails.email || loginEmail },
  })
  const { isSubmitting } = form.formState

  const submit = form.handleSubmit(async ({ firstName, lastName, email }) => {
    setGlobalErrors([])
    try {
      await complete.mutateAsync({
        name: { firstName, lastName },
        contactDetails: {
          email,
          phoneNumber: account.contactDetails.phoneNumber ?? null,
          language: account.contactDetails.language ?? null,
        },
      })
      onCompleted()
    } catch (error) {
      setGlobalErrors(applyViolations(error, form.setError, myProfileFields))
    }
  })

  return (
    <form onSubmit={submit} noValidate className="grid gap-4">
      {globalErrors.length > 0 && (
        <Alert variant="destructive">
          <AlertDescription>{globalErrors.join(' ')}</AlertDescription>
        </Alert>
      )}
      <FormField form={form} name="firstName" label={t('fields.firstName')}>
        <Input autoComplete="given-name" autoFocus {...form.register('firstName')} />
      </FormField>
      <FormField form={form} name="lastName" label={t('fields.lastName')}>
        <Input autoComplete="family-name" {...form.register('lastName')} />
      </FormField>
      <FormField form={form} name="email" label={t('fields.email')} hint={t('completeMyProfile.emailHint')}>
        <Input type="email" autoComplete="email" {...form.register('email')} />
      </FormField>
      <Button type="submit" className="w-full" disabled={isSubmitting}>
        {isSubmitting ? t('completeMyProfile.submitting') : t('completeMyProfile.submit')}
      </Button>
    </form>
  )
}
