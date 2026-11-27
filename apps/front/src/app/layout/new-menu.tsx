import { useInfiniteQuery, useQuery } from '@tanstack/react-query'
import { Link } from '@tanstack/react-router'
import { Building2, CalendarPlus, Megaphone, MessageSquarePlus, Plus, UserPlus } from 'lucide-react'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { accountQuery } from '@/features/account/queries'
import { contactSlicesQuery } from '@/features/contacts/queries'
import { ExchangeWithSomeone, type Gesture } from '@/features/exchanges/components/exchange-with-someone'
import { InterlocutorSearch } from '@/features/organizations/components/interlocutor-search'
import { Button } from '@/shared/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/shared/ui/dropdown-menu'

/**
 * « Nouveau », at the top of every page: it opens the creation forms that exist. To note or plan an exchange from
 * here, the member first chooses its contact or organization with the search. It opens once the member's rights are
 * read, so that no entry comes in after it opened.
 */
export function NewMenu() {
  const { t } = useTranslation()
  const account = useQuery(accountQuery())
  const contacts = useInfiniteQuery(contactSlicesQuery(''))
  const mayPrepareACampaign = account.data?._links.campaigns !== undefined
  const mayCollectAContact = contacts.data?.pages[0]?._links.collect !== undefined
  const [gesture, setGesture] = useState<Gesture | null>(null)

  return (
    <>
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button size="sm" variant="gold" aria-label={t('nav.new')} disabled={contacts.isPending}>
            <Plus aria-hidden />
            <span className="hidden sm:inline">{t('nav.new')}</span>
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" className="w-56">
          <DropdownMenuItem onSelect={() => setGesture('note')}>
            <MessageSquarePlus aria-hidden />
            {t('exchanges.note')}
          </DropdownMenuItem>
          <DropdownMenuItem onSelect={() => setGesture('plan')}>
            <CalendarPlus aria-hidden />
            {t('exchanges.planAnExchange')}
          </DropdownMenuItem>
          {mayCollectAContact && (
            <DropdownMenuItem asChild>
              <Link to="/contacts/new">
                <UserPlus aria-hidden />
                {t('contacts.new')}
              </Link>
            </DropdownMenuItem>
          )}
          <DropdownMenuItem asChild>
            <Link to="/organizations/new">
              <Building2 aria-hidden />
              {t('organizations.start.title')}
            </Link>
          </DropdownMenuItem>
          {mayPrepareACampaign && (
            <DropdownMenuItem asChild>
              <Link to="/campaigns/new">
                <Megaphone aria-hidden />
                {t('campaigns.prepare')}
              </Link>
            </DropdownMenuItem>
          )}
        </DropdownMenuContent>
      </DropdownMenu>
      <ExchangeWithSomeone
        gesture={gesture}
        onClose={() => setGesture(null)}
        interlocutorSearch={(onChoose) => <InterlocutorSearch onChoose={onChoose} />}
      />
    </>
  )
}
