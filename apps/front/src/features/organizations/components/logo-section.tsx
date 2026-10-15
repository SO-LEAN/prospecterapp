import { useTranslation } from 'react-i18next'
import { Card, CardContent, CardHeader, CardTitle } from '@/shared/ui/card'
import { ImageUpload } from '@/shared/ui/form/image-upload'
import { useChangeLogo, useRemoveLogo } from '../mutations'
import type { Organization } from '../queries'
import { OrganizationAvatar } from './organization-avatar'

export function LogoSection({ organization }: { organization: Organization }) {
  const { t } = useTranslation()
  const change = useChangeLogo(organization.id)
  const remove = useRemoveLogo()

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">{t('organizations.sections.logo')}</CardTitle>
      </CardHeader>
      <CardContent>
        <ImageUpload
          imageUrl={organization.logo?.thumbnailUrl}
          alt={organization.name}
          label={organization.logo ? t('organizations.logo.change') : t('organizations.logo.add')}
          pending={change.isPending || remove.isPending}
          onUpload={organization._links.changeLogo ? (file) => change.mutate(file) : undefined}
          onRemove={
            organization._links.removeLogo
              ? () => remove.mutate({ params: { path: { id: organization.id } } })
              : undefined
          }
          fallback={
            <OrganizationAvatar name={organization.name} className="size-full rounded-none border-0" />
          }
        />
      </CardContent>
    </Card>
  )
}
