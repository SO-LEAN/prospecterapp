import { createFileRoute } from '@tanstack/react-router'
import { useTranslation } from 'react-i18next'
import { BrandMark } from '@/app/layout/brand'
import { SignInForm } from '@/features/sign-in/components/sign-in-form'
import { useSignIn } from '@/features/sign-in/mutations'
import type { SignInValues } from '@/features/sign-in/schema'
import { userManager } from '@/shared/auth/user-manager'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/shared/ui/card'

/**
 * The authorization server sends an anonymous authorization request here (ADR 0037). Once signed in, the
 * interrupted request is opened again and goes on to the code; opened directly, the SPA starts one.
 */
export const Route = createFileRoute('/auth/sign-in')({
  component: SignInPage,
})

const goOnWithTheAuthorization = async (resume: string | null) => {
  if (resume) {
    window.location.assign(resume)
    return
  }
  await userManager.signinRedirect()
}

function SignInPage() {
  const { t } = useTranslation()
  const signIn = useSignIn()

  const signInThenGoOn = async (values: SignInValues) => {
    const signedIn = await signIn.mutateAsync({ body: values })
    await goOnWithTheAuthorization(signedIn.resume)
  }

  return (
    <main className="grid min-h-dvh place-items-center bg-background p-6">
      <div className="grid w-full max-w-sm gap-6">
        <div className="flex items-center justify-center gap-2 font-semibold text-lg tracking-tight">
          <BrandMark className="size-9" />
          Prospecter
        </div>
        <Card>
          <CardHeader>
            <CardTitle>
              <h1 className="text-xl">{t('signIn.title')}</h1>
            </CardTitle>
            <CardDescription>{t('signIn.subtitle')}</CardDescription>
          </CardHeader>
          <CardContent>
            <SignInForm onSubmit={signInThenGoOn} />
          </CardContent>
        </Card>
        <footer className="text-center text-muted-foreground text-xs">
          {t('signIn.copyright', { year: new Date().getFullYear() })}
        </footer>
      </div>
    </main>
  )
}
