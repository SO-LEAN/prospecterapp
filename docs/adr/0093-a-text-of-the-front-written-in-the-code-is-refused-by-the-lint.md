# 0093. A text of the front written in the code is refused by the lint, and the libraries speak through the translations

- Date: 2026-11-05
- Type: architecture
- Status: Accepted
- Implemented by: `apps/front/lint/translated-texts.grit` and its test, `apps/front/biome.json`, `shared/ui/map/map-view.tsx`, `shared/ui/sonner.tsx`

## Context

The front had the rule « every text through `t()` », kept by review only. An analysis of `apps/front/src` found texts written in the code (the formats under the drop zone of an image, the example of the legal form), and English texts that our code never sees, shown or announced by the libraries: the help and the controls of the maps of MapLibre, the cross of the notifications of Sonner. Nothing kept a new one out.

## Decision

- A plugin of Biome (GritQL, `apps/front/lint/translated-texts.grit`, `make lint`) refuses in `src/`, out of the tests, a text containing a letter written in the code where it is shown or announced: the text of JSX, a literal child, a literal in `title`, `aria-label`, `placeholder`, `alt` or `label`, a notification of `toast`, the branches of a condition or a fallback included. What is never shown (an error thrown, a log, a class, a key) is not concerned. Its test lints components with the rule alone, so that a change of the syntax of Biome cannot leave it matching nothing.
- A library showing or announcing texts gets them from `fr.ts`: MapLibre by its `locale`, Sonner by the `closeButtonAriaLabel` of its notifications, a component of shadcn added by its props.

## Consequences

- A text written in a screen fails `make lint`, naming the file, the line and the rule.
- The rule reads the syntax, not the sense: a text built by a function of our code, or a new attribute announcing a text, escapes it; the list of the attributes grows with them.
- A new version of a library may add a text in English: its identifiers in the library (`src/ui/default_locale.ts` of MapLibre) are checked at its upgrade.

## Alternatives considered

None weighed: the ticket chose a plugin of Biome, already installed, over a review.

## Revisit when

A second language arrives, or Biome offers a rule of its own on the texts written in JSX.
