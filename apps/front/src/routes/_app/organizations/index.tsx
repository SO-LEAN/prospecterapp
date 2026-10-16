import { keepPreviousData, useInfiniteQuery } from '@tanstack/react-query'
import { createFileRoute, Link, useNavigate } from '@tanstack/react-router'
import { zodValidator } from '@tanstack/zod-adapter'
import { Building2, Plus, Search, SearchX } from 'lucide-react'
import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'
import { OrganizationCard } from '@/features/organizations/components/organization-card'
import { organizationSlicesQuery } from '@/features/organizations/queries'
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

/** The search lives in the URL (shareable, the back button works); the list comes a slice after the other. */
export const Route = createFileRoute('/_app/organizations/')({
  validateSearch: zodValidator(searchSchema),
  loaderDeps: ({ search }) => search,
  loader: ({ context, deps }) => context.queryClient.ensureInfiniteQueryData(organizationSlicesQuery(deps.q)),
  pendingComponent: () => <PageSkeleton rows={6} />,
  errorComponent: ({ error, reset }) => <ErrorState error={error} reset={reset} />,
  component: Organizations,
})

function Organizations() {
  const { t } = useTranslation()
  const search = Route.useSearch()
  const navigate = useNavigate({ from: Route.fullPath })
  const { data, isPlaceholderData, hasNextPage, fetchNextPage, isFetchingNextPage } = useInfiniteQuery({
    ...organizationSlicesQuery(search.q),
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
      <PageHeader
        title={t('organizations.title')}
        description={t('organizations.subtitle')}
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

      {data && items.length === 0 ? (
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
          {items.map((organization) => (
            <li key={organization.id}>
              <OrganizationCard organization={organization} />
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
