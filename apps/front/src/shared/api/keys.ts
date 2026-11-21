/**
 * Query key prefixes shared by several features (openapi-react-query keys are [method, path, params]).
 * Invalidating a prefix marks every matching query stale: they refetch only if displayed.
 */
export const queryKeys = {
  dashboard: ['get', '/api/dashboard'],
  organizationList: ['get', '/api/organizations'],
  organization: ['get', '/api/organizations/{id}'],
  organizationContacts: ['get', '/api/organizations/{id}/contacts'],
  contactList: ['get', '/api/contacts'],
  contact: ['get', '/api/contacts/{id}'],
  organizationsOfContact: ['get', '/api/contacts/{id}/organizations'],
  organizationTags: ['get', '/api/organizations/{id}/tags'],
  contactTags: ['get', '/api/contacts/{id}/tags'],
  organizationTimelines: ['get', '/api/organizations/{id}/exchanges'],
  organizationUpcomingExchanges: ['get', '/api/organizations/{id}/upcoming-exchanges'],
  exchangesToMakeConform: ['get', '/api/organizations/{id}/exchanges-to-redact'],
  exchange: ['get', '/api/exchanges/{id}'],
  campaignList: ['get', '/api/campaigns'],
  campaign: ['get', '/api/campaigns/{id}'],
  campaignRecipients: ['get', '/api/campaigns/{id}/recipients'],
  myCalendar: ['get', '/api/me/calendar'],
  myDay: ['get', '/api/me/day'],
  exchangesToProcess: ['get', '/api/me/exchanges-to-process'],
} as const
