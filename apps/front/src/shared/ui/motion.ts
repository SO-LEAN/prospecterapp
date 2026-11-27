/**
 * A menu, a tooltip or a list opens in 160 ms, fading in and growing from 0.97 out of its trigger. It leaves the same
 * way, on the exit curve.
 */
export const FLOATING_MOTION =
  'animate-in fade-in-0 zoom-in-97 duration-160 ease-enter data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-97 data-[state=closed]:ease-exit'

/** A dialog or a panel opens in 220 ms, fading in as it slides 24 px, and leaves in 160 ms on the exit curve. */
export const PANEL_MOTION =
  'animate-in fade-in-0 duration-220 ease-enter data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:duration-160 data-[state=closed]:ease-exit'

/** Behind a dialog or a panel, the page fades under a 3 px blur, at the panel's pace. */
export const BACKDROP = `${PANEL_MOTION} fixed inset-0 z-50 bg-black/50 backdrop-blur-[3px]`
