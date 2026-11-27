import { useTranslation } from 'react-i18next'
import { Button } from '@/shared/ui/button'
import { QUICK_DURATIONS } from '../planning'

type Props = { chosen: number | undefined; onChoose: (minutes: number) => void }

/** The usual durations, one click each. The chosen duration is pressed. */
export function QuickDurations({ chosen, onChoose }: Props) {
  const { t } = useTranslation()

  return (
    <div className="flex flex-wrap gap-2">
      {QUICK_DURATIONS.map((minutes) => {
        const pressed = chosen === minutes
        return (
          <Button
            key={minutes}
            type="button"
            size="sm"
            variant={pressed ? 'secondary' : 'outline'}
            aria-pressed={pressed}
            onClick={() => onChoose(minutes)}
          >
            {t(`exchanges.durations.${minutes}`)}
          </Button>
        )
      })}
    </div>
  )
}
