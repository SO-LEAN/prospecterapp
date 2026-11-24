/**
 * Writes the page of a context's event storming from its model: node event-storming/generate.ts <model.json> <page>
 * (make event-storming, docs/architecture/event-storming.md).
 */
import { readFile, writeFile } from 'node:fs/promises'
import { layOut } from './layout.ts'
import type { Model } from './model.ts'
import { pageOf } from './page.ts'
import { viewsOf } from './views.ts'

const [modelFile, pageFile] = process.argv.slice(2)
if (modelFile === undefined || pageFile === undefined) {
  throw new Error('Usage: node event-storming/generate.ts <model.json> <page.html>')
}
const model = JSON.parse(await readFile(modelFile, 'utf8')) as Model
const views = await Promise.all(viewsOf(model).map(layOut))
await writeFile(pageFile, pageOf(model.context, views))
