import { useQuery } from '@tanstack/react-query'
import { ListFilter, X } from 'lucide-react'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Badge } from '@/shared/ui/badge'
import { Button } from '@/shared/ui/button'
import { Popover, PopoverContent, PopoverTrigger } from '@/shared/ui/popover'
import { suggestedTagsQuery } from '../queries'
import { TagPicker } from './tag-picker'

type Props = {
  chosen: string[]
  onChange: (tags: string[]) => void
}

/** The most tags a search takes at once (the api refuses more). */
const MAX_CHOSEN = 5

/**
 * The tags the contacts must all carry: a chip per tag chosen, each taken off by its cross, and « Filtrer par tag »
 * opening the picker of every tag of the account, the automatic ones included.
 */
export function TagsFilter({ chosen, onChange }: Props) {
  const { t } = useTranslation()
  const [open, setOpen] = useState(false)
  const suggested = useQuery({ ...suggestedTagsQuery(), enabled: open })

  return (
    <div className="flex flex-wrap items-center gap-1.5">
      {chosen.length > 0 && (
        <ul aria-label={t('tags.chosen')} className="flex flex-wrap items-center gap-1.5">
          {chosen.map((tag) => (
            <li key={tag}>
              <Badge variant="secondary" className="gap-0.5 pr-1">
                {tag}
                <button
                  type="button"
                  aria-label={t('tags.takeOffFilter', { tag })}
                  className="rounded-full p-0.5 hover:bg-background/60 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                  onClick={() => onChange(chosen.filter((other) => other !== tag))}
                >
                  <X className="size-3" aria-hidden />
                </button>
              </Badge>
            </li>
          ))}
        </ul>
      )}
      {chosen.length < MAX_CHOSEN && (
        <Popover open={open} onOpenChange={setOpen}>
          <PopoverTrigger asChild>
            <Button type="button" variant="outline" size="sm">
              <ListFilter aria-hidden />
              {t('tags.filter')}
            </Button>
          </PopoverTrigger>
          <PopoverContent align="start" className="w-80">
            {suggested.data && (
              <TagPicker
                searching
                suggested={suggested.data}
                carried={chosen}
                onPick={(tag) => {
                  setOpen(false)
                  onChange([...chosen, tag])
                }}
              />
            )}
          </PopoverContent>
        </Popover>
      )}
    </div>
  )
}
