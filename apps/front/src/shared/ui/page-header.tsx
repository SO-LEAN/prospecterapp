import type { ReactNode } from 'react'

type Props = {
  title: ReactNode
  description?: ReactNode
  actions?: ReactNode
  media?: ReactNode
}

export function PageHeader({ title, description, actions, media }: Props) {
  return (
    <header className="flex flex-wrap items-center justify-between gap-4">
      <div className="flex min-w-0 items-center gap-4">
        {media}
        <div className="min-w-0 space-y-1">
          <h1 className="truncate font-semibold text-2xl tracking-tight">{title}</h1>
          {description && <p className="text-muted-foreground text-sm">{description}</p>}
        </div>
      </div>
      {actions && <div className="flex items-center gap-2">{actions}</div>}
    </header>
  )
}
