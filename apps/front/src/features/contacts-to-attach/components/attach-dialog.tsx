import { useQuery } from '@tanstack/react-query'
import { Building2, Plus } from 'lucide-react'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Button } from '@/shared/ui/button'
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/shared/ui/dialog'
import { Input } from '@/shared/ui/input'
import { Label } from '@/shared/ui/label'
import { useAttach } from '../mutations'
import {
  domainOf,
  organizationNamedAfter,
  organizationsFoundQuery,
  proposedOrganizationQuery,
} from '../queries'

type Props = {
  contactId: string
  name: string
  email: string | null
  open: boolean
  onClose: () => void
}

/**
 * Attaching a contact to an organization: the one proposed by the domain of their email, one of mine searched, or
 * the one created for that domain, its name deduced from it and its email at it, both to change (ADR 0146).
 */
export function AttachDialog({ contactId, name, email, open, onClose }: Props) {
  const { t } = useTranslation()
  const attach = useAttach(onClose)
  const [creating, setCreating] = useState(false)

  return (
    <Dialog open={open} onOpenChange={(opened) => !opened && onClose()}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>
            {t('toAttach.attach')} {name}
          </DialogTitle>
        </DialogHeader>
        {creating ? (
          <CreateTheOrganization
            email={email}
            pending={attach.isPending}
            onCancel={() => setCreating(false)}
            onCreate={(corporateName, organizationEmail) =>
              attach.mutate({ contactId, corporateName, email: organizationEmail })
            }
          />
        ) : (
          <>
            {open && (
              <Proposed
                contactId={contactId}
                onAttach={(organizationId) => attach.mutate({ contactId, organizationId })}
              />
            )}
            <Search onAttach={(organizationId) => attach.mutate({ contactId, organizationId })} />
            <DialogFooter>
              <Button variant="outline" onClick={() => setCreating(true)}>
                <Plus aria-hidden /> {t('toAttach.newOrganization')}
              </Button>
            </DialogFooter>
          </>
        )}
      </DialogContent>
    </Dialog>
  )
}

function Proposed({
  contactId,
  onAttach,
}: {
  contactId: string
  onAttach: (organizationId: string) => void
}) {
  const { t } = useTranslation()
  const { data } = useQuery(proposedOrganizationQuery(contactId))
  const organization = data?.organization

  if (!organization) return null
  return (
    <Button onClick={() => onAttach(organization.id)}>
      <Building2 aria-hidden /> {t('toAttach.attachTo', { name: organization.name })}
    </Button>
  )
}

/** One of my organizations, found by the beginning of its name or of its email. */
function Search({ onAttach }: { onAttach: (organizationId: string) => void }) {
  const { t } = useTranslation()
  const [q, setQ] = useState('')
  const { data } = useQuery({ ...organizationsFoundQuery(q), enabled: q.trim() !== '' })

  return (
    <div className="space-y-2">
      <Input
        type="search"
        aria-label={t('toAttach.searchOrganization')}
        placeholder={t('toAttach.searchOrganization')}
        value={q}
        onChange={(event) => setQ(event.target.value)}
      />
      <ul className="space-y-1">
        {(q.trim() === '' ? [] : (data?.items ?? [])).map((organization) => (
          <li key={organization.id}>
            <Button
              variant="ghost"
              className="w-full justify-start"
              onClick={() => onAttach(organization.id)}
            >
              {t('toAttach.attachTo', { name: organization.name })}
            </Button>
          </li>
        ))}
      </ul>
    </div>
  )
}

type CreateProps = {
  email: string | null
  pending: boolean
  onCancel: () => void
  onCreate: (corporateName: string, email: string) => void
}

function CreateTheOrganization({ email, pending, onCancel, onCreate }: CreateProps) {
  const { t } = useTranslation()
  const [corporateName, setCorporateName] = useState(email ? organizationNamedAfter(email) : '')
  const [organizationEmail, setOrganizationEmail] = useState(email ? `contact@${domainOf(email)}` : '')

  return (
    <form
      className="space-y-3"
      onSubmit={(event) => {
        event.preventDefault()
        onCreate(corporateName.trim(), organizationEmail.trim())
      }}
    >
      <div className="space-y-1">
        <Label htmlFor="attach-corporate-name">{t('fields.corporateName')}</Label>
        <Input
          id="attach-corporate-name"
          value={corporateName}
          onChange={(event) => setCorporateName(event.target.value)}
          required
        />
      </div>
      <div className="space-y-1">
        <Label htmlFor="attach-email">{t('fields.email')}</Label>
        <Input
          id="attach-email"
          type="text"
          inputMode="email"
          value={organizationEmail}
          onChange={(event) => setOrganizationEmail(event.target.value)}
          required
        />
      </div>
      <DialogFooter>
        <Button type="button" variant="outline" onClick={onCancel}>
          {t('common.cancel')}
        </Button>
        <Button type="submit" disabled={pending}>
          {t('common.save')}
        </Button>
      </DialogFooter>
    </form>
  )
}
