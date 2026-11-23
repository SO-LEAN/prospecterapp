import { Mars, Venus } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import type { components } from '@/shared/api/schema'
import { Tooltip, TooltipContent, TooltipTrigger } from './tooltip'

type Civility = components['schemas']['ContactResource']['title']

const MARKS = { ms: Venus, mr: Mars } as const

/** A discreet mark beside a contact's name. It says Madame or Monsieur on hover and to a screen reader. Nothing shows when the title is unknown. */
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
