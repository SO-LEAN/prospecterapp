/** The event storming of one context, as `make event-storming` writes it from the code (model.json). */
export type StickyType =
  | 'actor'
  | 'command'
  | 'aggregate'
  | 'event'
  | 'policy'
  | 'readModel'
  | 'externalSystem'

export interface Sticky {
  id: string
  /** Its short name in a link to the wall: `command:BookSlot`, `published:SlotBooked`, `PersonalData/policy:…`. */
  slug: string
  type: StickyType
  /** In English, from its name. */
  label: string
  /** The title of its use case's feature, in French. */
  feature: string | null
  name: string
  class: string | null
  context: string
  module: string | null
  detail: string | null
  /** The contexts an external system's adapters read. */
  answeredBy: string[]
  /** The published services of the other contexts an external system's adapters take. */
  answers: Elsewhere[]
  /** What takes a published service in the other contexts. */
  readBy: Elsewhere[]
}

/** A post-it of another wall: its context and its slug there. A class of the delivery stands on no wall. */
export interface Elsewhere {
  context: string | null
  slug: string | null
  label: string
}

export interface Link {
  from: string
  to: string
  via: string | null
  certain: boolean
  /** When an uncertain link happens, in English, if the code writes it. */
  when: string | null
}

/** What the analysis of the whole code writes: every context's model, and what it warns of. */
export interface Document {
  models: Model[]
  warnings: string[]
}

/** "PersonalData" lives in the folder `personal-data`. */
export function folderOf(context: string): string {
  return context.replace(/(?<=[a-z])(?=[A-Z])/g, '-').toLowerCase()
}

export interface Model {
  context: string
  modules: string[]
  stickies: Sticky[]
  links: Link[]
}
