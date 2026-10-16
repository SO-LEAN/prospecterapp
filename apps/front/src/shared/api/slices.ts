/**
 * A list of the API comes in slices, newest first (ADR 0049): the link `next` of a slice holds the cursor
 * (`after`) of the following one, and is absent on the last.
 */
export function nextSliceOf(links: { next?: { href: string } }): string | undefined {
  const href = links.next?.href
  return href === undefined
    ? undefined
    : (new URL(href, window.location.origin).searchParams.get('after') ?? undefined)
}

/** The data of a response of the typed client, its problem thrown as the query bindings do. */
export function dataOf<Data>({ data, error }: { data?: Data; error?: unknown }): Data {
  if (data === undefined) {
    throw error ?? new Error('The API answered no data')
  }
  return data
}
