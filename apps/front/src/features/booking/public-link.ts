/** The address of the public page that a code leads to, on the host that serves the SPA. */
export const publicLinkOf = (code: string) => `${window.location.origin}/booking/${code}`
