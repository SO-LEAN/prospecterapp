import { Link } from '@tanstack/react-router'
import { X } from 'lucide-react'
import { type KeyboardEvent, useState } from 'react'
import {
  type FieldValues,
  get,
  type Path,
  type UseFormReturn,
  useController,
  useFormState,
} from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'
import { Button } from '@/shared/ui/button'
import { SourceMark, shortAddress } from '@/shared/ui/external-links'
import { Input } from '@/shared/ui/input'
import { FormField } from './form-field'

/** The addresses of the links of a sheet, recognized and checked by the API. */
export const linksSchema = z.array(z.string())

const KNOWN_HOSTS: [RegExp, string][] = [
  [/^(?:[a-z]{2,3}\.)?linkedin\.com$/, 'linkedin'],
  [/^malt\.fr$/, 'malt'],
  [/^welcometothejungle\.com$/, 'welcometothejungle'],
]

/** The source an address seems to be of, by its host, for its mark before the API recognizes it. */
function presumedSourceOf(address: string): string {
  const host =
    address
      .replace(/^https?:\/\//i, '')
      .replace(/^www\./i, '')
      .split(/[/?#]/)[0]
      ?.toLowerCase() ?? ''

  return KNOWN_HOSTS.find(([pattern]) => pattern.test(host))?.[1] ?? host
}

/**
 * The links of a person or an organization: a field to paste an address, added on Enter or by its button, and a line
 * per link with the mark of its source and a button taking it off.
 */
export function LinksField<Values extends FieldValues>({
  form,
  name,
}: {
  form: UseFormReturn<Values>
  name: Path<Values>
}) {
  const { t } = useTranslation()
  const { field } = useController({ control: form.control, name })
  const links: string[] = field.value ?? []
  const [pasted, setPasted] = useState('')

  const add = () => {
    const address = pasted.trim()
    if (address === '') return
    field.onChange([...links, address])
    setPasted('')
  }

  const addOnEnter = (event: KeyboardEvent<HTMLInputElement>) => {
    if (event.key !== 'Enter') return
    event.preventDefault()
    add()
  }

  return (
    <div className="grid content-start gap-2 sm:col-span-2">
      <span className="font-medium text-sm">{t('fields.links')}</span>
      {links.length > 0 && (
        <ul className="grid gap-1">
          {links.map((address) => (
            <li key={address} className="flex items-center gap-1.5 text-sm">
              <SourceMark source={presumedSourceOf(address)} />
              <span className="min-w-0 truncate">{shortAddress(address)}</span>
              <Button
                type="button"
                variant="ghost"
                size="icon"
                className="size-7"
                aria-label={t('fields.removeLink', { link: shortAddress(address) })}
                onClick={() => field.onChange(links.filter((link) => link !== address))}
              >
                <X className="size-4" aria-hidden />
              </Button>
            </li>
          ))}
        </ul>
      )}
      <div className="flex items-start gap-2">
        <FormField
          form={form}
          name={name}
          label={t('fields.addLink')}
          optional={t('common.optional')}
          hint={t('fields.linksHint')}
          className="flex-1"
        >
          <Input
            inputMode="url"
            autoComplete="url"
            value={pasted}
            onChange={(event) => setPasted(event.target.value)}
            onKeyDown={addOnEnter}
            onBlur={add}
          />
        </FormField>
        <Button type="button" variant="outline" onClick={add} className="mt-6">
          {t('fields.add')}
        </Button>
      </div>
      <HolderOfTheLink form={form} name={name} />
    </div>
  )
}

/** The contact holding the link refused, named by the API, a link to their sheet. */
function HolderOfTheLink<Values extends FieldValues>({
  form,
  name,
}: {
  form: UseFormReturn<Values>
  name: Path<Values>
}) {
  const { t } = useTranslation()
  const { errors } = useFormState({ control: form.control, name })
  const holder: { holder?: string; holderId?: string } | undefined = get(errors, name)?.types

  if (!holder?.holder || !holder.holderId) {
    return null
  }

  return (
    <Link
      to="/contacts/$contactId"
      params={{ contactId: holder.holderId }}
      className="text-primary text-xs hover:underline"
    >
      {t('fields.linkHolder', { holder: holder.holder })}
    </Link>
  )
}
