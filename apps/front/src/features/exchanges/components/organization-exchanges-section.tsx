import { useInfiniteQuery } from '@tanstack/react-query'
import { ShieldAlert } from 'lucide-react'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Button } from '@/shared/ui/button'
import { attachTheFiles, useAttachFiles, useNoteOrganizationExchange } from '../mutations'
import { exchangesToMakeConformQuery, organizationTimelineQuery } from '../queries'
import { ExchangeForm, type Interlocutors } from './exchange-form'
import { ExchangeLine } from './exchange-line'
import { NoteExchangeDialog, TimelineCard } from './timeline-card'

type Props = { organizationId: string; interlocutors: Interlocutors; className?: string }

/**
 * The timeline of the organization: its exchanges and those of its contacts, each with who communicated, the
 * most recent first (ADR 0076); narrowed to those to make conform, their contact forgotten, by the badge
 * counting them (ADR 0079).
 */
export function OrganizationExchangesSection({ organizationId, interlocutors, className }: Props) {
  const all = useInfiniteQuery(organizationTimelineQuery(organizationId))
  const [narrowed, setNarrowed] = useState(false)
  const toMakeConform = all.data?.pages[0]?.toMakeConform ?? 0
  const onlyToMakeConform = narrowed && toMakeConform > 0
  const narrowedTimeline = useInfiniteQuery({
    ...exchangesToMakeConformQuery(organizationId),
    enabled: onlyToMakeConform,
  })
  const timeline = onlyToMakeConform && narrowedTimeline.data ? narrowedTimeline : all
  const note = useNoteOrganizationExchange()
  const attach = useAttachFiles()

  if (!timeline.data) {
    return null
  }
  const slices = timeline.data.pages
  const mayNote = Boolean(slices[0]?._links.noteExchange)

  return (
    <TimelineCard
      className={className}
      timeline={timeline}
      filter={
        toMakeConform > 0 && (
          <ToMakeConformBadge count={toMakeConform} pressed={onlyToMakeConform} onPress={setNarrowed} />
        )
      }
      items={slices.flatMap((slice) => slice.items)}
      keyOf={(item) => item.exchange.id}
      renderItem={(item) => <ExchangeLine exchange={item.exchange} interlocutor={item.interlocutor} />}
      noteExchange={
        mayNote && (
          <NoteExchangeDialog name={interlocutors.organization}>
            {(close) => (
              <ExchangeForm
                withFiles
                interlocutors={interlocutors}
                onCancel={close}
                onSubmit={async (exchange, personId, files) => {
                  const { noted } = await note.mutateAsync({ organizationId, body: { exchange, personId } })
                  close()
                  attachTheFiles(attach, noted, files)
                }}
              />
            )}
          </NoteExchangeDialog>
        )
      }
    />
  )
}

type BadgeProps = { count: number; pressed: boolean; onPress: (pressed: boolean) => void }

/** How many exchanges are to make conform: pressed, the timeline shows only them. */
function ToMakeConformBadge({ count, pressed, onPress }: BadgeProps) {
  const { t } = useTranslation()

  return (
    <Button
      size="sm"
      variant={pressed ? 'secondary' : 'outline'}
      aria-pressed={pressed}
      title={t('exchanges.onlyToMakeConform')}
      onClick={() => onPress(!pressed)}
    >
      <ShieldAlert aria-hidden className="text-warning" />
      {t('exchanges.toMakeConformCount', { count })}
    </Button>
  )
}
