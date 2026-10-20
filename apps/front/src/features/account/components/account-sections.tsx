import { useSuspenseQuery } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'
import {
  AddressFields,
  addressFields,
  addressSchema,
  toAddress,
  toAddressValues,
} from '@/shared/forms/address'
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
import {
  PersonNameFields,
  personNameFields,
  personNameSchema,
  toPersonName,
  toPersonNameValues,
} from '@/shared/forms/person-name'
import { SectionForm } from '@/shared/forms/section-form'
import { i18n } from '@/shared/i18n'
import { addressLines } from '@/shared/lib/address'
import { countryName } from '@/shared/lib/countries'
import { initials } from '@/shared/lib/format'
import { Card, CardContent, CardHeader, CardTitle } from '@/shared/ui/card'
import { Detail } from '@/shared/ui/detail'
import { EditableSection } from '@/shared/ui/editable-section'
import { ImageUpload } from '@/shared/ui/form/image-upload'
import { Input } from '@/shared/ui/input'
import { ExplainedUnlocatedBadge } from '@/shared/ui/unlocated-badge'
import {
  type Account,
  useChangeMyContactDetails,
  useChangeMyLoginEmail,
  useChangeMyName,
  useChangeMyOrganizationLogo,
  useChangeMyPassword,
  useChangeMyPicture,
  useRelocateMyOrganization,
  useReviseMyOrganizationIdentity,
} from '../mutations'
import { signInQuery } from '../queries'

const loginEmailSchema = z.object({ email: z.email({ error: () => i18n.t('validation.email') }) })
const passwordSchema = z.object({
  password: z
    .string()
    .min(8, { error: () => i18n.t('validation.min', { min: 8 }) })
    .max(4096),
})

export const fullNameOf = (account: Account) =>
  [account.name.firstName, account.name.lastName].filter(Boolean).join(' ') ||
  account.contactDetails.email ||
  ''

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
          schema={personNameSchema}
          defaultValues={toPersonNameValues(account.name)}
          fields={personNameFields}
          save={(values) => change.mutateAsync({ body: toPersonName(values) })}
          onDone={close}
        >
          {(form) => <PersonNameFields form={form} />}
        </SectionForm>
      )}
    >
      <Detail label={t('fields.firstName')}>{account.name.firstName}</Detail>
      <Detail label={t('fields.lastName')}>{account.name.lastName}</Detail>
    </EditableSection>
  )
}

export function LoginEmailSection() {
  const { t } = useTranslation()
  const { data: signIn } = useSuspenseQuery(signInQuery())
  const change = useChangeMyLoginEmail()

  return (
    <EditableSection
      title={t('account.sections.loginEmail')}
      editable={Boolean(signIn._links.changeMyLoginEmail)}
      editor={(close) => (
        <SectionForm
          schema={loginEmailSchema}
          defaultValues={{ email: signIn.loginEmail }}
          fields={['email']}
          save={(values) => change.mutateAsync({ body: values })}
          onDone={close}
        >
          {(form) => (
            <FormField form={form} name="email" label={t('fields.loginEmail')}>
              <Input type="email" autoComplete="username" {...form.register('email')} />
            </FormField>
          )}
        </SectionForm>
      )}
    >
      <Detail label={t('fields.loginEmail')}>{signIn.loginEmail}</Detail>
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

export function PasswordSection() {
  const { t } = useTranslation()
  const { data: signIn } = useSuspenseQuery(signInQuery())
  const change = useChangeMyPassword()

  return (
    <EditableSection
      title={t('account.sections.password')}
      editable={Boolean(signIn._links.changeMyPassword)}
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

export function MyOrganizationAddressSection({ account }: { account: Account }) {
  const { t } = useTranslation()
  const relocate = useRelocateMyOrganization()
  const { address, location } = account.organization
  const lines = addressLines(address)

  return (
    <EditableSection
      title={t('account.sections.organizationAddress')}
      editable={Boolean(account._links.relocateMyOrganization)}
      editor={(close) => (
        <SectionForm
          schema={addressSchema}
          defaultValues={toAddressValues(address)}
          fields={addressFields}
          save={(values) => relocate.mutateAsync({ body: toAddress(values) })}
          onDone={close}
        >
          {(form) => <AddressFields form={form} />}
        </SectionForm>
      )}
    >
      {lines.length > 0 ? (
        <div className="flex flex-wrap items-start justify-between gap-2">
          <address className="not-italic">
            {lines.map((line) => (
              <div key={line}>{line}</div>
            ))}
          </address>
          {!location && <ExplainedUnlocatedBadge />}
        </div>
      ) : (
        <p className="text-muted-foreground">{t('common.notProvided')}</p>
      )}
      <p className="text-muted-foreground text-sm">{t('account.organizationAddressHint')}</p>
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
