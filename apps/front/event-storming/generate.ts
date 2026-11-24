/**
 * Writes every context's wall from the document of the models the analysis read: its model and its page, and, for
 * the repository's documents, an SVG per module and one of the whole wall. With a context, only that context's wall.
 * node event-storming/generate.ts <document.json> <pages folder> <svgs folder> [context] (make event-storming,
 * docs/architecture/event-storming.md).
 */
import { mkdir, readdir, readFile, rm, writeFile } from 'node:fs/promises'
import { join } from 'node:path'
import { type Document, folderOf } from './model.ts'
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
const models = document.models.filter((model) => context === undefined || model.context === context)
if (models.length === 0) throw new Error(`No context ${context} in the code`)
for (const model of models) {
  const pages = join(pagesFolder, folderOf(model.context))
  const svgs = join(svgsFolder, folderOf(model.context))
  await mkdir(pages, { recursive: true })
  await writeFile(join(pages, 'model.json'), `${JSON.stringify(model, null, 4)}\n`)
  await writeFile(join(pages, 'index.html'), pageOf(model, { style, font }))
  await mkdir(svgs, { recursive: true })
  for (const gone of (await readdir(svgs)).filter((name) => name.endsWith('.svg'))) await rm(join(svgs, gone))
  for (const [name, svg] of Object.entries(svgsOf(model, font))) await writeFile(join(svgs, name), svg)
}
for (const warning of document.warnings) process.stderr.write(`${warning}\n`)
