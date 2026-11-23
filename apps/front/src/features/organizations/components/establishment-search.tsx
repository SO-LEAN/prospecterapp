import { useQuery } from '@tanstack/react-query'
import { Search } from 'lucide-react'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { isProblem, messageOf } from '@/shared/api/problem'
import { Alert, AlertDescription } from '@/shared/ui/alert'
import { Badge } from '@/shared/ui/badge'
import { Button } from '@/shared/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/shared/ui/dialog'
import { type Establishment, establishmentsQuery } from '../queries'
import { spacedSiret } from '../siret'

/** The public directory's establishments that the organization may be. The chosen one gives its SIRET. */
export function EstablishmentSearch({
  organizationId,
  onChoose,
}: {
  organizationId: string
  onChoose: (siret: string) => void
}) {
  const { t } = useTranslation()
  const [open, setOpen] = useState(false)

  const choose = (siret: string) => {
    onChoose(siret)
    setOpen(false)
  }

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button type="button" variant="outline" size="sm">
          <Search aria-hidden /> {t('organizations.establishments.search')}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{t('organizations.establishments.title')}</DialogTitle>
          <DialogDescription>{t('organizations.establishments.description')}</DialogDescription>
        </DialogHeader>
        {open && <Establishments organizationId={organizationId} onChoose={choose} />}
      </DialogContent>
    </Dialog>
  )
}

function Establishments({
  organizationId,
  onChoose,
}: {
  organizationId: string
  onChoose: (siret: string) => void
}) {
  const { t } = useTranslation()
  const { data, error } = useQuery(establishmentsQuery(organizationId))

  if (error) {
    return (
      <Alert variant="destructive">
        <AlertDescription>{isProblem(error) ? messageOf(error) : t('errors.network')}</AlertDescription>
      </Alert>
    )
  }

  if (!data) {
    return <p className="text-sm text-muted-foreground">{t('organizations.establishments.searching')}</p>
  }

  if (data.establishments.length === 0) {
    return <p className="text-sm text-muted-foreground">{t('organizations.establishments.none')}</p>
  }

  return (
    <ul aria-label={t('organizations.establishments.title')} className="max-h-96 space-y-1 overflow-y-auto">
      {data.establishments.map((establishment) => (
        <li key={establishment.siret}>
          <EstablishmentChoice establishment={establishment} onChoose={onChoose} />
        </li>
      ))}
    </ul>
  )
}

function EstablishmentChoice({
  establishment,
  onChoose,
}: {
  establishment: Establishment
  onChoose: (siret: string) => void
}) {
  const { t } = useTranslation()

  return (
    <button
      type="button"
      onClick={() => onChoose(establishment.siret)}
      className="flex w-full items-start justify-between gap-3 rounded-md px-3 py-2 text-left hover:bg-accent focus-visible:bg-accent focus-visible:outline-none"
    >
      <span className="min-w-0">
        <span className="block font-medium">{establishment.name}</span>
        <span className="block text-sm text-muted-foreground">{establishment.address}</span>
        <span className="block text-xs text-muted-foreground">
          {establishment.activity
            ? t('organizations.establishments.siretAndActivity', {
                siret: spacedSiret(establishment.siret),
                activity: establishment.activity,
              })
            : spacedSiret(establishment.siret)}
        </span>
      </span>
      {establishment.closed && <Badge variant="secondary">{t('organizations.establishments.closed')}</Badge>}
    </button>
  )
}
