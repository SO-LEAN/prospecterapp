import { Building2 } from 'lucide-react'
import { initials } from '@/shared/lib/format'
import { cn } from '@/shared/lib/utils'

type Props = { name: string | null | undefined; logoUrl?: string | null; className?: string }

export function OrganizationAvatar({ name, logoUrl, className }: Props) {
  return (
    <div
      className={cn(
        'grid size-12 shrink-0 place-items-center overflow-hidden rounded-xl border bg-accent text-accent-foreground',
        className,
      )}
    >
      {logoUrl ? (
        <img src={logoUrl} alt="" loading="lazy" className="size-full object-cover" />
      ) : name ? (
        <span className="font-semibold text-sm">{initials(name)}</span>
      ) : (
        <Building2 className="size-5" aria-hidden />
      )}
    </div>
  )
}
