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
import { shortAddress } from '@/shared/ui/external-links'
import { Input } from '@/shared/ui/input'
import { FormField } from './form-field'

/** A sheet's link addresses, which the API recognizes and checks. */
export const linksSchema = z.array(z.string())

/**
 * A person's or an organization's links. A field takes a pasted address, added on Enter or with its button. Each link
 * has a line with a button that removes it. The API recognizes the links' sources, which show once saved.
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
    if (!links.includes(address)) field.onChange([...links, address])
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

/** The contact who holds the refused link, as the API names them, with a link to their sheet. */
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
