/**
 * Writes a context's wall from its model: its page and, for the repository's documents, an SVG per module and one of
 * the whole wall. node event-storming/generate.ts <model.json> <page.html> <svg folder> (make event-storming,
 * docs/architecture/event-storming.md).
 */
import { mkdir, readFile, writeFile } from 'node:fs/promises'
import { join } from 'node:path'
import type { Model } from './model.ts'
import { pageOf } from './page.ts'
import { svgsOf } from './svg.ts'

const [modelFile, pageFile, svgFolder] = process.argv.slice(2)
if (modelFile === undefined || pageFile === undefined || svgFolder === undefined) {
  throw new Error('Usage: node event-storming/generate.ts <model.json> <page.html> <svg folder>')
}
const here = new URL('.', import.meta.url)
const model = JSON.parse(await readFile(modelFile, 'utf8')) as Model
const style = await readFile(new URL('wall.css', here), 'utf8')
const font = (await readFile(new URL('font/patrick-hand.woff2', here))).toString('base64')
await writeFile(pageFile, pageOf(model, { style, font }))
await mkdir(svgFolder, { recursive: true })
for (const [name, svg] of Object.entries(svgsOf(model, font))) await writeFile(join(svgFolder, name), svg)
