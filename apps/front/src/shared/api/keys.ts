/**
 * Query key prefixes shared by several features (openapi-react-query keys are [method, path, params]).
 * Invalidating a prefix marks every matching query stale: they refetch only if displayed.
 */
export const queryKeys = {
  dashboard: ['get', '/api/dashboard'],
  organizationList: ['get', '/api/organizations'],
} as const
