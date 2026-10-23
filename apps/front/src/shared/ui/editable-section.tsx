import { Pencil } from 'lucide-react'
import { type ReactNode, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Button } from '@/shared/ui/button'
import { Card, CardAction, CardContent, CardHeader, CardTitle } from '@/shared/ui/card'

type Props = {
  title: string
  children: ReactNode
  editable: boolean
  editor: (close: () => void) => ReactNode
  className?: string
}

/**
 * A card showing one part of a resource, changed in place through its own small form when `editable`:
 * when the API offers the action (its link is there).
 */
export function EditableSection({ title, children, editable, editor, className }: Props) {
  const { t } = useTranslation()
  const [editing, setEditing] = useState(false)
  const close = () => setEditing(false)

  return (
    <Card className={className}>
      <CardHeader>
        <CardTitle className="text-base">{title}</CardTitle>
        {editable && !editing && (
          <CardAction>
            <Button
              variant="ghost"
              size="sm"
              onClick={() => setEditing(true)}
              aria-label={t('common.editSection', { section: title })}
            >
              <Pencil /> {t('common.edit')}
            </Button>
          </CardAction>
        )}
      </CardHeader>
      <CardContent className="text-sm">{editing && editable ? editor(close) : children}</CardContent>
    </Card>
  )
}
