import { Loader2 } from 'lucide-react'
import type { ReactNode } from 'react'

type Props = {
  title: string
  description?: string
  loading?: boolean
  children?: ReactNode
}

/** Centered message for states without the app shell: loading, fatal errors. */
export function FullPageMessage({ title, description, loading = false, children }: Props) {
  return (
    <main className="grid min-h-dvh place-items-center bg-background p-6">
      <div
        className="flex max-w-sm flex-col items-center gap-4 text-center"
        role={loading ? 'status' : undefined}
      >
        {loading && <Loader2 className="size-6 animate-spin text-primary" aria-hidden />}
        <div className="space-y-1">
          <h1 className="font-semibold text-lg tracking-tight">{title}</h1>
          {description && <p className="text-muted-foreground text-sm">{description}</p>}
        </div>
        {children}
      </div>
    </main>
  )
}
