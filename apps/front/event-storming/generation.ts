import { type Document, folderOf, type Model } from './model.ts'

/** What `make event-storming` writes: the wall of each context, in the folder named after it, and the overview. */
export interface Generation {
  walls: { model: Model; folder: string }[]
  overview: boolean
}

/**
 * Every context's wall and the overview, or the wall of the one context named (`CONTEXT=`) alone. A context the
 * code does not hold is refused.
 */
export function generationOf(document: Document, context?: string): Generation {
  const models = document.models.filter((model) => context === undefined || model.context === context)
  if (models.length === 0) throw new Error(`No context ${context} in the code`)
  return {
    walls: models.map((model) => ({ model, folder: folderOf(model.context) })),
    overview: context === undefined,
  }
}

/** The SVGs a wall's folder holds that the wall no longer writes: a lane gone leaves none behind. */
export function staleSvgsOf(found: string[], written: string[]): string[] {
  return found.filter((name) => name.endsWith('.svg') && !written.includes(name))
}
