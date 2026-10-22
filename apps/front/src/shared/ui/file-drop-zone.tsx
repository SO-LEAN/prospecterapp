import { Upload } from 'lucide-react'
import { type DragEvent, useId, useState } from 'react'
import { cn } from '@/shared/lib/utils'

type Props = {
  /** The files dropped or chosen, several at once. */
  onFiles: (files: File[]) => void
  /** What to do, before the button choosing the files: "Déposez des fichiers ici, ou". */
  invitation: string
  /** The button choosing the files. */
  choose: string
  hint?: string
  disabled?: boolean
}

/** Files dropped on it or chosen with the file picker of the system, keyboard reachable through its button. */
export function FileDropZone({ onFiles, invitation, choose, hint, disabled = false }: Props) {
  const input = useId()
  const [dragging, setDragging] = useState(false)

  const receive = (files: FileList | null) => {
    if (files && files.length > 0 && !disabled) {
      onFiles([...files])
    }
  }
  const drop = (event: DragEvent) => {
    event.preventDefault()
    setDragging(false)
    receive(event.dataTransfer.files)
  }

  return (
    <fieldset
      className={cn(
        'flex flex-col items-center gap-1 rounded-md border border-dashed px-4 py-4 text-center text-muted-foreground text-sm transition-colors',
        dragging && 'border-primary bg-accent',
        disabled && 'opacity-50',
      )}
      onDragOver={(event) => {
        event.preventDefault()
        setDragging(true)
      }}
      onDragLeave={() => setDragging(false)}
      onDrop={drop}
    >
      <Upload aria-hidden className="size-5" />
      <p>
        {invitation}{' '}
        <label
          htmlFor={input}
          className="cursor-pointer font-medium text-primary underline-offset-4 hover:underline has-focus-visible:underline"
        >
          {choose}
          <input
            id={input}
            type="file"
            multiple
            disabled={disabled}
            className="sr-only"
            onChange={(event) => {
              receive(event.target.files)
              event.target.value = ''
            }}
          />
        </label>
      </p>
      {hint && <p className="text-xs">{hint}</p>}
    </fieldset>
  )
}
