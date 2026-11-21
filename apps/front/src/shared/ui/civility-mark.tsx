import { Mars, Venus } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import type { components } from '@/shared/api/schema'
import { Tooltip, TooltipContent, TooltipTrigger } from './tooltip'

type Civility = components['schemas']['ContactResource']['title']

const MARKS = { ms: Venus, mr: Mars } as const

/** A discreet mark beside the name of a contact, saying Madame or Monsieur on hover and to a reader; nothing when unknown. */
export function CivilityMark({ civility }: { civility: Civility }) {
  const { t } = useTranslation()

  if (civility === 'x') return null

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
