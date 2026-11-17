import type { Campaign } from './queries'

/** The recipients the team handled already: processed by an exchange, or dismissed without one. */
export const handledIn = (campaign: Pick<Campaign, 'progress'>): number =>
  campaign.progress.processed + campaign.progress.dismissed
