import { zodResolver } from '@hookform/resolvers/zod'
import { useState } from 'react'
import { Controller, useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { useAuth } from 'react-oidc-context'
import { toast } from 'sonner'
import { applyViolations } from '@/shared/api/problem'
import { initials } from '@/shared/lib/format'
import { Alert, AlertDescription } from '@/shared/ui/alert'
import { Button } from '@/shared/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/shared/ui/card'
import { CountrySelect } from '@/shared/ui/form/country-select'
import { Field } from '@/shared/ui/form/field'
import { ImageUpload } from '@/shared/ui/form/image-upload'
import { Input } from '@/shared/ui/input'
import { type Account, useReplaceAccount, useUploadOrganizationLogo, useUploadPicture } from '../mutations'
import {
  type AccountField,
  type AccountFormValues,
  accountFields,
  accountSchema,
  toFormValues,
  toInput,
} from '../schema'

export function AccountForm({ account }: { account: Account }) {
  const { t } = useTranslation()
  const auth = useAuth()
  const replace = useReplaceAccount()
  const uploadPicture = useUploadPicture()
  const uploadLogo = useUploadOrganizationLogo()
  const [globalErrors, setGlobalErrors] = useState<string[]>([])
  const { register, control, handleSubmit, setError, getFieldState, reset, formState } =
    useForm<AccountFormValues>({
      resolver: zodResolver(accountSchema),
      values: toFormValues(account),
      resetOptions: { keepDirtyValues: true },
    })

  const { errors, isSubmitting, isDirty } = formState

  const signInAgainSinceTheTokenIsBoundToTheUserName = async () => {
    toast.info(t('account.userNameChanged'))
    await auth.removeUser()
    await auth.signinRedirect()
  }

  const submit = handleSubmit(async (values) => {
    setGlobalErrors([])
    try {
      const updated = await replace.mutateAsync({ body: toInput(values) })
      if (updated.userName !== account.userName) {
        await signInAgainSinceTheTokenIsBoundToTheUserName()
        return
      }
      reset(toFormValues(updated))
      toast.success(t('account.success'))
    } catch (error) {
      setGlobalErrors(applyViolations(error, setError, accountFields))
    }
  })

  const field = (name: AccountField) => ({
    label: t(`account.fields.${name}`),
    error: getFieldState(name, formState).error?.message,
  })
  const fullName = [account.firstName, account.lastName].filter(Boolean).join(' ') || account.userName

  return (
    <form onSubmit={submit} noValidate className="space-y-6">
      {globalErrors.length > 0 && (
        <Alert variant="destructive">
          <AlertDescription>{globalErrors.join(' ')}</AlertDescription>
        </Alert>
      )}

      <Card>
        <CardHeader>
          <CardTitle className="text-base">{t('account.profile')}</CardTitle>
          <CardDescription>{t('account.subtitle')}</CardDescription>
        </CardHeader>
        <CardContent className="space-y-6">
          <ImageUpload
            shape="circle"
            imageUrl={account.picture?.thumbnailUrl}
            alt={fullName}
            label={t('account.changePicture')}
            pending={uploadPicture.isPending}
            onUpload={(file) => uploadPicture.mutate(file)}
            fallback={
              <span className="font-semibold text-2xl text-muted-foreground">{initials(fullName)}</span>
            }
          />
          <div className="grid gap-5 sm:grid-cols-2">
            <Field {...field('firstName')}>
              <Input autoComplete="given-name" {...register('firstName')} />
            </Field>
            <Field {...field('lastName')}>
              <Input autoComplete="family-name" {...register('lastName')} />
            </Field>
            <Field {...field('email')}>
              <Input type="email" autoComplete="email" {...register('email')} />
            </Field>
            <Field {...field('phoneNumber')} optional={t('common.optional')}>
              <Input type="tel" autoComplete="tel" {...register('phoneNumber')} />
            </Field>
            <Field {...field('language')} optional={t('common.optional')}>
              <Controller
                control={control}
                name="language"
                render={({ field: { value, onChange } }) => (
                  <CountrySelect value={value} onChange={onChange} />
                )}
              />
            </Field>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">{t('account.organization')}</CardTitle>
        </CardHeader>
        <CardContent className="space-y-6">
          <ImageUpload
            imageUrl={account.organization.logo?.thumbnailUrl}
            alt={account.organization.corporateName ?? ''}
            label={t('account.changeLogo')}
            pending={uploadLogo.isPending}
            onUpload={(file) => uploadLogo.mutate(file)}
            fallback={
              <span className="font-semibold text-2xl text-muted-foreground">
                {initials(account.organization.corporateName ?? '')}
              </span>
            }
          />
          <div className="grid gap-5 sm:grid-cols-2">
            <Field {...field('organization.corporateName')}>
              <Input {...register('organization.corporateName')} />
            </Field>
            <Field {...field('organization.form')} optional={t('common.optional')}>
              <Input {...register('organization.form')} />
            </Field>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">{t('account.security')}</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-5 sm:grid-cols-2">
          <Field {...field('userName')}>
            <Input autoComplete="username" {...register('userName')} />
          </Field>
          <Field {...field('password')} hint={errors.password ? undefined : t('account.fields.passwordHint')}>
            <Input type="password" autoComplete="new-password" {...register('password')} />
          </Field>
        </CardContent>
      </Card>

      <div className="sticky bottom-0 -mx-4 flex justify-end gap-2 border-t bg-background/80 px-4 py-4 backdrop-blur md:mx-0 md:rounded-xl md:border">
        <Button
          type="button"
          variant="ghost"
          disabled={!isDirty}
          onClick={() => reset(toFormValues(account))}
        >
          {t('common.cancel')}
        </Button>
        <Button type="submit" disabled={isSubmitting || !isDirty}>
          {isSubmitting ? t('common.saving') : t('common.save')}
        </Button>
      </div>
    </form>
  )
}
