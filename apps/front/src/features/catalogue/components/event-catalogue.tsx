import type { ReactNode } from 'react'
import { useId } from 'react'
import { useTranslation } from 'react-i18next'
import { CHANNEL_ICONS, type Channel } from '@/shared/ui/event/channel'
import { EventPill } from '@/shared/ui/event/event-pill'
import {
  AIMarker,
  BookedByLinkMarker,
  CampaignMarker,
  GmailMarker,
  LateMarker,
  ToAttachMarker,
  ToRedactMarker,
} from '@/shared/ui/event/markers'
import type { ExchangeStatus } from '@/shared/ui/event/status-mark'
import { SyncMarker } from '@/shared/ui/event/sync-marker'
import { ToDoPill } from '@/shared/ui/event/to-do-pill'
import { TypeTile } from '@/shared/ui/event/type-tile'

const CHANNELS = Object.keys(CHANNEL_ICONS) as Channel[]

const STATUSES: ExchangeStatus[] = ['planned', 'done', 'missed', 'cancelled']

/**
 * Every shared event component in every state, to check them by eye in both themes: each channel in each status,
 * the type tiles, the to-dos and the markers. No screen links to it.
 */
export function EventCatalogue() {
  return (
    <div className="space-y-8">
      <ChannelsAndStatuses />
      <TypeTiles />
      <ToDos />
      <Markers />
    </div>
  )
}

function ChannelsAndStatuses() {
  const { t } = useTranslation()

  return (
    <div className="overflow-x-auto">
      <table
        aria-label={t('catalogue.channelsAndStatuses')}
        className="w-full border-separate border-spacing-2 text-sm"
      >
        <thead>
          <tr>
            <th scope="col" className="text-left font-medium text-muted-foreground">
              {t('catalogue.channel')}
            </th>
            {STATUSES.map((status) => (
              <th key={status} scope="col" className="text-left font-medium text-muted-foreground">
                {t(`events.statuses.${status}`)}
              </th>
            ))}
            <th scope="col" className="text-left font-medium text-muted-foreground">
              {t('events.statuses.late')}
            </th>
          </tr>
        </thead>
        <tbody>
          {CHANNELS.map((channel) => (
            <tr key={channel}>
              <th scope="row" className="text-left font-medium">
                {t(`exchanges.channels.${channel}`)}
              </th>
              {STATUSES.map((status) => (
                <td key={status}>
                  <EventPill channel={channel} status={status}>
                    {t('catalogue.sample')}
                  </EventPill>
                </td>
              ))}
              <td>
                <EventPill channel={channel} status="planned" late>
                  {t('catalogue.sample')}
                </EventPill>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}

function TypeTiles() {
  const { t } = useTranslation()

  return (
    <Section title={t('catalogue.tiles')}>
      {CHANNELS.map((channel) => (
        <span key={channel} className="flex gap-1">
          <TypeTile channel={channel} direction="inbound" />
          <TypeTile channel={channel} direction="outbound" />
        </span>
      ))}
    </Section>
  )
}

function ToDos() {
  const { t } = useTranslation()

  return (
    <Section title={t('catalogue.toDos')}>
      <ToDoPill done={false}>{t('catalogue.toDoSample')}</ToDoPill>
      <ToDoPill done>{t('catalogue.toDoSample')}</ToDoPill>
      <ToDoPill done={false} late>
        {t('catalogue.toDoSample')}
      </ToDoPill>
    </Section>
  )
}

function Markers() {
  const { t } = useTranslation()
  const now = new Date().toISOString()

  return (
    <Section title={t('catalogue.markers')}>
      <AIMarker />
      <GmailMarker />
      <CampaignMarker name={t('catalogue.campaignSample')} />
      <CampaignMarker name={null} />
      <BookedByLinkMarker />
      <ToAttachMarker exchange={{ contactToAttach: true }} />
      <ToRedactMarker />
      <LateMarker />
      <SyncMarker sync={{ provider: 'google', state: 'synced', at: now, reason: null }} />
      <SyncMarker sync={{ provider: 'google', state: 'pending', at: null, reason: null }} />
      <SyncMarker sync={{ provider: 'google', state: 'failed', at: now, reason: 'calendarRefusedThePush' }} />
    </Section>
  )
}

function Section({ title, children }: { title: string; children: ReactNode }) {
  const id = useId()

  return (
    <section aria-labelledby={id} className="space-y-3">
      <h2 id={id} className="font-semibold text-lg">
        {title}
      </h2>
      <div className="flex flex-wrap items-center gap-3">{children}</div>
    </section>
  )
}
