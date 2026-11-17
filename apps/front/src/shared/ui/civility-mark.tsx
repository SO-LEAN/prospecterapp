import { Mars, Venus } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import type { components } from '@/shared/api/schema'
import { Tooltip, TooltipContent, TooltipTrigger } from './tooltip'

type Civility = NonNullable<components['schemas']['ContactResource']['civility']>

const MARKS = { madame: Venus, monsieur: Mars } as const

/** A discreet mark beside the name of a contact, saying Madame or Monsieur on hover and to a reader; nothing when not noted. */
export function CivilityMark({ civility }: { civility: Civility | null | undefined }) {
  const { t } = useTranslation()

  if (!civility) return null

  const Mark = MARKS[civility]
  const label = t(`civilities.${civility}`)

  return (
    <Tooltip>
      <TooltipTrigger asChild>
        <span role="img" aria-label={label} className="inline-flex shrink-0 text-muted-foreground">
          <Mark aria-hidden className="size-4" />
        </span>
      </TooltipTrigger>
      <TooltipContent>{label}</TooltipContent>
    </Tooltip>
  )
}
