import { cloneElement, type ReactElement, useId } from 'react'
import { cn } from '@/shared/lib/utils'
import { Label } from '@/shared/ui/label'

type Props = {
  label: string
  error?: string
  hint?: string
  optional?: string
  className?: string
  children: ReactElement<{ id?: string; 'aria-invalid'?: boolean; 'aria-describedby'?: string }>
}

/**
 * Label, control, hint and error message, wired for screen readers. Its rows stay packed at its top when a
 * grid row stretches it to the height of a taller field beside it.
 */
export function Field({ label, error, hint, optional, className, children }: Props) {
  const id = useId()
  const descriptionId = `${id}-description`

  return (
    <div className={cn('grid content-start gap-2', className)}>
      <Label htmlFor={id} className="flex items-baseline gap-1.5">
        {label}
        {optional && (
          <span className="font-normal text-muted-foreground text-xs leading-none">({optional})</span>
        )}
      </Label>
      {cloneElement(children, {
        id,
        'aria-invalid': error ? true : undefined,
        'aria-describedby': error || hint ? descriptionId : undefined,
      })}
      {(error || hint) && (
        <p id={descriptionId} className={cn('text-xs', error ? 'text-destructive' : 'text-muted-foreground')}>
          {error ?? hint}
        </p>
      )}
    </div>
  )
}
