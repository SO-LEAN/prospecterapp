import type { ClipboardEvent } from 'react'

const WEB_SCHEMES = ['http:', 'https:']

/** Where Meet, Teams and Zoom are joined: their domain or one of its subdomains. */
const VIDEO_CALL_DOMAINS = ['meet.google.com', 'teams.microsoft.com', 'teams.live.com', 'zoom.us']

/** A web address in a text, up to a space or what usually closes it in an invitation. */
const WEB_ADDRESS = /https?:\/\/[^\s<>"'()[\]]+/g

/** Whether a link opens in a browser: the core refuses any other. */
export function isAWebAddress(link: string): boolean {
  return URL.canParse(link) && WEB_SCHEMES.includes(new URL(link).protocol)
}

const isAVideoCall = (link: string) => {
  const host = new URL(link).hostname
  return VIDEO_CALL_DOMAINS.some((domain) => host === domain || host.endsWith(`.${domain}`))
}

/** The first Meet, Teams or Zoom link of an invitation pasted whole; none when it holds none. */
export function videoCallLinkIn(invitation: string): string | null {
  return (
    (invitation.match(WEB_ADDRESS) ?? []).find((link) => isAWebAddress(link) && isAVideoCall(link)) ?? null
  )
}

/** Pasting an invitation whole sets its Meet, Teams or Zoom link in its place; any other text is pasted as it is. */
export const takingTheLinkOfAnInvitation =
  (setLink: (link: string) => void) => (event: ClipboardEvent<HTMLInputElement>) => {
    const link = videoCallLinkIn(event.clipboardData.getData('text'))
    if (link) {
      event.preventDefault()
      setLink(link)
    }
  }
