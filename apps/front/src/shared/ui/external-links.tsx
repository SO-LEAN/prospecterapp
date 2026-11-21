import { Globe } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import type { components } from '@/shared/api/schema'
import { LinkedInLogo } from './linkedin-logo'

export type ExternalLink = components['schemas']['ExternalLinkResource']

const SCHEME_AND_WWW = /^https?:\/\/(?:www\.)?/i

/** The mark of a tool, by its initials and its colour: no token of ours. */
function Initials({ name, initials, color }: { name: string; initials: string; color: string }) {
  return (
    <svg viewBox="0 0 24 24" className="size-4 shrink-0" role="img" aria-label={name}>
      <rect width="24" height="24" rx="4" fill={color} />
      <text x="12" y="16.5" textAnchor="middle" fontSize="11" fontWeight="700" fill="#FFFFFF">
        {initials}
      </text>
    </svg>
  )
}

/** The mark of the source of a link: the tool it lives on, or the web for one Prospecter does not know. */
function SourceMark({ source }: { source: string }) {
  const { t } = useTranslation()

  switch (source) {
    case 'linkedInProfile':
    case 'linkedInCompany':
      return (
        <span role="img" aria-label={t('links.sources.linkedIn')} className="inline-flex shrink-0">
          <LinkedInLogo className="size-4" />
        </span>
      )
    case 'malt':
      return <Initials name={t('links.sources.malt')} initials="M" color="#FC5757" />
    case 'welcomeToTheJungle':
      return <Initials name={t('links.sources.welcomeToTheJungle')} initials="W" color="#1C1C1C" />
    default:
      return <Globe className="size-4 shrink-0" role="img" aria-label={t('links.web')} />
  }
}

/** The address of a link as read, without its scheme nor `www.`. */
export const shortAddress = (address: string) => address.replace(SCHEME_AND_WWW, '')

/** The links of a person or an organization, each opened in a new tab with the mark of its source; masked, their stars. */
export function ExternalLinks({ links, masked }: { links: ExternalLink[]; masked: boolean }) {
  if (masked) return links.map((link) => link.address).join(' ')

  return (
    <ul className="grid min-w-0 grid-cols-1 gap-1">
      {links.map((link) => (
        <li key={`${link.source}#${link.identifier}`}>
          <a
            href={link.address}
            target="_blank"
            rel="noopener noreferrer"
            className="flex w-fit max-w-full items-center gap-1.5 text-primary hover:underline"
          >
            <SourceMark source={link.source} />
            <span className="truncate">{shortAddress(link.address)}</span>
          </a>
        </li>
      ))}
    </ul>
  )
}
