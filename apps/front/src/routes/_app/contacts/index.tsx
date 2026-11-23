import { keepPreviousData, useInfiniteQuery } from '@tanstack/react-query'
import { createFileRoute, useNavigate } from '@tanstack/react-router'
import { zodValidator } from '@tanstack/zod-adapter'
import { Search, SearchX, UsersRound } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'
import { ContactCard } from '@/features/contacts/components/contact-card'
import { type ContactInList, contactSlicesQuery } from '@/features/contacts/queries'
import { TagsFilter } from '@/features/tags/components/tags-filter'
import { chosenTagsIn, searchOfTags, taggedContactSlicesQuery } from '@/shared/api/tagged-contacts'
import { useSearchAsYouType } from '@/shared/hooks/use-search-as-you-type'
import { cn } from '@/shared/lib/utils'
import { Button } from '@/shared/ui/button'
import { EmptyState } from '@/shared/ui/feedback/empty-state'
import { ErrorState } from '@/shared/ui/feedback/error-state'
import { Input } from '@/shared/ui/input'
import { PageHeader } from '@/shared/ui/page-header'
import { PageSkeleton } from '@/shared/ui/page-skeleton'

const searchSchema = z.object({
  q: z.string().catch('').default(''),
  tags: z.string().catch('').default(''),
})

/**
 * The persons my organization prospects, with their organizations, one slice after another. The URL holds the search.
 * The search is by text or by tags, never both at once. The page's links offer the text search only with the right
 * to manage personal data. The tag search finds the contacts that carry all the tags, and counts them. A contact is
 * collected from an organization, never here.
 */
export const Route = createFileRoute('/_app/contacts/')({
  validateSearch: zodValidator(searchSchema),
  loaderDeps: ({ search }) => search,
  loader: ({ context, deps }) =>
    deps.tags
      ? context.queryClient.ensureInfiniteQueryData(taggedContactSlicesQuery(deps.tags))
      : context.queryClient.ensureInfiniteQueryData(contactSlicesQuery(deps.q)),
  pendingComponent: () => <PageSkeleton rows={6} />,
  errorComponent: ({ error, reset }) => <ErrorState error={error} reset={reset} />,
  component: Contacts,
})

function Contacts() {
  const { t } = useTranslation()
  const search = Route.useSearch()
  const navigate = useNavigate({ from: Route.fullPath })
  const chosen = chosenTagsIn(search.tags)
  const list = useContactList(search.q, search.tags)
  const [query, setQuery] = useSearchAsYouType(search.q, (q) => navigate({ search: { q }, replace: true }))

  return (
    <div className="space-y-6">
      <PageHeader title={t('contacts.title')} description={t('contacts.subtitle')} />

      <div className="space-y-3">
        {list.searchesByText && (
          <div className="relative max-w-md">
            <Search
              className="-translate-y-1/2 pointer-events-none absolute top-1/2 left-3 size-4 text-muted-foreground"
              aria-hidden
            />
            <Input
              type="search"
              value={query}
              onChange={(event) => setQuery(event.target.value)}
              placeholder={t('contacts.searchPlaceholder')}
              aria-label={t('contacts.searchPlaceholder')}
              className="pl-9"
            />
          </div>
        )}
        {(list.searchesByTags || chosen.length > 0) && (
          <TagsFilter
            chosen={chosen}
            onChange={(tags) => navigate({ search: tags.length > 0 ? { tags: searchOfTags(tags) } : {} })}
          />
        )}
        {list.found !== undefined && (
          <p className="text-muted-foreground text-sm">{t('contacts.found', { count: list.found })}</p>
        )}
      </div>

      {list.loaded && list.items.length === 0 ? (
        <NoContact query={search.q} tagged={chosen.length > 0} />
      ) : (
        <ul
          className={cn(
            'grid grid-cols-1 gap-3 transition-opacity md:grid-cols-2',
            list.isPlaceholderData && 'opacity-60',
          )}
        >
          {list.items.map((contact) => (
            <li key={contact.id}>
              <ContactCard contact={contact} />
            </li>
          ))}
        </ul>
      )}

      {list.hasNextPage && (
        <div className="flex justify-center">
          <Button
            variant="outline"
            onClick={() => void list.fetchNextPage()}
            disabled={list.isFetchingNextPage}
          >
            {t('common.loadMore')}
          </Button>
        </div>
      )}
    </div>
  )
}

/**
 * The contacts found by text, or, once a tag is chosen, the contacts that carry every chosen tag, with their count.
 * The text search is offered while no tag is chosen, as the full list's links say.
 */
function useContactList(q: string, tags: string) {
  const byText = useInfiniteQuery({
    ...contactSlicesQuery(q),
    placeholderData: keepPreviousData,
    enabled: !tags,
  })
  const byTags = useInfiniteQuery({
    ...taggedContactSlicesQuery(tags),
    placeholderData: keepPreviousData,
    enabled: !!tags,
  })
  const shown = tags ? byTags : byText
  const links = byText.data?.pages[0]?._links
  const items: ContactInList[] = shown.data?.pages.flatMap((slice) => slice.items) ?? []

  return {
    items,
    loaded: shown.data !== undefined,
    found: tags ? byTags.data?.pages[0]?.found : undefined,
    searchesByText: !tags && links?.search !== undefined,
    searchesByTags: links?.searchByTags !== undefined,
    isPlaceholderData: shown.isPlaceholderData,
    hasNextPage: shown.hasNextPage,
    fetchNextPage: shown.fetchNextPage,
    isFetchingNextPage: shown.isFetchingNextPage,
  }
}

function NoContact({ query, tagged }: { query: string; tagged: boolean }) {
  const { t } = useTranslation()

  if (tagged) {
    return (
      <EmptyState
        icon={SearchX}
        title={t('contacts.noneTagged.title')}
        description={t('contacts.noneTagged.description')}
      />
    )
  }
  return query ? (
    <EmptyState
      icon={SearchX}
      title={t('contacts.noResult.title')}
      description={t('contacts.noResult.description', { query })}
    />
  ) : (
    <EmptyState
      icon={UsersRound}
      title={t('contacts.empty.title')}
      description={t('contacts.empty.description')}
    />
  )
}
