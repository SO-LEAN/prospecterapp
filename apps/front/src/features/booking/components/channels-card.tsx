import { useTranslation } from 'react-i18next'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/shared/ui/card'
import { Checkbox } from '@/shared/ui/checkbox'
import { Label } from '@/shared/ui/label'
import { type BookingPage, useOfferChannels } from '../mutations'

const CHANNELS = ['call', 'videoCall'] as const
type Channel = (typeof CHANNELS)[number]

type Props = { page: BookingPage }

/** The channels I offer. Each one is saved as soon as it is checked or unchecked. */
export function ChannelsCard({ page }: Props) {
  const { t } = useTranslation()
  const offerChannels = useOfferChannels()
  const offered = page.channels as Channel[]

  const toggle = (channel: Channel, checked: boolean) => {
    const channels = CHANNELS.filter((one) => (one === channel ? checked : offered.includes(one)))
    offerChannels.mutate({ body: { channels } })
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">{t('booking.channels.title')}</CardTitle>
        <CardDescription>{t('booking.channels.description')}</CardDescription>
      </CardHeader>
      <CardContent className="flex flex-wrap gap-6 text-sm">
        {CHANNELS.map((channel) => (
          <div key={channel} className="flex items-center gap-2">
            <Checkbox
              id={`channel-${channel}`}
              checked={offered.includes(channel)}
              disabled={!page._links.offerChannels || offerChannels.isPending}
              onCheckedChange={(checked) => toggle(channel, checked === true)}
            />
            <Label htmlFor={`channel-${channel}`}>{t(`booking.channels.${channel}`)}</Label>
          </div>
        ))}
      </CardContent>
    </Card>
  )
}
