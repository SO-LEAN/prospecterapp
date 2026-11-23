import type { ClipboardEvent } from 'react'

const WEB_SCHEMES = ['http:', 'https:']

/** Where people join Meet, Teams and Zoom calls: their domain or one of its subdomains. */
const VIDEO_CALL_DOMAINS = ['meet.google.com', 'teams.microsoft.com', 'teams.live.com', 'zoom.us']

/** A web address in a text. It ends at a space or at a character that usually closes it in an invitation. */
const WEB_ADDRESS = /https?:\/\/[^\s<>"'()[\]]+/g

/** Whether a link opens in a browser. The core refuses any other link. */
export function isAWebAddress(link: string): boolean {
  return URL.canParse(link) && WEB_SCHEMES.includes(new URL(link).protocol)
}

const isAVideoCall = (link: string) => {
  const host = new URL(link).hostname
  return VIDEO_CALL_DOMAINS.some((domain) => host === domain || host.endsWith(`.${domain}`))
}

/** The first Meet, Teams or Zoom link in a whole pasted invitation, or none when it holds none. */
export function videoCallLinkIn(invitation: string): string | null {
  return (
    (invitation.match(WEB_ADDRESS) ?? []).find((link) => isAWebAddress(link) && isAVideoCall(link)) ?? null
  )
}

/** Pasting a whole invitation pastes its Meet, Teams or Zoom link instead. Any other text is pasted as it is. */
export const takingTheLinkOfAnInvitation =
  (setLink: (link: string) => void) => (event: ClipboardEvent<HTMLInputElement>) => {
    const link = videoCallLinkIn(event.clipboardData.getData('text'))
    if (link) {
      event.preventDefault()
      setLink(link)
    }
  }
