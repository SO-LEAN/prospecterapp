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
  organizationTimelines: ['get', '/api/organizations/{id}/exchanges'],
  exchangesToMakeConform: ['get', '/api/organizations/{id}/exchanges-to-make-conform'],
  exchange: ['get', '/api/exchanges/{id}'],
} as const
