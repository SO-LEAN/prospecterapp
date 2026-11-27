import { CircleCheckIcon, InfoIcon, Loader2Icon, OctagonXIcon, TriangleAlertIcon } from 'lucide-react'
import { useEffect } from 'react'
import { useTranslation } from 'react-i18next'
import { Toaster as Sonner, type ToasterProps, toast } from 'sonner'
import { useResolvedTheme } from '@/shared/stores/preferences'

const Toaster = ({ toastOptions, ...props }: ToasterProps) => {
  const { t } = useTranslation()
  const theme = useResolvedTheme()
  useEscapeClosingTheToasts()

  return (
    <Sonner
      theme={theme}
      className="toaster group"
      toastOptions={{ ...toastOptions, closeButtonAriaLabel: t('common.close') }}
      icons={{
        success: <CircleCheckIcon className="size-4" />,
        info: <InfoIcon className="size-4" />,
        warning: <TriangleAlertIcon className="size-4" />,
        error: <OctagonXIcon className="size-4" />,
        loading: <Loader2Icon className="size-4 animate-spin" />,
      }}
      style={
        {
          '--normal-bg': 'var(--popover)',
          '--normal-text': 'var(--popover-foreground)',
          '--normal-border': 'var(--border)',
          '--border-radius': 'var(--radius)',
        } as React.CSSProperties
      }
      {...props}
    />
  )
}

/** Escape closes the toasts, as it closes the dialogs and the menus. Closing one undoes nothing. */
function useEscapeClosingTheToasts() {
  useEffect(() => {
    const closeOnEscape = (event: KeyboardEvent) => {
      if (event.key === 'Escape') toast.dismiss()
    }
    document.addEventListener('keydown', closeOnEscape)
    return () => document.removeEventListener('keydown', closeOnEscape)
  }, [])
}

export { Toaster }
