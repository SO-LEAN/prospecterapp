/**
 * An API list comes in slices, newest first (ADR 0049). A slice's `next` link holds the next slice's cursor
 * (`after`). The last slice has no `next` link.
 */
export function nextSliceOf(links: { next?: { href: string } }): string | undefined {
  const href = links.next?.href
  return href === undefined
    ? undefined
    : (new URL(href, window.location.origin).searchParams.get('after') ?? undefined)
}

/** A typed client response's data. Throws its problem, as the query bindings do. */
export function dataOf<Data>({ data, error }: { data?: Data; error?: unknown }): Data {
  if (data === undefined) {
    throw error ?? new Error('The API answered no data')
  }
  return data
}
