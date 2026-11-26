import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { Field } from '@/shared/ui/form/field'
import { renderInARouter } from '@/test/render-in-a-router'
import { OrganizationChoice } from './organization-choice'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const ACME = '0190a1c0-0000-7000-8000-00000000a000'

/** The prospected organizations whose name starts with the searched text. */
function theApiFinds(organizations: unknown[]) {
  theApi.mockImplementation(async (input) => {
    const { pathname } = new URL((input as Request).url)
    return Response.json({ items: organizations, _links: { self: { href: pathname, method: 'GET' } } })
  })
}

const searched = () => theApi.mock.calls.map(([request]) => new URL((request as Request).url).search)

async function renderTheChoice(onChoose = vi.fn(), error?: string) {
  await renderInARouter(
    <QueryClientProvider client={new QueryClient()}>
      <Field label="Organisation" error={error}>
        <OrganizationChoice onChoose={onChoose} />
      </Field>
    </QueryClientProvider>,
  )
  return onChoose
}

afterEach(() => {
  theApi.mockReset()
})

describe('OrganizationChoice', () => {
  it('finds the prospected organizations by the start of their name; one is chosen in a click', async () => {
    theApiFinds([{ id: ACME, name: 'ACME', possibleDuplicates: [] }])
    const onChoose = await renderTheChoice()

    await userEvent.type(screen.getByRole('searchbox', { name: 'Organisation' }), 'AC')
    const acme = await screen.findByRole('button', { name: 'ACME' })
    expect(screen.queryByText(/Aucune organisation prospectée/)).not.toBeInTheDocument()
    await userEvent.click(acme)

    expect(searched()).toEqual(['?q=AC'])
    expect(onChoose).toHaveBeenCalledWith(ACME)
    expect(screen.getByText('ACME')).toBeInTheDocument()
    expect(screen.queryByRole('searchbox')).not.toBeInTheDocument()
  })

  it('chooses another organization once one is chosen', async () => {
    theApiFinds([{ id: ACME, name: 'ACME', possibleDuplicates: [] }])
    const onChoose = await renderTheChoice()
    await userEvent.type(screen.getByRole('searchbox'), 'AC')
    await userEvent.click(await screen.findByRole('button', { name: 'ACME' }))

    await userEvent.click(screen.getByRole('button', { name: 'Changer d’organisation' }))

    expect(onChoose).toHaveBeenLastCalledWith('')
    expect(screen.getByRole('searchbox', { name: 'Organisation' })).toHaveValue('')
  })

  it('shows the error of its field', async () => {
    await renderTheChoice(vi.fn(), 'Choisissez une organisation.')

    expect(screen.getByRole('searchbox', { name: 'Organisation' })).toHaveAccessibleDescription(
      'Choisissez une organisation.',
    )
    expect(screen.getByRole('searchbox')).toBeInvalid()
  })

  it('says when no prospected organization starts so, and offers to start prospecting one', async () => {
    theApiFinds([])
    const onChoose = await renderTheChoice()
    expect(screen.queryByText(/Aucune organisation prospectée/)).not.toBeInTheDocument()

    await userEvent.type(screen.getByRole('searchbox'), 'Zorglub')

    expect(
      await screen.findByText('Aucune organisation prospectée ne commence par « Zorglub ».'),
    ).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Commencer à prospecter' })).toHaveAttribute(
      'href',
      '/organizations/new',
    )
    expect(onChoose).not.toHaveBeenCalled()
  })
})
