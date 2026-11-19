import { Globe } from 'lucide-react'
import type { ReactNode } from 'react'
import { LinkedInLogo } from '@/shared/ui/linkedin-profile'

const SCHEME_AND_WWW = /^https:\/\/www\./
const SCHEME = /^https?:\/\//

function OpenedInANewTab({ href, icon, children }: { href: string; icon: ReactNode; children: string }) {
  return (
    <a
      href={href}
      target="_blank"
      rel="noopener noreferrer"
      className="inline-flex min-w-0 items-center gap-1.5 text-primary hover:underline"
    >
      {icon}
      <span className="truncate">{children}</span>
    </a>
  )
}

/** The page of the company on LinkedIn, opened in a new tab in a click. */
export function LinkedInPage({ page }: { page: string }) {
  return (
    <OpenedInANewTab href={page} icon={<LinkedInLogo className="size-4 shrink-0" />}>
      {page.replace(SCHEME_AND_WWW, '')}
    </OpenedInANewTab>
  )
}

/** The website of the organization, opened in a new tab in a click. */
export function Website({ site }: { site: string }) {
  return (
    <OpenedInANewTab href={site} icon={<Globe className="size-4 shrink-0" aria-hidden />}>
      {site.replace(SCHEME, '')}
    </OpenedInANewTab>
  )
}
