import { Link } from '@tanstack/react-router'
import { Sparkles } from 'lucide-react'
import { searchOfTags } from '@/shared/api/tagged-contacts'
import { Badge } from '@/shared/ui/badge'
import { Tooltip, TooltipContent, TooltipTrigger } from '@/shared/ui/tooltip'

type Props = {
  tag: string
  explanation: string
  automatic?: boolean
}

/**
 * A tag that the member does not take off. On hover and on focus, it says where it comes from: inherited from
 * organizations, or set automatically, which looks different (ADR 0141). Pressing it opens the contacts that
 * carry it.
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
