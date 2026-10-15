import { z } from 'zod'
import { addressSchema, toAddress, toAddressValues } from '@/shared/forms/address'
import {
  contactDetailsSchema,
  toContactDetails,
  toContactDetailsValues,
} from '@/shared/forms/contact-details'
import { legalIdentitySchema, toLegalIdentity, toLegalIdentityValues } from '@/shared/forms/legal-identity'
import { orNull, text } from '@/shared/forms/text'
import { i18n } from '@/shared/i18n'
import type { StartProspectingInput } from './queries'

/** What is known when prospecting starts; the same rule as the API: a corporate name or an email. */
export const startProspectingSchema = z
  .object({
    legalIdentity: legalIdentitySchema,
    contactDetails: contactDetailsSchema,
    address: addressSchema,
    observations: text(255),
  })
  .refine((values) => values.legalIdentity.corporateName !== '' || values.contactDetails.email !== '', {
    path: ['legalIdentity', 'corporateName'],
    error: () => i18n.t('organizations.validation.nameOrEmail'),
  })

export type StartProspectingValues = z.infer<typeof startProspectingSchema>

/** Field paths, as the API reports them in its violations. */
export const startProspectingFields = [
  'legalIdentity.corporateName',
  'legalIdentity.form',
  'legalIdentity.type',
  'contactDetails.email',
  'contactDetails.phoneNumber',
  'contactDetails.language',
  'address.street',
  'address.postalCode',
  'address.city',
  'address.country',
  'observations',
] as const

export type StartProspectingField = (typeof startProspectingFields)[number]

export const emptyStartProspecting = (): StartProspectingValues => ({
  legalIdentity: toLegalIdentityValues({ corporateName: null, form: null, type: null }),
  contactDetails: toContactDetailsValues({ email: null, phoneNumber: null, language: null }),
  address: toAddressValues(null),
  observations: '',
})

export const toStartProspectingInput = (values: StartProspectingValues): StartProspectingInput => ({
  legalIdentity: toLegalIdentity(values.legalIdentity),
  contactDetails: toContactDetails(values.contactDetails),
  address: toAddress(values.address),
  observations: orNull(values.observations),
  holdingId: null,
})
