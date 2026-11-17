import type { Campaign } from './queries'

/** The campaigns shown by status: those running by default (draft, frozen, started), the stopped ones, or all. */
export const CAMPAIGN_FILTERS = ['running', 'stopped', 'all'] as const
export type CampaignFilter = (typeof CAMPAIGN_FILTERS)[number]

const RUNNING: ReadonlyArray<Campaign['status']> = ['draft', 'frozen', 'started']

export function shownBy(filter: CampaignFilter, campaign: Campaign): boolean {
  switch (filter) {
    case 'running':
      return RUNNING.includes(campaign.status)
    case 'stopped':
      return campaign.status === 'stopped'
    case 'all':
      return true
  }
}
