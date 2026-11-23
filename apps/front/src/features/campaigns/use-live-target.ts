import { keepPreviousData, useInfiniteQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { searchOfTags, taggedContactSlicesQuery } from '@/shared/api/tagged-contacts'
import type { Channel, TargetedContact } from './queries'
import { MAX_TARGETED_TAGS, targetedTagsOf } from './schema'

/** Why there is no target to show: no tag is chosen yet, or more tags than a search takes. */
export type NoTarget = 'noTag' | 'tooManyTags'

export type LiveTarget = {
  /** The form gives it each time its tags or channel change. */
  follow: (tags: string[], channel: Channel) => void
  noTarget: NoTarget | null
  contacts: TargetedContact[]
  found: number
  loadMore?: () => void
}

/**
 * The contacts that a campaign targets as it is typed, saved or not (ADR 0144). It searches by the campaign's
 * tags and the channel's tag. The previous result stays on screen while the next one loads. Nothing is
 * requested while no tag, or more than 5 tags, are chosen.
 */
export function useLiveTarget(tags: string[], channel: Channel): LiveTarget {
  const [targeting, setTargeting] = useState({ tags, channel })
  const targeted = targetedTagsOf(targeting.tags, targeting.channel)
  const noTarget = noTargetFor(targeting.tags, targeted)
  const search = searchOfTags(targeted)
  const target = useInfiniteQuery({
    ...taggedContactSlicesQuery(search),
    placeholderData: keepPreviousData,
    enabled: noTarget === null,
  })

  return {
    follow: (followed, followedChannel) => {
      if (followed.join() !== targeting.tags.join() || followedChannel !== targeting.channel) {
        setTargeting({ tags: followed, channel: followedChannel })
      }
    },
    noTarget,
    contacts: target.data?.pages.flatMap((slice) => slice.items) ?? [],
    found: target.data?.pages[0]?.found ?? 0,
    loadMore: target.hasNextPage ? () => void target.fetchNextPage() : undefined,
  }
}

function noTargetFor(tags: string[], targeted: string[]): NoTarget | null {
  if (tags.length === 0) {
    return 'noTag'
  }
  return targeted.length > MAX_TARGETED_TAGS ? 'tooManyTags' : null
}
