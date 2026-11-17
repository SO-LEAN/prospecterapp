import { Link } from '@tanstack/react-router'
import { Sparkles } from 'lucide-react'
import { Badge } from '@/shared/ui/badge'
import { Tooltip, TooltipContent, TooltipTrigger } from '@/shared/ui/tooltip'
import { searchOfTags } from '../chosen-tags'

type Props = {
  tag: string
  explanation: string
  automatic?: boolean
}

/**
 * A tag the member does not take off, saying where it comes from on hover and on focus: inherited from
 * organizations, or set automatically, which looks apart (ADR 0141); pressed, it opens the contacts carrying it.
 */
export function ExplainedTag({ tag, explanation, automatic = false }: Props) {
  return (
    <Tooltip>
      <TooltipTrigger asChild>
        <Link
          to="/contacts"
          search={{ tags: searchOfTags([tag]) }}
          className="rounded-full focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
        >
          {automatic ? (
            <Badge variant="outline" className="border-dashed">
              <Sparkles aria-hidden />
              {tag}
            </Badge>
          ) : (
            <Badge variant="secondary">{tag}</Badge>
          )}
        </Link>
      </TooltipTrigger>
      <TooltipContent className="max-w-64">{explanation}</TooltipContent>
    </Tooltip>
  )
}
