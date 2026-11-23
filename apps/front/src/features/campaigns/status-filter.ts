import type { Campaign } from './queries'

/**
 * Which campaigns are shown, by status: the running ones by default (draft, frozen, started), the stopped or
 * ended ones, or all of them.
 */
export const CAMPAIGN_FILTERS = ['running', 'stopped', 'all'] as const
export type CampaignFilter = (typeof CAMPAIGN_FILTERS)[number]

const RUNNING: ReadonlyArray<Campaign['status']> = ['draft', 'frozen', 'started']
const OVER: ReadonlyArray<Campaign['status']> = ['stopped', 'ended']

export function shownBy(filter: CampaignFilter, campaign: Campaign): boolean {
  switch (filter) {
    case 'running':
      return RUNNING.includes(campaign.status)
    case 'stopped':
      return OVER.includes(campaign.status)
    case 'all':
      return true
  }
}
