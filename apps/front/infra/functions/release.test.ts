import { readFileSync } from 'node:fs'
import { describe, expect, it } from 'vitest'

interface Request {
  uri: string
  headers: Record<string, { value: string }>
}

interface Answer {
  statusCode?: number
  uri?: string
}

type Handler = (event: { request: Request }) => Answer

/** The function as Terraform writes it for an instance (cloudfront.tf), run as CloudFront runs it. */
function releaseFunctionOf(servesTheDocuments: boolean): Handler {
  const template = readFileSync('infra/functions/release.js.tftpl', 'utf8')
  const values: Record<string, string> = {
    version: '0123456789ab',
    config: '{}',
    origin_secret_digest: '',
    serves_the_documents: String(servesTheDocuments),
  }
  const code = template
    .replace("import crypto from 'crypto'", '')
    .replace(/\$\{(\w+)\}/g, (_placeholder, name: string) => values[name] ?? '')
  return new Function(`${code}\nreturn handler`)() as Handler
}

function asked(uri: string): { request: Request } {
  return { request: { uri, headers: {} } }
}

describe('the function choosing the release of the front', () => {
  it('answers a document of a folder by its index.html, on an instance that serves the documents', () => {
    const answer = releaseFunctionOf(true)(asked('/docs/event-storming/booking/'))

    expect(answer.uri).toBe('/releases/0123456789ab/docs/event-storming/booking/index.html')
  })

  it('does not find a document on an instance that does not serve them', () => {
    const answers = [
      '/docs/event-storming/booking/',
      '/releases/0123456789ab/docs/event-storming/booking/model.json',
    ].map((uri) => releaseFunctionOf(false)(asked(uri)).statusCode)

    expect(answers).toEqual([404, 404])
  })

  it('still answers a page of the SPA by its index.html', () => {
    const answer = releaseFunctionOf(false)(asked('/organizations'))

    expect(answer.uri).toBe('/releases/0123456789ab/index.html')
  })
})
