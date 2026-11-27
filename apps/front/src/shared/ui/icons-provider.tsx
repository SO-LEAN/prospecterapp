import { LucideProvider } from 'lucide-react'
import type { ReactNode } from 'react'

/** The Filon draws its lucide icons with a finer stroke than lucide's 2 px. */
const ICON_STROKE_WIDTH = 1.75

export function IconsProvider({ children }: { children: ReactNode }) {
  return <LucideProvider strokeWidth={ICON_STROKE_WIDTH}>{children}</LucideProvider>
}
