import { describe, expect, it } from 'vitest'
import { isAWebAddress, videoCallLinkIn } from './video-call-link'

const MEET = 'https://meet.google.com/abc-defg-hij'

describe('videoCallLinkIn', () => {
  it('finds the Meet link of an invitation pasted whole', () => {
    const invitation = `Démonstration Prospecter\nMardi 17 novembre · 14:00 – 14:30\nLien de la visio : ${MEET}\nOu appelez le : (FR) +33 1 23 45 67 89`

    expect(videoCallLinkIn(invitation)).toBe(MEET)
  })

  it('skips the other links for the first Teams link', () => {
    const invitation =
      'Agenda : https://example.com/agenda.\nRejoindre <https://teams.microsoft.com/l/meetup-join/19%3ameeting_abc> ici'

    expect(videoCallLinkIn(invitation)).toBe('https://teams.microsoft.com/l/meetup-join/19%3ameeting_abc')
  })

  it('finds a Zoom link on a subdomain', () => {
    expect(videoCallLinkIn('Zoom (https://us02web.zoom.us/j/123456?pwd=xyz)')).toBe(
      'https://us02web.zoom.us/j/123456?pwd=xyz',
    )
  })

  it('finds none in a text without Meet, Teams nor Zoom', () => {
    expect(videoCallLinkIn('réunion de mardi, voir https://example.com')).toBeNull()
  })

  it('does not take a domain ending like one of theirs', () => {
    expect(videoCallLinkIn('https://notzoom.us/j/1')).toBeNull()
  })
})

describe('isAWebAddress', () => {
  it('accepts a web address', () => {
    expect(isAWebAddress(MEET)).toBe(true)
  })

  it.each(['réunion de mardi', 'meet.google.com/abc', 'ftp://example.com', 'javascript:alert(1)'])(
    'refuses %s',
    (link) => {
      expect(isAWebAddress(link)).toBe(false)
    },
  )
})
