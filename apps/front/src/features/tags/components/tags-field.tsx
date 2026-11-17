import { TagsLine } from './tags-line'

type Props = { value: string[]; onChange: (tags: string[]) => void }

/** The tags chosen in a creation form, put once what they tag is created. */
export function TagsField({ value, onChange }: Props) {
  return (
    <TagsLine
      tags={value}
      onTag={(typed) => onChange(value.includes(typed) ? value : [...value, typed])}
      onTakeOff={(tag) => onChange(value.filter((chosen) => chosen !== tag))}
    />
  )
}
