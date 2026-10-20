import { MapPinOff } from 'lucide-react'
import { type ComponentProps, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { cn } from '@/shared/lib/utils'
import { Badge } from './badge'
import { Tooltip, TooltipContent, TooltipTrigger } from './tooltip'

/** Says an address was entered but the geocoder found no place for it: it is missing from the maps. */
export function UnlocatedBadge({ className, ...props }: ComponentProps<typeof Badge>) {
  const { t } = useTranslation()

  return (
    <Badge variant="outline" className={cn('border-warning bg-warning/15', className)} {...props}>
      <MapPinOff className="text-warning" aria-hidden />
      {t('common.unlocated')}
    </Badge>
  )
}

/** The badge with why it matters, on hover, on focus, and when pressed (a phone has no hover). */
export function ExplainedUnlocatedBadge() {
  const { t } = useTranslation()
  const [open, setOpen] = useState(false)

  return (
    <Tooltip open={open} onOpenChange={setOpen}>
      <TooltipTrigger
        className="rounded-full focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
        onClick={(event) => {
          event.preventDefault()
          setOpen(true)
        }}
      >
        <UnlocatedBadge />
      </TooltipTrigger>
      <TooltipContent className="max-w-64">{t('common.unlocatedExplanation')}</TooltipContent>
    </Tooltip>
  )
}
