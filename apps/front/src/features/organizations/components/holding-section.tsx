import { useQuery } from '@tanstack/react-query'
import { Link } from '@tanstack/react-router'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useDebouncedValue } from '@/shared/hooks/use-debounced-value'
import { Button } from '@/shared/ui/button'
import { EditableSection } from '@/shared/ui/editable-section'
import { Input } from '@/shared/ui/input'
import { useAttachToHolding, useDetachFromHolding } from '../mutations'
import { type Organization, organizationQuery, organizationsQuery } from '../queries'

export function HoldingSection({ organization }: { organization: Organization }) {
  const { t } = useTranslation()
  const detach = useDetachFromHolding()
  const holding = useQuery({
    ...organizationQuery(organization.holdingId ?? ''),
    enabled: organization.holdingId !== null,
  })

  return (
    <EditableSection
      title={t('organizations.sections.holding')}
      editable={Boolean(organization._links.attachToHolding)}
      editor={(close) => <HoldingPicker organization={organization} onDone={close} />}
    >
      {organization.holdingId ? (
        <div className="flex items-center justify-between gap-3">
          <Link
            to="/organizations/$organizationId"
            params={{ organizationId: organization.holdingId }}
            className="text-primary hover:underline"
          >
            {holding.data?.name ?? t('common.loading')}
          </Link>
          {organization._links.detachFromHolding && (
            <Button
              variant="ghost"
              size="sm"
              disabled={detach.isPending}
              onClick={() => detach.mutate({ params: { path: { id: organization.id } } })}
            >
              {t('organizations.holding.detach')}
            </Button>
          )}
        </div>
      ) : (
        <p className="text-muted-foreground">{t('organizations.holding.none')}</p>
      )}
    </EditableSection>
  )
}

function HoldingPicker({ organization, onDone }: { organization: Organization; onDone: () => void }) {
  const { t } = useTranslation()
  const attach = useAttachToHolding()
  const [query, setQuery] = useState('')
  const candidates = useQuery(organizationsQuery({ page: 1, q: useDebouncedValue(query) }))
  const others = (candidates.data?.items ?? []).filter((candidate) => candidate.id !== organization.id)

  const attachTo = async (holdingId: string) => {
    await attach.mutateAsync({ params: { path: { id: organization.id } }, body: { holdingId } })
    onDone()
  }

  return (
    <div className="space-y-3">
      <Input
        type="search"
        aria-label={t('organizations.holding.search')}
        placeholder={t('organizations.holding.search')}
        value={query}
        onChange={(event) => setQuery(event.target.value)}
        autoFocus
      />
      <ul className="max-h-60 divide-y overflow-auto rounded-lg border">
        {others.map((candidate) => (
          <li key={candidate.id}>
            <button
              type="button"
              className="w-full px-3 py-2 text-left hover:bg-accent"
              disabled={attach.isPending}
              onClick={() => void attachTo(candidate.id)}
            >
              {candidate.name}
            </button>
          </li>
        ))}
      </ul>
      <div className="flex justify-end">
        <Button type="button" variant="ghost" onClick={onDone}>
          {t('common.cancel')}
        </Button>
      </div>
    </div>
  )
}
