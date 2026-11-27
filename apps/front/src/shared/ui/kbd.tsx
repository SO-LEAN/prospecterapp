import type * as React from 'react'
import { cn } from '@/shared/lib/utils'

/**
 * The key of a shortcut, shown in a button (« Noter un échange L »). Screen readers skip it: the button names its
 * shortcut with `aria-keyshortcuts`.
 */
function Kbd({ className, ...props }: React.ComponentProps<'kbd'>) {
  return (
    <kbd
      data-slot="kbd"
      aria-hidden
      className={cn(
        'pointer-events-none inline-flex h-5 min-w-5 items-center justify-center rounded-chip border border-current/25 px-1 font-sans text-label opacity-75',
        className,
      )}
      {...props}
    />
  )
}

export { Kbd }
