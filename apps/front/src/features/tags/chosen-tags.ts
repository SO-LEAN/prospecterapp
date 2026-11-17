const MARK = '#'
const SEPARATOR = ','

/**
 * The tags chosen to find the contacts carrying them, as the url and the api keep them: their names without their
 * mark, separated by commas (`ESN,EMAIL`), so that a filter is shared as a link.
 */
export const chosenTagsIn = (search: string): string[] =>
  search === '' ? [] : search.split(SEPARATOR).map((name) => MARK + name)

export const searchOfTags = (tags: string[]): string =>
  tags.map((tag) => (tag.startsWith(MARK) ? tag.slice(MARK.length) : tag)).join(SEPARATOR)
