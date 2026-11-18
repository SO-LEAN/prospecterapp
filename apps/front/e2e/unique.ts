/**
 * What makes the name of the data a journey creates its own: the moment of the run, the one read of the real clock
 * the journeys make; it names, it decides nothing (docs/architecture/tests.md).
 */
export function unique(): number {
  // biome-ignore lint/plugin: names the data a journey creates, decides nothing
  return Date.now()
}
