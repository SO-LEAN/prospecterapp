import { Sparkles } from 'lucide-react'
import { useState } from 'react'
import { Badge } from '@/shared/ui/badge'
import { Tooltip, TooltipContent, TooltipTrigger } from '@/shared/ui/tooltip'

type Props = {
  tag: string
  explanation: string
  automatic?: boolean
}

/**
 * A tag the member does not take off, saying where it comes from on hover, on focus, and when pressed (a phone has no
 * hover): inherited from organizations, or set automatically, which looks apart (ADR 0141).
 */
export function ExplainedTag({ tag, explanation, automatic = false }: Props) {
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
        {automatic ? (
          <Badge variant="outline" className="border-dashed">
            <Sparkles aria-hidden />
            {tag}
          </Badge>
        ) : (
          <Badge variant="secondary">{tag}</Badge>
        )}
      </TooltipTrigger>
      <TooltipContent className="max-w-64">{explanation}</TooltipContent>
    </Tooltip>
  )
}
