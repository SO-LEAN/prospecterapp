import { Link } from '@tanstack/react-router'
import { useTranslation } from 'react-i18next'
import { Button } from '@/shared/ui/button'
import { FullPageMessage } from './full-page-message'

export function NotFound() {
  const { t } = useTranslation()

  return (
    <FullPageMessage title={t('errors.notFound')} description={t('errors.notFoundDescription')}>
      <Button asChild>
        <Link to="/">{t('errors.backHome')}</Link>
      </Button>
    </FullPageMessage>
  )
}
