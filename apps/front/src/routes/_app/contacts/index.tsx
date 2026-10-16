import { keepPreviousData, useInfiniteQuery } from '@tanstack/react-query'
import { createFileRoute, useNavigate } from '@tanstack/react-router'
import { zodValidator } from '@tanstack/zod-adapter'
import { Search, SearchX, UsersRound } from 'lucide-react'
import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'
import { ContactCard } from '@/features/contacts/components/contact-card'
import { contactSlicesQuery } from '@/features/contacts/queries'
import { useDebouncedValue } from '@/shared/hooks/use-debounced-value'
import { cn } from '@/shared/lib/utils'
import { Button } from '@/shared/ui/button'
import { EmptyState } from '@/shared/ui/feedback/empty-state'
import { ErrorState } from '@/shared/ui/feedback/error-state'
import { Input } from '@/shared/ui/input'
import { PageHeader } from '@/shared/ui/page-header'
import { PageSkeleton } from '@/shared/ui/page-skeleton'

const searchSchema = z.object({
  q: z.string().catch('').default(''),
})

/**
 * The persons my organization prospects, with their organizations, a slice after the other. The search lives in the URL;
 * searching is offered only with the right to manage personal data (the links of the page). A contact is
 * collected from an organization, never here.
 */
export const Route = createFileRoute('/_app/contacts/')({
  validateSearch: zodValidator(searchSchema),
  loaderDeps: ({ search }) => search,
  loader: ({ context, deps }) => context.queryClient.ensureInfiniteQueryData(contactSlicesQuery(deps.q)),
  pendingComponent: () => <PageSkeleton rows={6} />,
  errorComponent: ({ error, reset }) => <ErrorState error={error} reset={reset} />,
  component: Contacts,
})

function Contacts() {
  const { t } = useTranslation()
  const search = Route.useSearch()
  const navigate = useNavigate({ from: Route.fullPath })
  const { data, isPlaceholderData, hasNextPage, fetchNextPage, isFetchingNextPage } = useInfiniteQuery({
    ...contactSlicesQuery(search.q),
    placeholderData: keepPreviousData,
  })
  const items = data?.pages.flatMap((slice) => slice.items) ?? []
  const [query, setQuery] = useState(search.q)
  const debouncedQuery = useDebouncedValue(query)

  useEffect(() => {
    if (debouncedQuery !== search.q) {
      void navigate({ search: { q: debouncedQuery }, replace: true })
    }
  }, [debouncedQuery])

  return (
    <div className="space-y-6">
      <PageHeader title={t('contacts.title')} description={t('contacts.subtitle')} />

      {data?.pages[0]?._links.search && (
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

      {data && items.length === 0 ? (
        search.q ? (
          <EmptyState
            icon={SearchX}
            title={t('contacts.noResult.title')}
            description={t('contacts.noResult.description', { query: search.q })}
          />
        ) : (
          <EmptyState
            icon={UsersRound}
            title={t('contacts.empty.title')}
            description={t('contacts.empty.description')}
          />
        )
      ) : (
        <ul className={cn('grid gap-3 transition-opacity md:grid-cols-2', isPlaceholderData && 'opacity-60')}>
          {items.map((contact) => (
            <li key={contact.id}>
              <ContactCard contact={contact} />
            </li>
          ))}
        </ul>
      )}

      {hasNextPage && (
        <div className="flex justify-center">
          <Button variant="outline" onClick={() => void fetchNextPage()} disabled={isFetchingNextPage}>
            {t('common.loadMore')}
          </Button>
        </div>
      )}
    </div>
  )
}
