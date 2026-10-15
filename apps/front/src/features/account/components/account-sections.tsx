import { useTranslation } from 'react-i18next'
import { useAuth } from 'react-oidc-context'
import { toast } from 'sonner'
import { z } from 'zod'
import {
  ContactDetailsFields,
  contactDetailsFields,
  contactDetailsSchema,
  toContactDetails,
  toContactDetailsValues,
} from '@/shared/forms/contact-details'
import { FormField } from '@/shared/forms/form-field'
import {
  LegalIdentityFields,
  legalIdentityFields,
  legalIdentitySchema,
  toLegalIdentity,
  toLegalIdentityValues,
} from '@/shared/forms/legal-identity'
import { SectionForm } from '@/shared/forms/section-form'
import { orNull, text } from '@/shared/forms/text'
import { i18n } from '@/shared/i18n'
import { countryName } from '@/shared/lib/countries'
import { initials } from '@/shared/lib/format'
import { Card, CardContent, CardHeader, CardTitle } from '@/shared/ui/card'
import { Detail } from '@/shared/ui/detail'
import { EditableSection } from '@/shared/ui/editable-section'
import { ImageUpload } from '@/shared/ui/form/image-upload'
import { Input } from '@/shared/ui/input'
import {
  type Account,
  useChangeMyContactDetails,
  useChangeMyName,
  useChangeMyOrganizationLogo,
  useChangeMyPassword,
  useChangeMyPicture,
  useChangeMyUserName,
  useReviseMyOrganizationIdentity,
} from '../mutations'

const nameSchema = z.object({ firstName: text(100), lastName: text(100) })
const userNameSchema = z.object({ userName: text(50).min(1, { error: () => i18n.t('validation.required') }) })
const passwordSchema = z.object({
  password: z
    .string()
    .min(8, { error: () => i18n.t('validation.min', { min: 8 }) })
    .max(4096),
})

export const fullNameOf = (account: Account) =>
  [account.name.firstName, account.name.lastName].filter(Boolean).join(' ') || account.userName

export function PictureSection({ account }: { account: Account }) {
  const { t } = useTranslation()
  const change = useChangeMyPicture()

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">{t('account.sections.picture')}</CardTitle>
      </CardHeader>
      <CardContent>
        <ImageUpload
          shape="circle"
          imageUrl={account.picture?.mediumUrl}
          alt={fullNameOf(account)}
          label={t('account.changePicture')}
          pending={change.isPending}
          onUpload={account._links.changeMyPicture ? (file) => change.mutate(file) : undefined}
          fallback={
            <span className="font-semibold text-2xl text-muted-foreground">
              {initials(fullNameOf(account))}
            </span>
          }
        />
      </CardContent>
    </Card>
  )
}

export function NameSection({ account }: { account: Account }) {
  const { t } = useTranslation()
  const change = useChangeMyName()

  return (
    <EditableSection
      title={t('account.sections.name')}
      editable={Boolean(account._links.changeMyName)}
      editor={(close) => (
        <SectionForm
          schema={nameSchema}
          defaultValues={{ firstName: account.name.firstName ?? '', lastName: account.name.lastName ?? '' }}
          fields={['firstName', 'lastName']}
          save={(values) =>
            change.mutateAsync({
              body: { firstName: orNull(values.firstName), lastName: orNull(values.lastName) },
            })
          }
          onDone={close}
        >
          {(form) => (
            <>
              <FormField form={form} name={'firstName'} label={t('fields.firstName')}>
                <Input autoComplete="given-name" {...form.register('firstName')} />
              </FormField>
              <FormField form={form} name={'lastName'} label={t('fields.lastName')}>
                <Input autoComplete="family-name" {...form.register('lastName')} />
              </FormField>
            </>
          )}
        </SectionForm>
      )}
    >
      <Detail label={t('fields.firstName')}>{account.name.firstName}</Detail>
      <Detail label={t('fields.lastName')}>{account.name.lastName}</Detail>
    </EditableSection>
  )
}

export function UserNameSection({ account }: { account: Account }) {
  const { t } = useTranslation()
  const auth = useAuth()
  const change = useChangeMyUserName()

  const signInAgainSinceTheTokenIsBoundToTheUserName = async () => {
    toast.info(t('account.userNameChanged'))
    await auth.removeUser()
    await auth.signinRedirect()
  }

  return (
    <EditableSection
      title={t('account.sections.userName')}
      editable={Boolean(account._links.changeMyUserName)}
      editor={(close) => (
        <SectionForm
          schema={userNameSchema}
          defaultValues={{ userName: account.userName }}
          fields={['userName']}
          save={async (values) => {
            const changed = await change.mutateAsync({ body: values })
            if (changed.userName !== account.userName) {
              await signInAgainSinceTheTokenIsBoundToTheUserName()
            }
          }}
          onDone={close}
        >
          {(form) => (
            <FormField form={form} name={'userName'} label={t('fields.userName')}>
              <Input autoComplete="username" {...form.register('userName')} />
            </FormField>
          )}
        </SectionForm>
      )}
    >
      <Detail label={t('fields.userName')}>{account.userName}</Detail>
    </EditableSection>
  )
}

export function ContactDetailsSection({ account }: { account: Account }) {
  const { t } = useTranslation()
  const change = useChangeMyContactDetails()
  const contact = account.contactDetails

  return (
    <EditableSection
      title={t('account.sections.contactDetails')}
      editable={Boolean(account._links.changeMyContactDetails)}
      editor={(close) => (
        <SectionForm
          schema={contactDetailsSchema}
          defaultValues={toContactDetailsValues(contact)}
          fields={contactDetailsFields}
          save={(values) => change.mutateAsync({ body: toContactDetails(values) })}
          onDone={close}
        >
          {(form) => <ContactDetailsFields form={form} />}
        </SectionForm>
      )}
    >
      <Detail label={t('fields.email')}>{contact.email}</Detail>
      <Detail label={t('fields.phoneNumber')}>{contact.phoneNumber}</Detail>
      <Detail label={t('fields.language')}>{countryName(contact.language)}</Detail>
    </EditableSection>
  )
}

export function PasswordSection({ account }: { account: Account }) {
  const { t } = useTranslation()
  const change = useChangeMyPassword()

  return (
    <EditableSection
      title={t('account.sections.password')}
      editable={Boolean(account._links.changeMyPassword)}
      editor={(close) => (
        <SectionForm
          schema={passwordSchema}
          defaultValues={{ password: '' }}
          fields={['password']}
          save={(values) => change.mutateAsync({ body: values })}
          onDone={close}
        >
          {(form) => (
            <FormField form={form} name={'password'} label={t('fields.password')}>
              <Input type="password" autoComplete="new-password" {...form.register('password')} />
            </FormField>
          )}
        </SectionForm>
      )}
    >
      <p className="text-muted-foreground">••••••••</p>
    </EditableSection>
  )
}

export function MyOrganizationIdentitySection({ account }: { account: Account }) {
  const { t } = useTranslation()
  const revise = useReviseMyOrganizationIdentity()
  const identity = account.organization.legalIdentity

  return (
    <EditableSection
      title={t('account.sections.organizationIdentity')}
      editable={Boolean(account._links.reviseMyOrganizationIdentity)}
      editor={(close) => (
        <SectionForm
          schema={legalIdentitySchema}
          defaultValues={toLegalIdentityValues(identity)}
          fields={legalIdentityFields}
          save={(values) => revise.mutateAsync({ body: toLegalIdentity(values) })}
          onDone={close}
        >
          {(form) => <LegalIdentityFields form={form} withType={false} />}
        </SectionForm>
      )}
    >
      <Detail label={t('fields.corporateName')}>{identity.corporateName}</Detail>
      <Detail label={t('fields.form')}>{identity.form}</Detail>
    </EditableSection>
  )
}

export function MyOrganizationLogoSection({ account }: { account: Account }) {
  const { t } = useTranslation()
  const change = useChangeMyOrganizationLogo()
  const name = account.organization.legalIdentity.corporateName ?? ''

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">{t('account.sections.organizationLogo')}</CardTitle>
      </CardHeader>
      <CardContent>
        <ImageUpload
          imageUrl={account.organization.logo?.mediumUrl}
          alt={name}
          label={t('account.changeLogo')}
          pending={change.isPending}
          onUpload={account._links.changeMyOrganizationLogo ? (file) => change.mutate(file) : undefined}
          fallback={<span className="font-semibold text-2xl text-muted-foreground">{initials(name)}</span>}
        />
      </CardContent>
    </Card>
  )
}
