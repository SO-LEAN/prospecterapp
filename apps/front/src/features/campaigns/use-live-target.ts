import { keepPreviousData, useInfiniteQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { searchOfTags, taggedContactSlicesQuery } from '@/shared/api/tagged-contacts'
import type { Channel, TargetedContact } from './queries'
import { MAX_TARGETED_TAGS, targetedTagsOf } from './schema'

/** Why there is no target to show: no tag chosen yet, or more than a search takes. */
export type NoTarget = 'noTag' | 'tooManyTags'

export type LiveTarget = {
  /** Given by the form each time its tags or its channel change. */
  follow: (tags: string[], channel: Channel) => void
  noTarget: NoTarget | null
  contacts: TargetedContact[]
  found: number
  loadMore?: () => void
}

/**
 * The contacts a campaign targets as it is typed, saved or not (ADR 0144): the search by its tags and that of its
 * channel, the previous one kept on screen while the next loads; none asked while no tag or more than 5 are chosen.
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
