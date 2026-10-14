import { keepPreviousData, useQuery } from '@tanstack/react-query'
import { createFileRoute, Link, useNavigate } from '@tanstack/react-router'
import { zodValidator } from '@tanstack/zod-adapter'
import { Building2, ChevronLeft, ChevronRight, Plus, Search, SearchX } from 'lucide-react'
import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'
import { OrganizationCard } from '@/features/organizations/components/organization-card'
import { organizationsQuery } from '@/features/organizations/queries'
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

/** The search and the page live in the URL: shareable, and the back button works. */
export const Route = createFileRoute('/_app/organizations/')({
  validateSearch: zodValidator(searchSchema),
  loaderDeps: ({ search }) => search,
  loader: ({ context, deps }) => context.queryClient.ensureQueryData(organizationsQuery(deps)),
  pendingComponent: () => <PageSkeleton rows={6} />,
  errorComponent: ({ error, reset }) => <ErrorState error={error} reset={reset} />,
  component: Organizations,
})

function Organizations() {
  const { t } = useTranslation()
  const search = Route.useSearch()
  const navigate = useNavigate({ from: Route.fullPath })
  // previous page stays on screen while the next one loads (no flash of skeleton)
  const { data, isPlaceholderData } = useQuery({
    ...organizationsQuery(search),
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
        title={t('organizations.title')}
        description={data && t('organizations.subtitle', { count: data.totalItems })}
        actions={
          <Button asChild>
            <Link to="/organizations/new">
              <Plus /> {t('organizations.new')}
            </Link>
          </Button>
        }
      />

      <div className="relative max-w-md">
        <Search
          className="-translate-y-1/2 pointer-events-none absolute top-1/2 left-3 size-4 text-muted-foreground"
          aria-hidden
        />
        <Input
          type="search"
          value={query}
          onChange={(event) => setQuery(event.target.value)}
          placeholder={t('organizations.searchPlaceholder')}
          aria-label={t('organizations.searchPlaceholder')}
          className="pl-9"
        />
      </div>

      {data && data.items.length === 0 ? (
        search.q ? (
          <EmptyState
            icon={SearchX}
            title={t('organizations.noResult.title')}
            description={t('organizations.noResult.description', { query: search.q })}
          />
        ) : (
          <EmptyState
            icon={Building2}
            title={t('organizations.empty.title')}
            description={t('organizations.empty.description')}
            action={
              <Button asChild>
                <Link to="/organizations/new">
                  <Plus /> {t('organizations.new')}
                </Link>
              </Button>
            }
          />
        )
      ) : (
        <ul className={cn('grid gap-3 transition-opacity md:grid-cols-2', isPlaceholderData && 'opacity-60')}>
          {data?.items.map((organization) => (
            <li key={organization.id}>
              <OrganizationCard organization={organization} />
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
