import { useTranslation } from 'react-i18next'
import { ImageUpload } from '@/shared/ui/form/image-upload'
import { useRemoveOrganizationLogo, useUploadOrganizationLogo } from '../mutations'
import type { Organization } from '../queries'
import { OrganizationAvatar } from './organization-avatar'

export function OrganizationLogo({ organization }: { organization: Organization }) {
  const { t } = useTranslation()
  const upload = useUploadOrganizationLogo(organization.id)
  const remove = useRemoveOrganizationLogo()

  return (
    <ImageUpload
      imageUrl={organization.logo?.thumbnailUrl}
      alt={organization.corporateName ?? ''}
      label={organization.logo ? t('organizations.logo.change') : t('organizations.logo.add')}
      pending={upload.isPending || remove.isPending}
      onUpload={(file) => upload.mutate(file)}
      onRemove={() => remove.mutate({ params: { path: { id: String(organization.id) } } })}
      fallback={
        <OrganizationAvatar name={organization.corporateName} className="size-full rounded-none border-0" />
      }
    />
  )
}
