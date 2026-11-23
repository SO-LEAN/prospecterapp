import type { ComponentProps } from 'react'
import { useTranslation } from 'react-i18next'
import { Input } from '@/shared/ui/input'
import { takingTheLinkOfAnInvitation } from '../video-call-link'

type Props = Omit<ComponentProps<typeof Input>, 'type' | 'onPaste'> & {
  /** Gets the link of a whole pasted invitation, which replaces the invitation. */
  onLink: (link: string) => void
}

/** A video call's link, typed or taken from a whole pasted invitation. */
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
