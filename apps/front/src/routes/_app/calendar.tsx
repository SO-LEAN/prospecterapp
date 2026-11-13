import { createFileRoute, useNavigate } from '@tanstack/react-router'
import { zodValidator } from '@tanstack/zod-adapter'
import { z } from 'zod'
import { CalendarPage } from '@/features/exchanges/calendar/components/calendar-page'
import { CalendarSkeleton } from '@/features/exchanges/calendar/components/calendar-skeleton'
import { dayOf, isDay, parseDay, periodOf, VIEWS, type View } from '@/features/exchanges/calendar/dates'
import { calendarQuery } from '@/features/exchanges/calendar/queries'
import { defaultView } from '@/features/exchanges/calendar/view'
import { ErrorState } from '@/shared/ui/feedback/error-state'

const searchSchema = z.object({
  view: z.enum(VIEWS).optional().catch(undefined),
  date: z.string().refine(isDay).optional().catch(undefined),
})

type Shown = { view: View; day: Date }

/** What the URL says, else the view of the screen on today. */
const shownBy = ({ view, date }: z.infer<typeof searchSchema>): Shown => ({
  view: view ?? defaultView(),
  day: parseDay(date ?? ''),
})

/** My calendar, its view and its day in the URL: the back button and a reload keep them. */
export const Route = createFileRoute('/_app/calendar')({
  validateSearch: zodValidator(searchSchema),
  loaderDeps: ({ search }) => search,
  loader: ({ context, deps }) => {
    const { view, day } = shownBy(deps)
    return context.queryClient.ensureQueryData(calendarQuery(periodOf(view, day)))
  },
  pendingComponent: () => <CalendarSkeleton />,
  errorComponent: ({ error, reset }) => <ErrorState error={error} reset={reset} />,
  component: Calendar,
})

function Calendar() {
  const { view, day } = shownBy(Route.useSearch())
  const navigate = useNavigate({ from: Route.fullPath })

  return (
    <CalendarPage
      view={view}
      day={day}
      onShow={(shownView, shownDay) => navigate({ search: { view: shownView, date: dayOf(shownDay) } })}
    />
  )
}
