import type { ComponentProps } from 'react'
import { useTranslation } from 'react-i18next'
import { Input } from '@/shared/ui/input'
import { takingTheLinkOfAnInvitation } from '../video-call-link'

type Props = Omit<ComponentProps<typeof Input>, 'type' | 'onPaste'> & {
  /** The link of an invitation pasted whole, in place of the invitation. */
  onLink: (link: string) => void
}

/** The link of a video call, typed, or taken from an invitation pasted whole. */
export function VideoCallLinkInput({ onLink, ...input }: Props) {
  const { t } = useTranslation()

  return (
    <Input
      type="url"
      inputMode="url"
      autoComplete="off"
      placeholder={t('exchanges.linkPlaceholder')}
      {...input}
      onPaste={takingTheLinkOfAnInvitation(onLink)}
    />
  )
}
