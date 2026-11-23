import type { TFunction } from 'i18next'
import { useId, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Input } from '@/shared/ui/input'
import { isTheTag, matches } from '../matching'
import type { SuggestedTags } from '../queries'

type Props = {
  suggested: SuggestedTags
  carried: string[]
  onPick: (typed: string) => void
  /** For a contact search: the automatic tags are proposed too, and no tag is created. */
  searching?: boolean
}

type Group = { label: string; tags: string[] }

/**
 * Finds a tag among the proposed tags, or creates it as typed. The predefined tags come first, by family,
 * then the account's tags. Tags already carried are not proposed. Enter picks the first option. For a contact
 * search, the automatic tags are proposed last, and nothing is created.
 */
export function TagPicker({ suggested, carried, onPick, searching = false }: Props) {
  const { t } = useTranslation()
  const [typed, setTyped] = useState('')
  const listId = useId()
  const groups = groupsOf(suggested, searching, t)
    .map((group) => ({ ...group, tags: group.tags.filter((tag) => isOffered(tag, carried, typed)) }))
    .filter((group) => group.tags.length > 0)
  const toCreate = !searching && typed.trim() !== '' && !proposes(suggested, typed) ? typed.trim() : null
  const first = groups[0]?.tags[0] ?? toCreate

  const pick = (tag: string) => {
    onPick(tag)
    setTyped('')
  }

  return (
    <div className="space-y-2">
      <Input
        autoFocus
        role="combobox"
        aria-label={t('tags.tag')}
        aria-controls={listId}
        aria-expanded
        placeholder={t(searching ? 'tags.filterPlaceholder' : 'tags.searchPlaceholder')}
        value={typed}
        onChange={(event) => setTyped(event.target.value)}
        onKeyDown={(event) => {
          if (event.key === 'Enter' && first) {
            event.preventDefault()
            pick(first)
          }
        }}
      />
      <div
        id={listId}
        role="listbox"
        aria-label={t('tags.proposed')}
        className="max-h-64 space-y-2 overflow-y-auto"
      >
        {groups.map((group) => (
          <fieldset key={group.label}>
            <legend className="px-1 text-xs font-medium text-muted-foreground">{group.label}</legend>
            <div className="flex flex-wrap gap-1 pt-1">
              {group.tags.map((tag) => (
                <TagOption key={tag} label={tag} onPick={() => pick(tag)} />
              ))}
            </div>
          </fieldset>
        ))}
        {toCreate && <TagOption label={t('tags.create', { tag: toCreate })} onPick={() => pick(toCreate)} />}
      </div>
    </div>
  )
}

function TagOption({ label, onPick }: { label: string; onPick: () => void }) {
  return (
    <button
      type="button"
      role="option"
      aria-selected={false}
      className="rounded-full border px-2 py-0.5 text-xs font-medium hover:bg-accent hover:text-accent-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
      onClick={onPick}
    >
      {label}
    </button>
  )
}

const isOffered = (tag: string, carried: string[], typed: string) =>
  !carried.includes(tag) && matches(tag, typed)

const proposes = (suggested: SuggestedTags, typed: string) =>
  [...suggested.families.flatMap((family) => family.tags), ...suggested.own].some((tag) =>
    isTheTag(tag, typed),
  )

function groupsOf(suggested: SuggestedTags, searching: boolean, t: TFunction): Group[] {
  return [
    ...suggested.families.map((family) => ({
      label: t(`tags.families.${family.family}`),
      tags: family.tags,
    })),
    { label: t('tags.own'), tags: suggested.own },
    ...(searching ? [{ label: t('tags.automaticOnes'), tags: suggested.automatic }] : []),
  ]
}
