import { describe, expect, it } from 'vitest'
import { meansOf, openingOf } from './reach'

const herMaltProfile = {
  source: 'malt',
  identifier: 'adalovelace',
  address: 'https://www.malt.fr/profile/adalovelace',
}
const ada = {
  email: 'ada@acme.test',
  phoneNumber: '+33 6 12 34 56 78',
  language: null,
  links: [
    herMaltProfile,
    {
      source: 'linkedInProfile',
      identifier: 'ada-lovelace',
      address: 'https://www.linkedin.com/in/ada-lovelace',
    },
  ],
}
const message = { text: 'Bonjour Ada,\nune question & une autre', subject: 'Ada, une question' }

describe('how a recipient is reached by the channel of the campaign', () => {
  it('is their e-mail, their phone or their LinkedIn profile', () => {
    expect(meansOf('email', ada)).toBe('ada@acme.test')
    expect(meansOf('call', ada)).toBe('+33 6 12 34 56 78')
    expect(meansOf('sms', ada)).toBe('+33 6 12 34 56 78')
    expect(meansOf('linkedIn', ada)).toBe('https://www.linkedin.com/in/ada-lovelace')
    expect(meansOf('email', { ...ada, email: null })).toBeNull()
  })

  it('is no other link than their LinkedIn profile for LinkedIn', () => {
    expect(meansOf('linkedIn', { ...ada, links: [herMaltProfile] })).toBeNull()
  })

  it('opens the mail with its subject and its message, the SMS with its message, the call, or the profile', () => {
    expect(openingOf('email', ada, message)).toEqual({
      href: 'mailto:ada@acme.test?subject=Ada%2C%20une%20question&body=Bonjour%20Ada%2C%0Aune%20question%20%26%20une%20autre',
      external: false,
    })
    expect(openingOf('sms', ada, message)).toEqual({
      href: 'sms:+33612345678?body=Bonjour%20Ada%2C%0Aune%20question%20%26%20une%20autre',
      external: false,
    })
    expect(openingOf('call', ada, message)).toEqual({ href: 'tel:+33612345678', external: false })
    expect(openingOf('linkedIn', ada, message)).toEqual({
      href: 'https://www.linkedin.com/in/ada-lovelace',
      external: true,
    })
  })

  it('opens nothing without the means of the channel', () => {
    expect(openingOf('call', { ...ada, phoneNumber: null }, message)).toBeNull()
  })

  it('opens the mail without a subject when the message has none', () => {
    expect(openingOf('email', ada, { text: 'Bonjour', subject: null })?.href).toBe(
      'mailto:ada@acme.test?body=Bonjour',
    )
  })
})
