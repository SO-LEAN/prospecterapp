import { z } from 'zod'
import { addressSchema, toAddress, toAddressValues } from '@/shared/forms/address'
import {
  legalIdentityFields,
  legalIdentitySchema,
  toLegalIdentity,
  toLegalIdentityValues,
} from '@/shared/forms/legal-identity'
import { orNull, text } from '@/shared/forms/text'
import { i18n } from '@/shared/i18n'
import {
  organizationContactDetailsSchema,
  toOrganizationContactDetails,
  toOrganizationContactDetailsValues,
} from './organization-contact-details'
import type { Organization, ReviseLegalIdentityInput, StartProspectingInput } from './queries'

/** What a member knows when prospecting starts. As in the API, it needs a corporate name or an email. */
export const startProspectingSchema = z
  .object({
    legalIdentity: legalIdentitySchema,
    contactDetails: organizationContactDetailsSchema,
    address: addressSchema,
    observations: text(255),
  })
  .refine((values) => values.legalIdentity.corporateName !== '' || values.contactDetails.email !== '', {
    path: ['legalIdentity', 'corporateName'],
    error: () => i18n.t('organizations.validation.nameOrEmail'),
  })

export type StartProspectingValues = z.infer<typeof startProspectingSchema>

/** The field paths that the API's violations report. */
export const startProspectingFields = [
  'legalIdentity.corporateName',
  'legalIdentity.form',
  'legalIdentity.type',
  'contactDetails.email',
  'contactDetails.phoneNumber',
  'contactDetails.language',
  'contactDetails.links',
  'contactDetails.website',
  'address.street',
  'address.postalCode',
  'address.city',
  'address.country',
  'observations',
] as const

export type StartProspectingField = (typeof startProspectingFields)[number]

export const emptyStartProspecting = (): StartProspectingValues => ({
  legalIdentity: toLegalIdentityValues({ corporateName: null, form: null, type: null }),
  contactDetails: toOrganizationContactDetailsValues({}),
  address: toAddressValues(null),
  observations: '',
})

export const toStartProspectingInput = (values: StartProspectingValues): StartProspectingInput => ({
  legalIdentity: toLegalIdentity(values.legalIdentity),
  contactDetails: toOrganizationContactDetails(values.contactDetails),
  address: toAddress(values.address),
  observations: orNull(values.observations),
  holdingId: null,
})

/** A prospected organization's identity: the shared fields and its establishment's SIRET. The API checks the SIRET. */
export const organizationIdentitySchema = legalIdentitySchema.extend({ siret: text(20) })

export type OrganizationIdentityValues = z.infer<typeof organizationIdentitySchema>

export const organizationIdentityFields = [...legalIdentityFields, 'siret'] as const

export const toOrganizationIdentityValues = (
  identity: Organization['legalIdentity'],
): OrganizationIdentityValues => ({
  ...toLegalIdentityValues(identity),
  siret: identity.siret ?? '',
})

export const toOrganizationIdentity = (values: OrganizationIdentityValues): ReviseLegalIdentityInput => ({
  ...toLegalIdentity(values),
  siret: orNull(values.siret),
})
