/**
 * Writes the walls and the overview `generationOf` decides, from the document of the models the analysis read: each
 * wall's model and page, and, for the repository's documents, an SVG per module and one of the whole wall. This file
 * only reads and writes; what it writes is decided and tested in `generation.ts`.
 * node event-storming/generate.ts <document.json> <pages folder> <svgs folder> [context] (make event-storming,
 * docs/architecture/event-storming.md).
 */
import { mkdir, readdir, readFile, rm, writeFile } from 'node:fs/promises'
import { join } from 'node:path'
import { generationOf, staleSvgsOf } from './generation.ts'
import type { Document } from './model.ts'
import { overviewOf } from './overview.ts'
import { pageOf } from './page.ts'
import { svgsOf } from './svg.ts'

const [documentFile, pagesFolder, svgsFolder, context] = process.argv.slice(2)
if (documentFile === undefined || pagesFolder === undefined || svgsFolder === undefined) {
  throw new Error(
    'Usage: node event-storming/generate.ts <document.json> <pages folder> <svgs folder> [context]',
  )
}
const here = new URL('.', import.meta.url)
const document = JSON.parse(await readFile(documentFile, 'utf8')) as Document
const style = await readFile(new URL('wall.css', here), 'utf8')
const font = (await readFile(new URL('font/patrick-hand.woff2', here))).toString('base64')
const generation = generationOf(document, context)
for (const { model, folder } of generation.walls) {
  const pages = join(pagesFolder, folder)
  const svgs = join(svgsFolder, folder)
  await mkdir(pages, { recursive: true })
  await writeFile(join(pages, 'model.json'), `${JSON.stringify(model, null, 4)}\n`)
  await writeFile(join(pages, 'index.html'), pageOf(model, { style, font }))
  await mkdir(svgs, { recursive: true })
  const drawn = svgsOf(model, font)
  for (const stale of staleSvgsOf(await readdir(svgs), Object.keys(drawn))) await rm(join(svgs, stale))
  for (const [name, svg] of Object.entries(drawn)) await writeFile(join(svgs, name), svg)
}
if (generation.overview) {
  await writeFile(join(pagesFolder, 'index.html'), overviewOf(document.models, { style, font }))
}
for (const warning of document.warnings) process.stderr.write(`${warning}\n`)
