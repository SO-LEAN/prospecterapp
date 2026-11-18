/** The address of the public page a code leads to, on the host the SPA is served from. */
export const publicLinkOf = (code: string) => `${window.location.origin}/booking/${code}`
