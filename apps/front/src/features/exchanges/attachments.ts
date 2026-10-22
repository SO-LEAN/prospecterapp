import { fetchClient } from '@/shared/api/client'
import type { Problem } from '@/shared/api/problem'
import { dataOf } from '@/shared/api/slices'
import type { ExchangeDetail } from './queries'

const ANY_FILE = 'application/octet-stream'

/**
 * The browser sends the file to the storage itself, with the form the API signed for it (ADR 0077): the one request
 * of the SPA out of the API, to S3. The upload is then joined to the exchange. A file heavier than the form takes is
 * refused before it is sent.
 */
export async function attachFile(exchangeId: string, file: File): Promise<ExchangeDetail> {
  const upload = dataOf(await fetchClient.POST('/api/uploads'))
  if (file.size > upload.heaviest) {
    throw refusalOf('attachmentTooHeavy', file)
  }
  const form = new FormData()
  for (const [name, value] of Object.entries(upload.fields)) {
    form.append(name, value)
  }
  form.append('Content-Type', file.type || ANY_FILE)
  form.append('file', file)
  const sent = await fetch(upload.url, { method: 'POST', body: form })
  if (!sent.ok) {
    throw refusalOf(sent.status === 400 ? 'attachmentTooHeavy' : 'attachmentNotSent', file)
  }

  return dataOf(
    await fetchClient.POST('/api/exchanges/{id}/attachments', {
      params: { path: { id: exchangeId } },
      body: { upload: upload.upload, name: file.name },
    }),
  )
}

/** A refusal shaped like those of the API, worded the same way (ADR 0073). */
function refusalOf(reason: 'attachmentTooHeavy' | 'attachmentNotSent', file: File): Problem {
  return {
    type: 'about:blank',
    status: 422,
    title: reason,
    detail: file.name,
    reason,
    violations: [{ propertyPath: 'file', title: reason, reason, context: { name: file.name } }],
  }
}

/**
 * The link of the API needs the access token: it answers a link signed for a minute, opened at once, which the
 * browser downloads under the name of the file.
 */
export async function downloadAttachment(exchangeId: string, attachmentId: string): Promise<void> {
  const download = dataOf(
    await fetchClient.GET('/api/exchanges/{id}/attachments/{attachmentId}', {
      params: { path: { id: exchangeId, attachmentId } },
    }),
  )
  window.location.assign(download.url)
}
