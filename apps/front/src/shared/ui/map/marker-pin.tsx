import { initials } from '@/shared/lib/format'

type Props = { label: string; imageUrl?: string | null }

export function MarkerPin({ label, imageUrl }: Props) {
  return (
    <span className="grid size-9 place-items-center overflow-hidden rounded-full border-2 border-background bg-primary font-semibold text-primary-foreground text-xs shadow-lg ring-primary/30 transition group-hover:scale-110 group-focus-visible:ring-4">
      {imageUrl ? <img src={imageUrl} alt="" className="size-full object-cover" /> : initials(label)}
    </span>
  )
}
