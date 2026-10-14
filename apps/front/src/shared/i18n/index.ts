import i18next from 'i18next'
import { initReactI18next } from 'react-i18next'
import { fr } from './fr'

export const resources = { fr: { translation: fr } } as const

void i18next.use(initReactI18next).init({
  lng: 'fr',
  fallbackLng: 'fr',
  resources,
  interpolation: { escapeValue: false },
})

export const i18n = i18next

/** Country names in the user's language, for country code fields. */
export const countryNames = new Intl.DisplayNames(['fr'], { type: 'region' })

declare module 'i18next' {
  interface CustomTypeOptions {
    defaultNS: 'translation'
    resources: (typeof resources)['fr']
  }
}
