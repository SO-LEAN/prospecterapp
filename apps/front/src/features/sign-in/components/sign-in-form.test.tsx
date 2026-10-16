import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import { SignInForm } from './sign-in-form'

const refusal = (code: string, message = 'Refused') => ({ code, message })

function renderForm(onSubmit = vi.fn().mockResolvedValue(undefined)) {
  render(<SignInForm onSubmit={onSubmit} />)
  return { onSubmit, user: userEvent.setup() }
}

async function signIn(user: ReturnType<typeof userEvent.setup>, password = 'password') {
  await user.type(screen.getByLabelText('E-mail'), 'prospector@solean-it.io')
  await user.type(screen.getByLabelText('Mot de passe'), password)
  await user.click(screen.getByRole('button', { name: 'Se connecter' }))
}

describe('SignInForm', () => {
  it('sends the login email and the password', async () => {
    const { onSubmit, user } = renderForm()

    await signIn(user)

    expect(onSubmit).toHaveBeenCalledWith({ email: 'prospector@solean-it.io', password: 'password' })
  })

  it('checks the email before sending', async () => {
    const { onSubmit, user } = renderForm()

    await user.type(screen.getByLabelText('E-mail'), 'not an email')
    await user.click(screen.getByRole('button', { name: 'Se connecter' }))

    expect(await screen.findByText('Adresse e-mail invalide.')).toBeInTheDocument()
    expect(onSubmit).not.toHaveBeenCalled()
  })

  it('says the credentials are wrong without telling which', async () => {
    const { user } = renderForm(
      vi.fn().mockRejectedValue(refusal('NotAuthorizedException', 'Incorrect username or password.')),
    )

    await signIn(user, 'wrong password')

    expect(await screen.findByRole('alert')).toHaveTextContent('E-mail ou mot de passe incorrect.')
  })

  it('asks to wait after too many failed attempts', async () => {
    const { user } = renderForm(
      vi.fn().mockRejectedValue(refusal('NotAuthorizedException', 'Password attempts exceeded')),
    )

    await signIn(user)

    expect(await screen.findByRole('alert')).toHaveTextContent(
      'Trop de tentatives échouées : réessayez dans une minute.',
    )
  })
})
