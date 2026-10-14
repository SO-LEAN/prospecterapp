import { Link, type LinkProps, useCanGoBack, useRouter } from '@tanstack/react-router'
import { ArrowLeft } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { Button } from '@/shared/ui/button'

/**
 * Goes back to the previous page of the application (dashboard, filtered list...), as the browser
 * back button does; opened from a bookmark or a shared link, goes to `fallback` instead.
 */
export function BackButton({ fallback }: { fallback: LinkProps['to'] }) {
  const { t } = useTranslation()
  const router = useRouter()
  const canGoBack = useCanGoBack()
  const className = '-ml-2 text-muted-foreground'

  if (canGoBack) {
    return (
      <Button variant="ghost" size="sm" className={className} onClick={() => router.history.back()}>
        <ArrowLeft /> {t('common.back')}
      </Button>
    )
  }

  return (
    <Button variant="ghost" size="sm" className={className} asChild>
      <Link to={fallback}>
        <ArrowLeft /> {t('common.back')}
      </Link>
    </Button>
  )
}
