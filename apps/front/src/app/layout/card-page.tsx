import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/shared/ui/card'
import { BrandMark } from './brand'

type Props = { title: string; description: string; children: ReactNode }

/** A page of one card under the brand, outside the shell of the application: signing in, completing a profile. */
export function CardPage({ title, description, children }: Props) {
  const { t } = useTranslation()

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
              <h1 className="text-xl">{title}</h1>
            </CardTitle>
            <CardDescription>{description}</CardDescription>
          </CardHeader>
          <CardContent>{children}</CardContent>
        </Card>
        <footer className="text-center text-muted-foreground text-xs">
          {t('signIn.copyright', { year: new Date().getFullYear() })}
        </footer>
      </div>
    </main>
  )
}
