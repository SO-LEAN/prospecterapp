# Design system

Modern SaaS style: cool neutrals, generous spacing, one blue accent, light and dark themes. Built on Tailwind CSS 4 and shadcn/ui.

## Principles

- Semantic tokens only: components use `bg-card`, `text-muted-foreground`, `border`, `bg-primary`… never raw palette colors (`bg-blue-500`). Changing a token restyles the whole app, both themes.
- Accessible by default: Radix primitives (focus management, keyboard, ARIA), visible focus rings, labels wired to inputs (`shared/ui/form/field.tsx`), sufficient contrast in both themes.
- Calm hierarchy: one primary action per screen, secondary actions as `outline` or `ghost` buttons, titles `text-2xl font-semibold tracking-tight`.
- Every async state is designed: skeletons (`PageSkeleton`), empty states (`EmptyState`), error states (`ErrorState`), toasts for outcomes.

## Tokens

Defined in `apps/front/src/app/styles.css` (`:root` and `.dark`) in OKLCH, exposed to Tailwind with `@theme inline`. The sign-in page of the authorization server (`apps/auth/templates/auth/login.html.twig`) copies the same values: keep them in sync.

| Token | Use |
|---|---|
| `background` / `foreground` | page |
| `card`, `popover` | raised surfaces |
| `primary` (blue, hue 262) | main actions, active navigation, links |
| `secondary`, `muted`, `accent` | quiet surfaces, hints, icon backgrounds |
| `destructive`, `success`, `warning` | outcomes |
| `border`, `input`, `ring` | strokes and focus |
| `sidebar*` | navigation panel |
| `chart-1..5` | data visualisation |

Radius: `--radius` 0.75 rem (`rounded-lg`), cards `rounded-xl`. Shadow: `shadow-card`. Font: Inter Variable (self-hosted, `@fontsource-variable/inter`), numbers in stats use `tabular-nums`.

## Theme

`shared/stores/preferences.ts`: `light`, `dark` or `system` (follows the OS), persisted. A script in `index.html` applies the `dark` class before the first paint (no flash). Maps switch between the OpenFreeMap `positron` and `dark` styles.

## Components

- `shared/ui/*`: shadcn components (button, input, select, card, dialog, dropdown-menu, sheet, tooltip, badge, avatar, skeleton, alert, separator, sonner…). They are project code: adapt them, keep their API. Add new ones with `pnpm dlx shadcn@latest add <name>` (configured by `components.json` to write into `shared/ui`), then fix the `cn` import if the generator gets it wrong.
- `shared/ui/form/`: `Field` (label + control + hint/error), `CountrySelect` (French names, frequent countries first), `ImageUpload` (drag and drop, preview, client-side checks matching the API: JPEG/PNG/WebP, 2 MB).
- `shared/ui/feedback/`: `FullPageMessage`, `EmptyState`, `ErrorState`, `NotFound`.
- `shared/ui/map/geo-map.tsx`: lazy MapLibre map with avatar markers that fits all of them.
- `shared/ui/page-header.tsx`, `page-skeleton.tsx`.
- `app/layout/`: app shell (collapsible sidebar, mobile sheet, sticky blurred header, user menu with theme switch).

## Icons and texts

lucide-react icons at `size-4` in buttons and `size-[18px]` in navigation, `aria-hidden` when next to a text. All texts go through i18n (`shared/i18n/fr.ts`).
