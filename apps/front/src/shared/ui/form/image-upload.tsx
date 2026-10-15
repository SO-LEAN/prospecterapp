import { ImageUp, Loader2, Trash2 } from 'lucide-react'
import { type DragEvent, useRef, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { cn } from '@/shared/lib/utils'
import { Button } from '@/shared/ui/button'

const ACCEPTED = ['image/jpeg', 'image/png', 'image/webp']
const MAX_SIZE = 2 * 1024 * 1024

type Props = {
  imageUrl: string | null | undefined
  alt: string
  label: string
  onUpload?: (file: File) => void
  onRemove?: () => void
  pending?: boolean
  shape?: 'square' | 'circle'
  fallback?: React.ReactNode
}

/**
 * Preview + drag and drop or click to upload; the file is checked before being sent (same rules as the API).
 * Without `onUpload` (the API does not offer it), only the preview.
 */
export function ImageUpload({
  imageUrl,
  alt,
  label,
  onUpload,
  onRemove,
  pending = false,
  shape = 'square',
  fallback,
}: Props) {
  const { t } = useTranslation()
  const input = useRef<HTMLInputElement>(null)
  const [dragging, setDragging] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const pick = (file: File | undefined) => {
    if (!file) {
      return
    }
    if (!ACCEPTED.includes(file.type) || file.size > MAX_SIZE) {
      setError(t('errors.imageInvalid'))
      return
    }
    setError(null)
    onUpload?.(file)
  }

  const onDrop = (event: DragEvent) => {
    event.preventDefault()
    setDragging(false)
    pick(event.dataTransfer.files[0])
  }

  const frame = cn(
    'relative grid size-24 shrink-0 place-items-center overflow-hidden border bg-muted',
    shape === 'circle' ? 'rounded-full' : 'rounded-xl',
  )
  const preview = imageUrl ? <img src={imageUrl} alt={alt} className="size-full object-cover" /> : fallback

  if (!onUpload) {
    return <div className={frame}>{preview}</div>
  }

  return (
    <div className="flex items-center gap-5">
      <button
        type="button"
        onClick={() => input.current?.click()}
        onDragOver={(event) => {
          event.preventDefault()
          setDragging(true)
        }}
        onDragLeave={() => setDragging(false)}
        onDrop={onDrop}
        disabled={pending}
        aria-label={label}
        className={cn(
          frame,
          'group transition hover:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
          dragging && 'border-primary ring-4 ring-primary/20',
        )}
      >
        {preview}
        <span className="absolute inset-0 grid place-items-center bg-foreground/50 text-background opacity-0 transition group-hover:opacity-100 group-focus-visible:opacity-100">
          {pending ? <Loader2 className="size-5 animate-spin" /> : <ImageUp className="size-5" />}
        </span>
        {pending && (
          <span className="absolute inset-0 grid place-items-center bg-background/70">
            <Loader2 className="size-5 animate-spin text-primary" />
          </span>
        )}
      </button>
      <div className="space-y-2">
        <div className="flex flex-wrap gap-2">
          <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={() => input.current?.click()}
            disabled={pending}
          >
            {pending ? t('common.uploading') : label}
          </Button>
          {onRemove && (
            <Button
              type="button"
              variant="ghost"
              size="sm"
              onClick={onRemove}
              disabled={pending}
              className="text-muted-foreground"
            >
              <Trash2 /> {t('common.remove')}
            </Button>
          )}
        </div>
        <p className={cn('text-xs', error ? 'text-destructive' : 'text-muted-foreground')}>
          {error ?? 'JPEG, PNG, WebP · 2 Mo max'}
        </p>
      </div>
      <input
        ref={input}
        type="file"
        accept={ACCEPTED.join(',')}
        className="sr-only"
        tabIndex={-1}
        onChange={(event) => {
          pick(event.target.files?.[0])
          event.target.value = ''
        }}
      />
    </div>
  )
}
