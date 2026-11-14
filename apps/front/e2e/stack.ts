/**
 * The addresses of the stack under test: on 443 for the stack of the main checkout, on the port of its own for that
 * of a worktree (`PUBLIC_PORT`, ADR 0116), the same in the network of the stack as from the host.
 */
const PUBLIC_PORT = process.env.PUBLIC_PORT ?? '443'
const PORT_SUFFIX = PUBLIC_PORT === '443' ? '' : `:${PUBLIC_PORT}`

export const SPA_URL = `https://local.prospecter.io${PORT_SUFFIX}`
export const API_URL = `https://api.local.prospecter.io${PORT_SUFFIX}`
export const STATIC_URL = `https://static.local.prospecter.io${PORT_SUFFIX}`
