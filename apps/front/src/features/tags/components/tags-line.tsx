import { useQuery } from '@tanstack/react-query'
import { Link } from '@tanstack/react-router'
import { Plus, X } from 'lucide-react'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { searchOfTags } from '@/shared/api/tagged-contacts'
import { Badge } from '@/shared/ui/badge'
import { Button } from '@/shared/ui/button'
import { Popover, PopoverContent, PopoverTrigger } from '@/shared/ui/popover'
import { suggestedTagsQuery } from '../queries'
import { ExplainedTag } from './explained-tag'
import { TagPicker } from './tag-picker'

type Props = {
  tags: string[]
  inherited?: { name: string; from: string[] }[]
  automatic?: string[]
  /** Where the automatic tags come from, said on each: automatic, unless told otherwise. */
  automaticFrom?: string
  onTag: (typed: string) => void
  onTakeOff: (tag: string) => void
}

/**
 * A line of chips, one per tag put by hand, each taken off by its cross, then those a contact inherits from the
 * organizations named, and the automatic ones, neither taken off (ADR 0141, 0142), and « Ajouter un tag » opening the
 * picker of the tags proposed to the account. The predefined tags and those of the account look alike (ADR 0138).
 */
export function TagsLine({ tags, inherited = [], automatic = [], automaticFrom, onTag, onTakeOff }: Props) {
  const { t } = useTranslation()

  return (
    <ul aria-label={t('tags.title')} className="flex flex-wrap items-center gap-1.5">
      {tags.map((tag) => (
        <li key={tag}>
          <Badge variant="secondary" className="gap-0.5 pr-1">
            <Link to="/contacts" search={{ tags: searchOfTags([tag]) }} className="hover:underline">
              {tag}
            </Link>
            <button
              type="button"
              aria-label={t('tags.takeOff', { tag })}
              className="rounded-full p-0.5 hover:bg-background/60 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
              onClick={() => onTakeOff(tag)}
            >
              <X className="size-3" aria-hidden />
            </button>
          </Badge>
        </li>
      ))}
      {inherited.map((tag) => (
        <li key={tag.name}>
          <ExplainedTag
            tag={tag.name}
            explanation={
              tag.from.length > 0
                ? t('tags.inheritedFrom', { organizations: tag.from.join(', ') })
                : t('tags.inheritedFromAnOrganization')
            }
          />
        </li>
      ))}
      {automatic.map((tag) => (
        <li key={tag}>
          <ExplainedTag tag={tag} explanation={automaticFrom ?? t('tags.automatic')} automatic />
        </li>
      ))}
      <li>
        <AddATag carried={tags} onPick={onTag} />
      </li>
    </ul>
  )
}

function AddATag({ carried, onPick }: { carried: string[]; onPick: (typed: string) => void }) {
  const { t } = useTranslation()
  const [open, setOpen] = useState(false)
  const suggested = useQuery({ ...suggestedTagsQuery(), enabled: open })

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button type="button" variant="ghost" size="sm" className="h-6 px-2 text-xs">
          <Plus aria-hidden />
          {t('tags.add')}
        </Button>
      </PopoverTrigger>
      <PopoverContent align="start" className="w-80">
        {suggested.data && (
          <TagPicker
            suggested={suggested.data}
            carried={carried}
            onPick={(typed) => {
              onPick(typed)
              setOpen(false)
            }}
          />
        )}
      </PopoverContent>
    </Popover>
  )
}
