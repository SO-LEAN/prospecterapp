import { createFileRoute, useNavigate } from '@tanstack/react-router'
import { zodValidator } from '@tanstack/zod-adapter'
import { z } from 'zod'
import { GoogleAnswerPage } from '@/features/connectors/components/google-answer'

const searchSchema = z.object({
  code: z.string().optional().catch(undefined),
  state: z.string().optional().catch(undefined),
  error: z.string().optional().catch(undefined),
})

/** The redirect URI of the OAuth client of the instance: Google brings the member back here (ADR 0120). */
export const Route = createFileRoute('/_app/settings_/connectors/google')({
  validateSearch: zodValidator(searchSchema),
  component: GoogleAnswer,
})

function GoogleAnswer() {
  const answer = Route.useSearch()
  const navigate = useNavigate()

  return (
    <GoogleAnswerPage
      answer={answer}
      onDone={() => void navigate({ to: '/settings', search: { section: 'connectors' }, replace: true })}
    />
  )
}
