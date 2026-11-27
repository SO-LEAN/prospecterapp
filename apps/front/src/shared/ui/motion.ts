/**
 * A menu, a tooltip or a list opens in 160 ms, fading in and growing from 0.97 out of its trigger. It leaves the same
 * way, on the exit curve.
 */
export const FLOATING_MOTION =
  'animate-in fade-in-0 zoom-in-97 duration-160 ease-enter data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-97 data-[state=closed]:ease-exit'
