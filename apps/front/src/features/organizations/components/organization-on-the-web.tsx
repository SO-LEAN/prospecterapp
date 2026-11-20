import { Globe } from 'lucide-react'
import type { ReactNode } from 'react'

const SCHEME = /^https?:\/\//

function OpenedInANewTab({ href, icon, children }: { href: string; icon: ReactNode; children: string }) {
  return (
    <a
      href={href}
      target="_blank"
      rel="noopener noreferrer"
      className="flex w-fit max-w-full items-center gap-1.5 text-primary hover:underline"
    >
      {icon}
      <span className="truncate">{children}</span>
    </a>
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
