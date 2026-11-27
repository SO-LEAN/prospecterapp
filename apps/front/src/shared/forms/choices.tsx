import type { LucideIcon } from 'lucide-react'
import type { ReactNode } from 'react'
import type { UseFormRegisterReturn } from 'react-hook-form'

/** A group of choices under its legend, with the error of the field they fill. */
export function Choices({
  legend,
  error,
  children,
}: {
  legend: string
  error?: string
  children: ReactNode
}) {
  return (
    <fieldset className="space-y-2" aria-invalid={error ? true : undefined}>
      <legend className="font-medium text-sm">{legend}</legend>
      <div className="flex flex-wrap gap-2">{children}</div>
      {error && <p className="text-destructive text-xs">{error}</p>}
    </fieldset>
  )
}

type ChoiceProps = UseFormRegisterReturn & { icon: LucideIcon; label: string; value: string }

/** A radio button that looks like a button. The input lies transparent over its label, and it receives the click and the reading. */
export function Choice({ icon: Icon, label, ...radio }: ChoiceProps) {
  return (
    <label className="relative inline-flex cursor-pointer items-center gap-1.5 rounded-md border px-3 py-1.5 text-sm transition-colors hover:bg-accent has-checked:border-primary has-checked:bg-primary has-checked:text-primary-foreground has-focus-visible:ring-[3px] has-focus-visible:ring-ring/50">
      <input type="radio" className="absolute inset-0 cursor-pointer appearance-none opacity-0" {...radio} />
      <Icon aria-hidden className="size-4" />
      {label}
    </label>
  )
}
