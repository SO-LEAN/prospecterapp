import { type FormEvent, useId, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Button } from '@/shared/ui/button'
import { Input } from '@/shared/ui/input'
import { Label } from '@/shared/ui/label'
import { useSetDailyCap } from '../mutations'
import type { Campaign } from '../queries'

/**
 * A campaign's daily cap, changed on its sheet until the campaign is stopped or ended. It is a guard: an AI
 * stops at it, and the member may go beyond it. When the field is left empty, the campaign has no cap.
 */
export function DailyCapSetting({ campaign }: { campaign: Campaign }) {
  const { t } = useTranslation()
  const id = useId()
  const [typed, setTyped] = useState(campaign.dailyCap?.toString() ?? '')
  const setDailyCap = useSetDailyCap()

  if (!campaign._links.setDailyCap) {
    return null
  }

  const save = (event: FormEvent) => {
    event.preventDefault()
    setDailyCap.mutate({
      params: { path: { id: campaign.id } },
      body: { dailyCap: typed.trim() === '' ? null : Number(typed) },
    })
  }

  return (
    <form onSubmit={save} className="flex items-end gap-2">
      <div className="grid gap-1.5">
        <Label htmlFor={id}>{t('campaigns.dailyCap.label')}</Label>
        <Input
          id={id}
          type="number"
          min={1}
          inputMode="numeric"
          className="w-32"
          value={typed}
          onChange={(event) => setTyped(event.target.value)}
        />
      </div>
      <Button type="submit" variant="outline" disabled={setDailyCap.isPending}>
        {t('campaigns.dailyCap.save')}
      </Button>
    </form>
  )
}
