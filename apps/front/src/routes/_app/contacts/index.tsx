import { keepPreviousData, useQuery } from '@tanstack/react-query'
import { createFileRoute, Link, useNavigate } from '@tanstack/react-router'
import { zodValidator } from '@tanstack/zod-adapter'
import { ChevronLeft, ChevronRight, Plus, Search, SearchX, UsersRound } from 'lucide-react'
import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'
import { ContactCard } from '@/features/contacts/components/contact-card'
import { contactsQuery } from '@/features/contacts/queries'
import { useDebouncedValue } from '@/shared/hooks/use-debounced-value'
import { cn } from '@/shared/lib/utils'
import { Button } from '@/shared/ui/button'
import { EmptyState } from '@/shared/ui/feedback/empty-state'
import { ErrorState } from '@/shared/ui/feedback/error-state'
import { Input } from '@/shared/ui/input'
import { PageHeader } from '@/shared/ui/page-header'
import { PageSkeleton } from '@/shared/ui/page-skeleton'

const searchSchema = z.object({
  page: z.number().int().positive().catch(1).default(1),
  q: z.string().catch('').default(''),
})

/**
 * The persons my organization prospects. The search and the page live in the URL; searching and collecting
 * are offered only with the right to manage personal data (the links of the page).
 */
export const Route = createFileRoute('/_app/contacts/')({
  validateSearch: zodValidator(searchSchema),
  loaderDeps: ({ search }) => search,
  loader: ({ context, deps }) => context.queryClient.ensureQueryData(contactsQuery(deps)),
  pendingComponent: () => <PageSkeleton rows={6} />,
  errorComponent: ({ error, reset }) => <ErrorState error={error} reset={reset} />,
  component: Contacts,
})

function Contacts() {
  const { t } = useTranslation()
  const search = Route.useSearch()
  const navigate = useNavigate({ from: Route.fullPath })
  const { data, isPlaceholderData } = useQuery({
    ...contactsQuery(search),
    placeholderData: keepPreviousData,
  })
  const [query, setQuery] = useState(search.q)
  const debouncedQuery = useDebouncedValue(query)

  useEffect(() => {
    if (debouncedQuery !== search.q) {
      void navigate({ search: { q: debouncedQuery, page: 1 }, replace: true })
    }
  }, [debouncedQuery])

  const page = data?.page ?? search.page
  const totalPages = Math.max(data?.totalPages ?? 1, 1)

  return (
    <div className="space-y-6">
      <PageHeader
        title={t('contacts.title')}
        description={data && t('contacts.subtitle', { count: data.totalItems })}
        actions={
          data?._links.collect && (
            <Button asChild>
              <Link to="/contacts/new">
                <Plus /> {t('contacts.new')}
              </Link>
            </Button>
          )
        }
      />

      {data?._links.search && (
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

      {data && data.items.length === 0 ? (
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
            action={
              data._links.collect && (
                <Button asChild>
                  <Link to="/contacts/new">
                    <Plus /> {t('contacts.new')}
                  </Link>
                </Button>
              )
            }
          />
        )
      ) : (
        <ul className={cn('grid gap-3 transition-opacity md:grid-cols-2', isPlaceholderData && 'opacity-60')}>
          {data?.items.map((contact) => (
            <li key={contact.id}>
              <ContactCard contact={contact} />
            </li>
          ))}
        </ul>
      )}

      {totalPages > 1 && (
        <nav className="flex items-center justify-between gap-4" aria-label="Pagination">
          <Button variant="outline" size="sm" disabled={page <= 1} asChild={page > 1}>
            {page > 1 ? (
              <Link to="." search={(previous) => ({ ...previous, page: page - 1 })}>
                <ChevronLeft /> {t('common.previous')}
              </Link>
            ) : (
              <span>
                <ChevronLeft /> {t('common.previous')}
              </span>
            )}
          </Button>
          <span className="text-muted-foreground text-sm">
            {t('common.pageOf', { page, total: totalPages })}
          </span>
          <Button variant="outline" size="sm" disabled={page >= totalPages} asChild={page < totalPages}>
            {page < totalPages ? (
              <Link to="." search={(previous) => ({ ...previous, page: page + 1 })}>
                {t('common.next')} <ChevronRight />
              </Link>
            ) : (
              <span>
                {t('common.next')} <ChevronRight />
              </span>
            )}
          </Button>
        </nav>
      )}
    </div>
  )
}
