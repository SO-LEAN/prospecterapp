# Design system

This document states the rules in force. The decision records (`docs/adr`) explain why: read them only for that, or before changing a rule (ADR 0090).

The art direction is « Le Filon »: warm paper, deep ink, petrol for the primary, and gold kept for what has value. Each color has a job: it says a channel, a status or a kind of day, never decoration. The style has generous spacing, and light and dark themes. The logo is a gold nugget on a petrol tile. The design system is built on Tailwind CSS 4 and shadcn/ui.

## Principles

- Semantic tokens only: components use `bg-card`, `text-muted-foreground`, `border`, `bg-primary`…, never raw palette colors (`bg-blue-500`). Changing a token restyles the whole app, in both themes.
- Accessible by default: Radix primitives (focus management, keyboard, ARIA), visible focus rings, labels wired to inputs (`shared/ui/form/field.tsx`), sufficient contrast in both themes.
- Every clickable area shows the hand cursor through the base rule in `app/styles.css`, never through a `cursor-pointer` on each component. The rule covers buttons, the `button`, `menuitem*`, `option` and `tab` roles, `summary`, `label[for]`, checkboxes, radios and file inputs. It shows `not-allowed` when they are disabled. A clickable element that is neither a button nor a link (a card, a row) is a `<button>`, a link, or carries `role="button"`.
- Calm hierarchy: one primary action per screen, secondary actions as `outline` or `ghost` buttons, titles in `text-2xl font-semibold tracking-tight`.
- Every async state is designed: skeletons (`PageSkeleton`), empty states (`EmptyState`), error states (`ErrorState`), toasts for outcomes.
- On a phone, the page is used at its own size. It never zooms. Only the maps zoom. The `viewport` in `index.html` and `touch-action: pan-x pan-y` on every element prevent the zoom, and `shared/lib/keep-the-page-from-zooming.ts` cancels Safari's gesture events. The page never scrolls sideways either. A responsive grid starts with `grid-cols-1` (`minmax(0, 1fr)`). Otherwise its implicit column takes the width of its longest truncated text.

## Tokens

The tokens are defined in OKLCH in `apps/front/src/app/styles.css` (`:root` and `.dark`). `@theme inline` exposes them to Tailwind.

| Token | Use |
|---|---|
| `background` (paper) / `foreground` (ink) | page |
| `card`, `popover` (white) | raised surfaces |
| `primary` (petrol) | main actions, active navigation, selection |
| `link` (the primary) | links and e-mail addresses |
| `secondary`, `muted`, `accent` | quiet surfaces, hints, icon backgrounds |
| `destructive`, `success`, `warning` | outcomes |
| `border`, `input`, `ring` | strokes and focus |
| `gold`, `gold-soft`, `gold-foreground` | what has value: the « Nouveau » button, the active page, the action to take. Never a background fill. The text on the gold is always `gold-foreground`, the gold's ink |
| `event-call`, `event-meeting`, `event-video`, `event-written`, `event-todo`, each with its `-soft` background | the family of an exchange, by its channel (calls, meetings, video calls, the written channels), and the to-dos |
| `day-weekend`, `day-holiday`, `day-holiday-soft`, `day-vacation`, `day-vacation-soft` | the calendar's context. It never carries an event on its own |
| `sidebar*` | navigation panel |
| `chart-1..5` | data visualisation |

The light theme takes its values from the UI/UX audit's palette. Two rules hold for every token in `:root`, and `app/styles.test.ts` checks them with the values:

- No hue between 250° and 330°: no violet, no magenta. The hue is the HSL hue of the sRGB color, as the audit's palette gives it in hexadecimal. In OKLCH, the video calls' blue would sit at 259°.
- Body text, muted text, links, and the text on the main buttons, the accent, the danger color and the gold keep a contrast of at least 4.5:1 on their surface. That is why `muted-foreground` is #5E6D7A rather than the audit's #6B7A87, which reads 4.0:1 on the paper.

Radius: `--radius` 0.75 rem (`rounded-lg`), cards `rounded-xl`. Shadow: `shadow-sm` on cards (`shared/ui/card.tsx`), and the softer `shadow-card` token on the organization cards in lists. Font: Inter Variable (self-hosted, `@fontsource-variable/inter`). Numbers in stats use `tabular-nums`.

## Theme

`shared/stores/preferences.ts` keeps the theme: `light`, `dark` or `system` (which follows the OS). The choice is persisted. A script in `index.html` applies the `dark` class before the first paint (no flash). The native controls (check boxes, radios, scroll bars, date fields) follow the app's theme, not the system's: `styles.css` sets `color-scheme` on `:root` and `.dark`. The `color-scheme` and `theme-color` metas follow it too. The script sets them before the first paint, and `useThemeClass` keeps them in sync. Maps switch between the OpenFreeMap `positron` and `dark` styles.

## Components

- `shared/ui/*`: the components written by hand, and the shadcn components (button, input, select, card, dialog, dropdown-menu, popover, sheet, tooltip, badge, avatar, skeleton, alert, separator, sonner…). They are project code: adapt them, but keep their API. Add new ones with `pnpm dlx shadcn@latest add <name>` (`components.json` makes it write into `shared/ui`), then fix the `cn` import if the generator gets it wrong. Add the new component to `apps/front/mutation/generated-files`, so that the mutation leaves it out. Take a component out of that list when you write a line of it by hand (`docs/architecture/tests.md`).
- `shared/ui/form/`: `Field` (label + control + hint/error, pinned to the top of its cell so that a row's fields stay aligned when one has a hint or an error), `CountrySelect` (French names, frequent countries first), `ImageUpload` (drag and drop, preview, client-side checks matching the API: JPEG/PNG/WebP, 2 MB).
- `shared/ui/feedback/`:
  - `FullPageMessage`, `EmptyState`;
  - `ErrorState`, a page's `errorComponent`. A failure offers « Réessayer ». A 403, for a role or a right the member lacks, is explained in French, with nothing to retry. The explanation names the right when the API gives its `reason`;
  - `NotFound`;
  - `PageError`, for a page that could not load at all because the API is unreachable. It is the root route's `errorComponent`.
- `shared/ui/map/geo-map.tsx`: a lazy MapLibre map with avatar markers, fitted to show all of them.
- `shared/ui/unlocated-badge.tsx`: "Non géolocalisée" next to an address that the geocoder could not place. It uses the `warning` token on the stroke and the icon, and `foreground` on the text for contrast. `ExplainedUnlocatedBadge` explains why on hover, focus or press. `UnlocatedBadge` stands alone inside a link (a list card).
- `shared/ui/page-header.tsx`, `page-skeleton.tsx`.
- `app/layout/`: the app shell (collapsible sidebar, mobile sheet, sticky blurred header, user menu with a theme switch).

## Logo

The logo is a white vein that climbs towards a gold nugget, on a petrol tile (`#0E7C84`, nugget `#F5B32E`, the ground under the vein white at 18 % opacity). It has one version, which reads on paper and on ink: its colors are fixed and follow no theme. Only the word « Prospecter » beside it, text in the app's font, takes the theme's foreground. `BrandMark` (`app/layout/brand.tsx`) draws it. The same drawing is the front's `public/favicon.svg`, the tab icon. It is also the API's doc icon (`App\Doc\ShowFavicon`). A logo change goes in all three. `site.webmanifest` takes the tile's color as its `theme_color`. The PNG icons are generated from the SVG once, then committed:

```sh
docker compose run --rm --no-deps -w /var/www/src/apps/front/public node sh -c '
  x="npx --yes sharp-cli@6.1.0 --density 600 -i favicon.svg"
  $x -o favicon-32.png resize 32 32
  $x -o apple-touch-icon.png resize 148 148 -- extend 16 16 16 16 --background "#ffffff" -- flatten --background "#ffffff"
  $x -o icon-192.png resize 160 160 -- extend 16 16 16 16 --background "#ffffff" -- flatten --background "#ffffff"
  $x -o icon-512.png resize 428 428 -- extend 42 42 42 42 --background "#ffffff" -- flatten --background "#ffffff"'
```

## Icons and texts

The icons come from lucide-react. They are `size-4` in buttons and `size-[18px]` in navigation, and `aria-hidden` when next to a text. All texts go through i18n (`shared/i18n/fr.ts`).
