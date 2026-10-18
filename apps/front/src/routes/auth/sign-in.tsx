import { createFileRoute, useNavigate } from '@tanstack/react-router'
import { useTranslation } from 'react-i18next'
import { BrandMark } from '@/app/layout/brand'
import { SignInForm } from '@/features/sign-in/components/sign-in-form'
import { useSignIn } from '@/features/sign-in/mutations'
import type { SignInValues } from '@/features/sign-in/schema'
import { safeReturnTo } from '@/shared/auth/sign-in-page'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/shared/ui/card'

/**
 * The sign-in screen of the SPA (ADR 0037, 0052): an anonymous visitor is sent here with the page it asked
 * for, and goes back to it once signed in.
 */
export const Route = createFileRoute('/auth/sign-in')({
  validateSearch: (search: Record<string, unknown>) => ({ returnTo: safeReturnTo(search.returnTo) }),
  component: SignInPage,
})

function SignInPage() {
  const { t } = useTranslation()
  const signIn = useSignIn()
  const { returnTo } = Route.useSearch()
  const navigate = useNavigate()

  const signInThenGoOn = async (values: SignInValues) => {
    await signIn.mutateAsync(values)
    await navigate({ href: returnTo, replace: true })
  }

  return (
    <main className="grid min-h-dvh place-items-center bg-background p-6">
      <div className="grid w-full max-w-sm gap-6">
        <div className="flex flex-col items-center gap-2">
          <BrandMark className="size-20" />
          <span className="font-semibold text-2xl tracking-tight">{t('app.name')}</span>
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
