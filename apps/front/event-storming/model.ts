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
  type: StickyType
  label: string
  name: string
  class: string | null
  context: string
  module: string | null
  detail: string | null
}

export interface Link {
  from: string
  to: string
  via: string | null
  certain: boolean
}

export interface Model {
  context: string
  modules: string[]
  stickies: Sticky[]
  links: Link[]
}
