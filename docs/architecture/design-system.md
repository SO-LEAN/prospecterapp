# Design system

This document states the rules in force. The decision records (`docs/adr`) explain why: read them only for that, or before changing a rule (ADR 0090).

Modern SaaS style: cool neutrals, generous spacing, one blue accent, light and dark themes; the logo in navy and gold. Built on Tailwind CSS 4 and shadcn/ui.

## Principles

- Semantic tokens only: components use `bg-card`, `text-muted-foreground`, `border`, `bg-primary`…, never raw palette colors (`bg-blue-500`). Changing a token restyles the whole app, in both themes.
- Accessible by default: Radix primitives (focus management, keyboard, ARIA), visible focus rings, labels wired to inputs (`shared/ui/form/field.tsx`), sufficient contrast in both themes.
- Every clickable area shows the hand cursor, through the base rule in `app/styles.css` (buttons, the `button`, `menuitem*`, `option` and `tab` roles, `summary`, `label[for]`, checkboxes, radios, file inputs; `not-allowed` when disabled), never through a `cursor-pointer` on each component. A clickable element that is neither a button nor a link (a card, a row) is a `<button>`, a link, or carries `role="button"`.
- Calm hierarchy: one primary action per screen, secondary actions as `outline` or `ghost` buttons, titles in `text-2xl font-semibold tracking-tight`.
- Every async state is designed: skeletons (`PageSkeleton`), empty states (`EmptyState`), error states (`ErrorState`), toasts for outcomes.
- On a phone, the page is used at its own size: it never zooms (the `viewport` in `index.html`, `touch-action: pan-x pan-y` on every element, and Safari's gesture events cancelled by `shared/lib/keep-the-page-from-zooming.ts`); only the maps zoom. It never scrolls sideways either: a responsive grid starts with `grid-cols-1` (`minmax(0, 1fr)`), otherwise its implicit column takes the width of its longest truncated text.

## Tokens

Defined in `apps/front/src/app/styles.css` (`:root` and `.dark`) in OKLCH, and exposed to Tailwind with `@theme inline`.

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

Radius: `--radius` 0.75 rem (`rounded-lg`), cards `rounded-xl`. Shadow: `shadow-sm` on cards (`shared/ui/card.tsx`), and the softer `shadow-card` token on the organization cards in lists. Font: Inter Variable (self-hosted, `@fontsource-variable/inter`); numbers in stats use `tabular-nums`.

## Theme

`shared/stores/preferences.ts`: `light`, `dark` or `system` (follows the OS), persisted. A script in `index.html` applies the `dark` class before the first paint (no flash). Maps switch between the OpenFreeMap `positron` and `dark` styles.

## Components

- `shared/ui/*`: shadcn components (button, input, select, card, dialog, dropdown-menu, popover, sheet, tooltip, badge, avatar, skeleton, alert, separator, sonner…). They are project code: adapt them, but keep their API. Add new ones with `pnpm dlx shadcn@latest add <name>` (`components.json` makes it write into `shared/ui`), then fix the `cn` import if the generator gets it wrong.
- `shared/ui/form/`: `Field` (label + control + hint/error, pinned to the top of its cell so that the fields of a row stay aligned when one has a hint or an error), `CountrySelect` (French names, frequent countries first), `ImageUpload` (drag and drop, preview, client-side checks matching the API: JPEG/PNG/WebP, 2 MB).
- `shared/ui/feedback/`: `FullPageMessage`, `EmptyState`, `ErrorState` (a page's `errorComponent`: a failure offers « Réessayer »; a 403, for a role or a right the member lacks, is explained in French, naming the right when the API gives its `reason`, with nothing to retry), `NotFound`, `PageError` (a page that could not load at all because the API is unreachable: the root route's `errorComponent`).
- `shared/ui/map/geo-map.tsx`: a lazy MapLibre map with avatar markers, fitted to show all of them.
- `shared/ui/unlocated-badge.tsx`: "Non géolocalisée" next to an address the geocoder could not place (the `warning` token on the stroke and the icon, the text in `foreground` for contrast). `ExplainedUnlocatedBadge` explains why on hover, focus or press; `UnlocatedBadge` stands alone inside a link (a list card).
- `shared/ui/page-header.tsx`, `page-skeleton.tsx`.
- `app/layout/`: the app shell (collapsible sidebar, mobile sheet, sticky blurred header, user menu with a theme switch).

## Logo

A shovel digging up gold nuggets: `BrandMark` (`app/layout/brand.tsx`) draws it with the tokens `brand-ink` (navy, light in the dark theme) and `brand-gold*`. The same drawing is the front's `public/favicon.svg` (the tab icon, its ink following the system theme) and the API's (its doc icon, `App\Doc\ShowFavicon`): a logo change goes in all three. The PNG icons and `site.webmanifest` are generated from the SVG once, then committed:

```sh
docker compose run --rm --no-deps -w /var/www/src/apps/front/public node sh -c '
  x="npx --yes sharp-cli@6.1.0 --density 600 -i favicon.svg"
  $x -o favicon-32.png resize 32 32
  $x -o apple-touch-icon.png resize 148 148 -- extend 16 16 16 16 --background "#ffffff" -- flatten --background "#ffffff"
  $x -o icon-192.png resize 160 160 -- extend 16 16 16 16 --background "#ffffff" -- flatten --background "#ffffff"
  $x -o icon-512.png resize 428 428 -- extend 42 42 42 42 --background "#ffffff" -- flatten --background "#ffffff"'
```

## Icons and texts

lucide-react icons at `size-4` in buttons and `size-[18px]` in navigation, `aria-hidden` when next to a text. All texts go through i18n (`shared/i18n/fr.ts`).
