# The event storming page as a wall

A design proposal for the generated event storming (ticket 284 and its follow-up). It comes with `mockup.html`, a standalone prototype that draws the real Booking `model.json` with this design. Open it in a browser: it loads nothing from the network.

Useful links for the mockup: `mockup.html?theme=dark`, `mockup.html#lane=Appointment`, `mockup.html#at=command:BookSlot`, `mockup.html#at=actor:mcp`, `mockup.html?view=text`, `mockup.html?q=slot`.

## 1. What is wrong with the current page

I opened `../../../apps/front/public/docs/event-storming/booking/index.html` in Chrome (screenshots: `current-overview.png`, `current-bookingpage.png`) and read `views.ts`, `layout.ts` and `page.ts`.

The extraction is good. The page is a correct graph. It is not an event storming wall yet.

| # | Problem | Why it hurts |
|---|---|---|
| 1 | **It is a node-and-arrow diagram.** Every link is an orthogonal arrow. The BookingPage view draws 16 arrows from 2 actors to 8 use cases, and 6 arrows into one pink box. | On a real wall there are almost no arrows. Adjacency carries the grammar: the actor sticks on the command, the command touches the aggregate, the event touches the aggregate. The spaghetti hides the 8 simple stories this module holds. |
| 2 | **Actors are full-size boxes on the left.** | In Brandolini's grammar the actor is a small yellow sticky on the corner of the command. A column of actors turns into a fan of arrows as soon as a role does more than three things. In Prospecting it would be unreadable. |
| 3 | **External systems are hubs.** `Hosts` sits in the middle of the commands' column and receives six arrows. | The reader sees a hub and thinks it is the centre of the module. It is a lookup. |
| 4 | **No timeline.** ELK orders the layers by graph depth. The page reads top to bottom, in one tall column. | Event storming is a timeline, left to right. A reader who knows the method looks for "what happens first" and finds no answer. |
| 5 | **Labels mix two languages and two registers.** Commands carry the French feature title, events carry a raw class name (`InactivePeriodRemoved`), policies too (`ForgetAppointmentOfErasedExchangePolicy`, wider than its box). | Two different commands get the same label: `OpenBooking` and `CloseBooking` both read "Activer et couper ma prise de rendez-vous", `AddInactivePeriod` and `RemoveInactivePeriod` both read "Ajouter une période inactive". The label lies about the intent. |
| 6 | **Flat rectangles.** 176 × 104 boxes, 3 px radius, a hairline border. | Nothing says "post-it". The colours are right, the object is wrong: a post-it is square, leans, casts a shadow, and is handwritten. |
| 7 | **Views are tabs that swap the whole board.** | The reader loses the place. The overview and the module views are different drawings of the same things, so nothing learned in one helps in the other. Cross-module links show as faded copies with no way to go to the original. |
| 8 | **Hover lights one hop.** | The question is "what happens when someone books a slot?". The answer is a chain of five post-its over two modules and two contexts. One hop does not show it. |
| 9 | **No zoom, no pan, no search, no link to a post-it.** A scroll box holds the SVG. | Fine for Booking (32 post-its). Prospecting has about 37 commands and 55 events. |
| 10 | **The filter removes boxes and their arrows.** | The chains break and the layout keeps the holes. A filter on a wall should make things step back, not vanish. |
| 11 | **Foreign post-its are dashed outlines.** `IdentifyBookerPolicy` (PersonalData) and `ExchangeFactsChanged` (Prospecting) look "disabled". | They are the most interesting post-its of the wall: where this context meets the others. Nothing invites the reader to go there. |
| 12 | **Accessibility is thin.** Each post-it is focusable, but the SVG is one `role="img"`, the tab order follows ELK's node order, and the `<title>` tooltip is the only text alternative. | A keyboard or screen-reader user gets a list of 60 unlabeled stops. |
| 13 | **Things the model knows and the page hides.** The aggregate's method (`via`), the published flag, the conditional link's reason, the port's adapter. | They are in `model.json` and only in a hover title, or nowhere. |

Things to keep: the canonical colours, light and dark, the dashed line for a conditional link, one copy of the aggregate per chain, the standalone page with no outside script, the determinism ("two generations give the same file").

## 2. The target: one wall per context

### 2.1 The wall metaphor

One context is **one roll of paper taped on a wall**. The page is that wall and a camera.

- The room is a neutral wall. The roll is off-white paper with a faint grain, two strips of masking tape at its top corners, and a soft shadow. In dark mode the lights are off: the paper is slate, the marker is chalk-white, the post-its keep their colours a little dimmed.
- The context's name is written in marker at the top left. A long marker arrow labelled **time** runs along the top.
- Each **module is a swimlane**, separated by a marker line. Its name is written on a strip of masking tape at the left. Clicking the tape frames the lane.
- The camera pans by drag or scroll, zooms by pinch or Ctrl + scroll (the Figma convention, so a trackpad scroll never zooms by accident). A minimap in the corner shows the wall "from the door", with the viewport as a rectangle you can drag.
- **Semantic zoom.** Under 46 % the small print disappears (method names, "whenever…", actor names, stamps) and the lane tapes grow, as when you step back from a wall: you read colours and titles. Step closer and the detail comes back.

This replaces the tabs. "Overview" is the wall seen whole. "A module" is the camera on a lane. They are the same drawing, so the reader never loses the place.

### 2.2 Visual grammar

Post-its are **square** (132 px at real size), lean by a few tenths of a degree, lift at the bottom-right corner, have a glue band at the top, and are written in a handwriting font (Patrick Hand, OFL, a 22 kB Latin subset embedded as a data URI, so the page still loads nothing). The lean comes from a hash of the post-it's id: the same post-it leans the same way at each generation.

| Post-it | Look | Text | Where it sits |
|---|---|---|---|
| Event | orange | past tense, sentence case: `Slot booked` | right of the aggregate, touching it |
| Published event | orange, with a red ink stamp `PUBLISHED` | same | right of the policy or the command that publishes it |
| Command | blue | imperative: `Open booking` | the anchor of a step |
| Aggregate | pale yellow, slightly behind | noun: `Booking page`, and the method in small monospace: `open()` | between its command and its event, overlapped by both |
| Policy | lilac | small "whenever slot booked", then `Publish slot booked` | after the event it hears, joined by a marker arrow |
| Read model | green | the noun the query presents: `My booking page` | first in its lane: the actor looks, then decides |
| External system | pink, a narrow strip | the port: `Hosts`, and "asks Accounts · PersonalData" | tucked under the command or the read model that asks it |
| Actor | small bright yellow (70 px) with a **stick figure** | `Member`, `Member's AI`, `Anyone with the link` | on the top-left corner of each command or read model it can send |
| Hotspot | magenta, tilted about 10° | `?! No event recorded` | on the step that raises the question |
| Another context | a torn piece of **kraft paper** under the post-it, labelled with the context | | at the edge of the step that touches it |

Three stick figures: a person; a person with a square head and an antenna for the member's AI; a person with a second one behind for "anyone". They read at 30 px.

**A step is a cluster, not a row of boxes.** Who asks (actors), the command, what it changes (aggregate), what happened (events), and what it needed to ask (ports) touch each other. This is the picture Brandolini calls "the picture that explains everything". It needs no arrow.

**Arrows are for reactions only**: event → policy, policy → command, policy → published event's listeners. They are drawn in marker, slightly wobbly, under the post-its. A conditional one is dotted and carries a handwritten "maybe". On Booking this takes the drawing from 53 arrows to 4.

### 2.3 From class names to sticky text (wish 1)

The label comes from the intent name in the code, never from the French feature title.

1. Split the class name on case changes: `RemoveInactivePeriod` → `Remove inactive period`. Sentence case, because that is how people write on post-its.
2. Keep a short list of acronyms in capitals (`AI`, `MCP`, `API`, `URL`, `ID`, `PDF`, `CSV`, `SIRET`…).
3. By type:
   - command and event: the name as is. The code's naming rule already gives an imperative and a past participle.
   - policy: drop the `Policy` or `Handler` suffix. Prefix with a small "whenever <event label>", built from its links.
   - read model: the noun the output port presents (`detail`: `MyBookingPage` → `My booking page`); without it, the query's name minus the leading `Consult` (`ConsultAppointments` → `Appointments`). This also removes "consult", a false friend the style guide lists.
   - aggregate: the name, and the method called (`via`) under it.
   - external system: the port's name. The contexts or the outside service that answer go on a second line.
   - actor: a table in the generator, keyed by the role: `ROLE_USER` → `Member`, `mcp` → `Member's AI`, `public` → `Anyone with the link`. An unknown role falls back to its name in sentence case.
4. The French feature title stays one click away, in the post-it's card, marked `lang="fr"`. The search reads it too: typing "créneau" finds `Book slot`.

The generator should do this in PHP or TypeScript once, and write the result in the model as `label`, with the feature title moved to its own field (`feature`). Then a test can pin every label.

### 2.4 Layout on the timeline

The layout is a deterministic grid. It needs no graph layout engine for the post-its.

- **Lanes**: one per module, in the order of causality (a module that hears another sits below it), then the model's order. Use cases at the application's root get a lane named after the context.
- **Steps**: in a lane, steps stand left to right. First the read models an actor looks at, then the commands, then the steps a reaction starts. A step that waits for an event of this wall stands to the right of that event, whatever its lane. So every arrow points right, and time never runs backwards.
- **A reaction that only sends one command** is drawn in line before it: foreign event (on kraft) → policy → command.
- **A reaction that publishes** gets its own step: policy, then the published event touching it, then the other contexts' listeners on kraft.
- **Each step has its own copy of the aggregate** (kept from the current page). Each use case has its own copies of its actors and ports. Hovering one copy lights all of them.
- **The order of independent commands in a lane is not in the model.** The mockup uses the model's order (alphabetical). See section 4 for the data that would make it a real story.

`views.ts` becomes "lanes and steps", `layout.ts` becomes the grid, and both stay pure functions that Vitest pins. ELK is no longer needed for placing. For Prospecting, keep it or a small channel router only if the reaction arrows cross too much (see 2.11).

### 2.5 Jump pictograms (wish 4), and coming back

A small round sticker on the corner of a post-it says "the story goes on elsewhere".

| Pictogram | On | Hover text | Click |
|---|---|---|---|
| → (paper-white) | an event heard in another lane | `Heard by "Publish slot booked" · Appointment` | the camera flies there and pins that post-it |
| ← (paper-white) | a policy that hears an event of another lane | `Hears "Slot booked" · PublicLink` | flies back to it |
| ↗ (kraft) | a post-it on kraft paper | `On PersonalData's wall — go to "Identify booker" there` | opens `../personal-data/#at=policy:IdentifyBookerPolicy` |
| ↗ (kraft, small) | a port answered by another context | `Accounts answers "Hosts" — go to its wall` | opens `../accounts/` |

The tooltip shows on hover and on keyboard focus. `J` follows the focused post-it's first pictogram.

**Every move is a history entry with a deep link**:

- `#` the whole wall, `#lane=PublicLink` a lane, `#at=command:BookSlot` a post-it (pinned, its story lit, its card open). A foreign post-it is `#at=Prospecting/published:ExchangeFactsChanged`.
- The browser's Back button and the toolbar's Back button (Alt + ←) undo a jump, across contexts too, since each wall is a page with its own hash.
- The breadcrumb reads `Event storming › Booking ▾ › [Whole wall | Booking page | Public link | Appointment]`. The context menu lists the seven contexts and the big picture.
- "Copy the link to this post-it" in the card, for a ticket or a pull request.

A stable short id per post-it (`command:BookSlot`) should be written by the generator. Today the ids are fully qualified class names, too long for a link.

### 2.6 Hover, focus and pin: follow one story

- **Hover or focus** a post-it: its whole story lights, upstream and downstream, across lanes: actor → command → aggregate → event → policy → next command or published event → the other context's policy. Everything else steps back (20 % opacity, desaturated). Lit post-its lift a little.
- Hover an **actor**: every copy lights, with everything that actor can do. Same for a **port**: everything that asks it. The legend holds one chip per actor for the same thing ("light everything the member's AI can do").
- **Click or Enter** pins the story and opens the post-it's **card**: type, label, class, module, the French feature title, what comes before, what comes then (each a link that flies there), the source file on GitHub, the step told as a sentence, and the link to copy. Click again, click the paper, or Esc lets go.

### 2.7 Search, filters, legend

- **Search** (`/`): matches labels, class names, feature titles and details, word by word. Results list the type and the lane. While typing, the matches glow on the wall and the rest steps back. Enter flies to the first.
- **Legend** = "how to read the wall": one chip per type, in the order of the grammar. Each chip is also the **filter**: a type switched off fades to a ghost. It does not leave the wall, so the clusters keep their shape.
- Arrow samples: always, only sometimes.
- The legend folds (`L`).

### 2.8 The wall as text

"Read as text" (`T`) tells the same wall in sentences, lane by lane:

> Anyone with the link asks to **Book slot**, which asks Connected calendars, Host agendas, Hosts. Booking page records **Slot booked**.
> Whenever **Slot booked** (PublicLink), **Publish slot booked** publishes **Slot booked**. Personal data hears it: **Identify booker**.

Each name is a coloured chip that flies to its post-it. This is the accessible alternative, the print-friendly version, and a good review tool: a wrong sentence is a wrong model. In the real page this list should be in the static HTML, so it reads without JavaScript.

### 2.9 Keyboard and accessibility

- The wall is one tab stop (roving `tabindex`). Arrow keys move to the nearest post-it in that direction. The camera follows when focus leaves the view.
- Each post-it is a `button` with a full name: `Command: Book slot. Module PublicLink. After Anyone with the link. Then Connected calendars, Host agendas, Hosts, Booking page.`
- Keys: `/` search, `F` whole wall, `1` real size, `+` `−`, `Enter` pin, `J` jump, `Alt+←` back, `T` text, `L` legend, `D` lights, `?` help, `Esc` let go.
- Colour never carries the meaning alone: position in the cluster, the actor's figure, the policy's "whenever", the stamp, the kraft paper and the accessible name all repeat it.
- Text on post-its is dark ink on every colour, in both themes (the post-its do not invert). The main text measures between 8:1 and 14:1 on the seven canonical colours in light, 6.8:1 or more in dark, and about 5:1 on the magenta hotspot. The small grey captions are lower and need a check.
- `prefers-reduced-motion` turns the camera flights into cuts.
- Tooltips appear on focus as well as on hover.

### 2.10 Light, dark, print, export

- **Theme**: follows the system, the toolbar button overrides it and remembers it. `?theme=dark` forces it in a link.
- **Print**: the chrome disappears, the wall is laid whole on one A3 landscape sheet, colours kept (`print-color-adjust: exact`). For a large context, one lane per sheet. Print to PDF is the vector export. No library needed.
- **Export**: the text view can be copied as Markdown for a ticket. A PNG export would need a canvas renderer: not worth it while PDF exists.

### 2.11 Performance for Prospecting

Estimate for Prospecting (about 37 commands, 55 events, 28 reactions, about 30 queries): some 420 post-its and 60 arrows, on a wall about 6 000 px wide once #283 splits it into modules.

- Post-its are absolutely positioned `div`s. One CSS transform on their parent moves the camera. 420 nodes is small for the compositor. No virtualisation needed.
- Positions are computed at generation time and written in the HTML. The inline script only handles the camera, the hover, the search and the history. Budget: under 15 kB of script, under 250 kB per page with the font.
- Hover toggles two classes on the wall and one per lit post-it. The story of each post-it can be precomputed at generation.
- The paper grain is one 220 px SVG tile. No large filter runs at pan time.
- **The risk is arrows, not post-its.** 28 reactions in one context can cross. Three defences, in order: (1) the ordering rule that puts a listener right of its event keeps arrows short and pointing right; (2) an arrow that would cross more than one lane or run more than about 1 500 px is drawn as two **stubs**, one leaving the event and one entering the policy, each ending in a jump pictogram (the "off-page connector" of a paper wall); the full arrow appears when its story is lit; (3) only then, route the remaining ones in the gutters between lanes.

### 2.12 What stays generated, what the layout decides

| Generated from the code (model) | Decided by the layout (page generator) | Never by hand |
|---|---|---|
| The post-its, their type, context, module | Lane order, step order, positions | Any post-it or link |
| English labels and short ids | Which reaction is in line, which gets its own step | Any coordinate |
| Links, their condition, the aggregate's method | Which arrows are stubs | |
| Feature titles, source paths | The lean of each post-it (a hash of its id) | |
| Hotspots (rules on the model) | Font size by label length | |

The generator's rule stays: two generations of the same code give the same files.

## 3. Ideas ranked by value and cost

Value and cost: S, M, L. "Data" says whether `model.json` holds what is needed today.

| # | Idea | Value | Cost | Data |
|---|---|---|---|---|
| 1 | Steps as clusters; arrows only for reactions | L | M | yes |
| 2 | English labels from intent names; feature title in the card | L | S | yes (actor labels need a role table) |
| 3 | Real post-its: square, paper, lean, shadow, handwriting font | L | S | yes |
| 4 | Actors as small stickies with stick figures on each use case | L | S | yes |
| 5 | One wall per context, modules as swimlanes on a timeline | L | M | yes |
| 6 | Zoom, pan, minimap, semantic zoom | L | M | yes |
| 7 | Story highlight end to end, pin, post-it card | L | S | yes |
| 8 | Jump pictograms, deep links, history, breadcrumb | L | S inside a wall, M across contexts | needs the other contexts' pages and short ids |
| 9 | Kraft paper for other contexts' post-its | M | S | yes |
| 10 | Search over labels, classes and feature titles | M | S | yes |
| 11 | The wall as text, in the static HTML | M | S | yes |
| 12 | Roving keyboard navigation, full accessible names | M | S | yes |
| 13 | Legend as the filter; per-actor highlight | M | S | yes |
| 14 | Generated hotspots (command without event, published service nobody is seen calling, use case without feature, feature without use case) | M | S | partly: the first two today, the tags check is in the ticket's draft |
| 15 | Print on A3, one lane per sheet | S | S | yes |
| 16 | Arrow stubs for long reactions | M for Prospecting | M | yes |
| 17 | Narrative order of the steps in a lane | L | M | **no** (see 4.1) |
| 18 | Read models placed after the events that feed them, before the command they inform | M | L | **no** (see 4.2) |
| 19 | Refusals as small notes on the command ("refused when the link is taken") | M | M | **no** (see 4.3) |
| 20 | Real outside systems (Google, Gmail, Cognito) told apart from other contexts | M | S | **partly** (see 4.4) |
| 21 | The clock as an actor for scheduled commands | S | M | **no** (see 4.5) |
| 22 | Event payload in the card | S | M | **no** |
| 23 | Big picture: seven small walls and the published events between them | L | L | needs every context's model and `subscriptions.json` |
| 24 | "Group by aggregate" toggle (commands left, events right of one big yellow sheet) | S | M | yes |
| 25 | PNG export | S | M | yes |

Suggested first lot: 1 to 7, 9, 11, 12. It needs no new data and delivers the four wishes inside one context. Second lot: 8 across contexts, 10, 13 to 16, with the other contexts' pages. Then the data items.

## 4. Data the model does not hold yet

1. **Narrative order.** Nothing says that a member sets windows before opening the booking. Options, cheapest first: the order of the scenarios' feature files; a `@step-N` tag beside the use case tag; an order file per module. Without it, the layout keeps causal order between steps and the model's order inside. The page must say so, or the reader will read a story into alphabetical order.
2. **Read model feeds.** The model links an actor to a read model, and a read model to its ports. It does not say which events change what the read model shows, nor which command a screen leads to. The first could be read from the repositories a query handler takes. The second lives in the front.
3. **Refusals and rules.** The core has `Domain/Rule` and refusals. A collector could link a command to the refusals it can return. They are the "business rule" notes of a design-level storming.
4. **Kind of external system.** `label` holds the contexts an adapter reads, as free text (`Accounts, PersonalData`). The mockup parses it against the list of contexts to draw the jump pictograms. The model should hold `answeredBy: [{ context }]` or `{ outside: "Google Calendar" }`.
5. **Triggers without a member.** A console command or a scheduler that sends a command is not an actor today.
6. **Who calls a published service.** `ConsultAppointments` has no actor and no caller in the model, hence its hotspot in the mockup.
7. **Short ids and both labels.** `id` is the class name. Add `slug` (`command:BookSlot`), keep `label` for the English text and move the feature title to `feature`.
8. **Published event version and subscription** (`SlotBooked.v1`, the subscription's name): in `subscriptions.json`, not in the model.

## 5. What the mockup shows, and what it fakes

It shows, on the real Booking model: the wall, the three lanes, the clusters, the stick figures, the marker arrows, the kraft paper, the jump pictograms with their tooltips, the story highlight, the pin and the card, the deep links and Back, the search, the legend as a filter, the per-actor highlight, the text view, the minimap, the semantic zoom, light and dark, the keyboard, a first print stylesheet, and two generated hotspots.

It fakes or leaves out:

- **Jumps to another context** show a message with the URL the real page would open. Only Booking exists.
- **The English labels and the actor names** are computed in the browser. The real generator writes them in the model.
- **The layout runs in the browser** from the embedded JSON. The real one runs at generation and is tested.
- **The step order** inside a lane is the model's order (section 4.1).
- **The print** fits the wall on one sheet roughly. It is not tuned.
- I checked it in headless Chrome only (light, dark, pinned, text view, narrow window). Firefox and Safari, touch, and a screen reader are untested.

It also changes one rule of `../../architecture/event-storming.md`: "a view per module" becomes "a lane per module on one wall". That is a decision for a record.

## 6. The top 10 changes for the ticket

1. **One wall per context**, on a left-to-right timeline, with one swimlane per module. The tabs go. "Overview" and "module" become camera positions with deep links (`#lane=…`).
2. **Steps are clusters of touching post-its** (actor, command, aggregate, event, ports). Arrows remain only for reactions, in marker, dotted with "maybe" when conditional. Booking goes from 53 arrows to 4.
3. **Every label in English, from the intent name**: sentence case, `Policy`/`Handler`/`Consult` stripped, "whenever <event>" on policies, a role table for actors. The French feature title moves to the card and stays searchable.
4. **Post-its that look like post-its**: square, paper gradient, glue band, lifted corner, a lean derived from the id, an embedded handwriting font, the canonical colours, a `PUBLISHED` stamp on contract events.
5. **Actors as small yellow stickies with a stick figure** on the corner of each use case they send, with a distinct figure for the member's AI and for anyone.
6. **Jump pictograms** with a tooltip on hover and focus: inside the wall (event ↔ the policy that hears it), and to another context's wall for post-its on kraft paper and for ports. Every jump is a history entry, so Back returns.
7. **Follow a story end to end** on hover or focus; click to pin it and open the post-it's card (class, module, feature, before, then, source, link to copy). Hovering an actor or a port lights every place it appears.
8. **Zoom, pan, minimap and semantic zoom**, sized for Prospecting (about 420 post-its as plain positioned elements, positions computed at generation, long arrows cut into stubs with pictograms).
9. **Search, legend-as-filter and the wall as text**: `/` finds by label, class or feature title; a filtered type fades instead of vanishing; "Read as text" tells each lane in sentences and is the accessible and printable form. Roving keyboard focus and full accessible names come with it.
10. **Model additions**: a short id and an English label per post-it, the feature title in its own field, who answers each port as data, generated hotspots; then, when wanted, the narrative order of the steps, the refusals, and the callers of published services.

## 7. Iteration 1: only a cause moves a step to the right

The maintainer's review: in the first mockup, the independent use cases of the "Booking page" lane stood one after another. The horizontal axis is time, so they read as a sequence they do not have. This iteration replaces the step order of section 2.4. The mockup and the screenshots in `shots` follow it.

### The layout rule

1. **Independent steps stack in rows**, one under the other, inside their module's lane. They all start at the lane's left edge. A row says nothing about order.
2. **Only a real cause moves a step to the right**: a step that a reaction starts stands right of the event it hears. Inside a step, command → aggregate → event still read left to right, and a foreign event → policy → command chain too.
3. **A step that hears an event of its own lane** joins the row of that event, to its right. The row then reads as one story.
4. **A step that hears an event of another lane** takes the first row of its lane that is free at that place.

So the left column is "what can start a story", and everything right of it is a consequence. On Booking, `Publish slot booked` is the only step of this wall that moves: it stands right of `Slot booked`.

### The order of the rows in a lane

Top to bottom:

1. the steps another lane starts (their arrow comes from above and crosses no row);
2. each human actor's group: its read models in the left column, its commands **by aggregate, then by intent name**;
3. the commands and read models no human actor sends (the AI's alone, if any), by the same order;
4. the steps a reaction of this wall starts, from another context's event;
5. the read models no actor is seen reading (published services);
6. last, the rows whose event a lower lane hears (their arrow leaves downwards and crosses no row).

Booking has one aggregate, so rule 3 gives the alphabetical order there. A narrative order still needs data the model does not hold (section 4.1), and it would only sort the rows: it would not put them back on the time axis.

### Where the actors stand

The first mockup put a stick figure on every use case. The canonical grammar says more: a person **looks at a read model, then decides, then sends a command**.

- **A human actor stands once per lane**, on the right edge of the read model it reads there. One marker line leaves it and branches to each command it sends in that lane. The lane reads: read model → actor → its commands, stacked → what each one changes → what happened.
- **The member's AI is the exception.** It calls the use cases through its MCP tools and looks at no screen. It keeps a **small round badge** with its figure on the corner of each command and read model it can call. Hovering a badge lights all of them. The generator knows such actors by a list (`mcp` today).
- **Which read model?** The model links actors to use cases, not a read model to the commands decided from it. The rule is the lane: the actor stands by the read models it reads in the module, and all the commands it sends in that module hang from there. With several read models in a lane, they stack in the left column and the actor stands by the first. This is honest for a module that is one screen, and an approximation otherwise (section 4.2 names the missing data).
- **A command with no read model to stand by.** Nothing is invented. When the actor reads nothing in the lane, it stands alone at the head of its commands and the read model's place stays empty: the hole is the information. A command that only the AI or a reaction sends has no human actor at all and stacks under the groups. Booking has no such case, so the mockup's code path for it is written and not exercised.
- A command that two human actors send hangs from the first one's group and is linked to the second (not exercised on Booking either).

With the rows, a group shares its rows: row 1 holds the read model, the actor and the first command; the next commands stack under the first.

### Staying readable at Prospecting's size

Rows make the wall tall instead of wide: Booking is about 1 650 × 2 750 px. Prospecting would be about 70 rows spread over its modules.

- **Collapsible lanes.** A round button under each lane's tape folds the lane to one band: its tape, its count, and its post-its as a row of small coloured squares, grouped by step. Arrows still reach the squares, and a folded square still lights with its story. The folded lanes are in the link (`#fold=BookingPage,PublicLink`).
- **Semantic zoom** is unchanged: from far, colours and lane titles only.
- The first look fits the wall's width and starts at the top. A lane's button in the toolbar frames the lane, top-aligned when it is taller than the window.
- The minimap fits tall walls.
- Later, if rows of one aggregate are many: draw one aggregate sheet for the group instead of one copy per row (idea 24).

### What changed in the mockup

`place()` replaces the single-row placement, and runs again when a lane folds. The time arrow stays at the top. Nothing else of the design changed. Section 2.11's width estimate (6 000 px) no longer holds: the wall is now as wide as its longest causal chain.

## 8. Iteration 2: the actor opens the view, and the guess is shown as a guess

### What changed

1. **Actor → read model → commands.** The human actor now stands on the left edge of the read model it reads: it opens the view, reads it, then acts. The marker line to the commands leaves from the read model's right edge. This replaces "on the right edge" in section 7. The member's AI keeps its badge.
2. **The aggregate's method is never clipped.** It wraps at the capitals of its name, inside the post-it (`addInactive` / `Period()`). A driver check measures every method label against its post-it, on Booking and on a fixture with a 32-character name.
3. **The guess is drawn as a guess.** The first stroke of the line, from the read model to the branch, is dotted and carries a handwritten "?". The command's card has a "Decided from" row with the read model, a "?", the actor it comes from, and a sentence that says what the model knows and what it does not.
4. **An actor with no read model in its lane** stands at the same place with a magenta hotspot "Reads what?" where the view would be. Iteration 1 left that place empty; a hotspot is the wall's own way to say "something is missing here", so I changed my mind. Its line is solid: nothing is guessed, the actor does send those commands.
5. **A command that several people send** hangs from the first one's group. The others each have their own line to it, on a spine a little to the left, so the lines do not merge. Pinning the command lights all its senders.
6. **Compact rows** (`C`, or the "Compact" button, `#rows=compact` in the link): what a use case asks becomes a row of small pink squares under it, named on hover and focus, and the rows tighten. Booking goes from 2 654 to 2 264 px high. A wall of more than 24 rows opens compact.
7. **Folded lanes say what they hold.** Each step is its coloured squares and the name of its use case under them, in handwriting, wrapping on several lines. A folded lane is a table of contents, not a strip of dots.
8. **First look.** It fits the wall's width and starts at the top, so the title, the time arrow and the first lane's head show at real size. When the wall is more than four windows high, every lane opens folded: the first look is then the table of contents, and one click unfolds a lane.
9. **Keyboard fixes found by the real key presses.** Tab now enters the wall on a post-it (it landed on a lane's tape before). The one-letter shortcuts work while a button has the focus (they were ignored).

### Making the actor's view exact

Today the generator knows `actor → read model` and `actor → command`. It guesses that the commands of a lane are decided from the read model the actor reads in that lane. Three places could give the real link, cheapest first.

**A. The links of the API's resources (recommended).** The API is already hypermedia. The presenter of a read model builds the links to the commands it offers, by route name:

```php
// apps/api/src/Booking/BookingPageResourcePresenter.php
$this->links->to('api_booking_page_set_windows'),
open: $this->offered('OpenBooking', $page, 'api_booking_page_open'),
```

So the code states it: `My booking page` offers `Set weekly windows` always, and `Open booking` only while the page is closed.

- Model addition: a link `readModel → command` with `via` the link's name (`setWindows`, `open`) and `certain: false` when the link is offered under a condition (`offered(...)`, a nullable property of the links resource).
- How: `CallsCollector` also keeps the string literal passed to the links service. `ClassFactsCollector` already reads controller attributes for `#[IsGranted]`; it also keeps the `#[Route(name:)]`. The route name joins the presenter to the controller, then to the handler's command. The presenter joins to the read model by the output port it implements, which the model already holds (`detail`).
- Cost: small to medium. Two collector extensions, one join in `ContextStorming`, their fixtures and tests. No change to the core or to the API.
- What it gives beyond exactness: the wall can draw "offered while…" as a dotted line, and a command no resource links to becomes a hotspot ("no view offers this"). Nested resources work the same way (a period's `remove` link).
- Limit: a command reached without a link (a form posted to a known URL) stays unlinked. The MCP tools already declare their route with `#[Adapts('api_booking_page_close')]`, which confirms the badge.

**B. The front's screens.** Each feature calls the endpoints by path (`api.useMutation('post', '/api/me/booking-page/opening')` in `../../../apps/front/src/features/booking/mutations.ts`), and the routes import the feature's components. A TypeScript pass could say which route's screen calls which query and which mutation. This is the truest answer to "what does the person see", and the only one that knows about two views of the same read model. Cost: large (follow hooks through components to routes, a second analyser beside PHPStan, and the front becomes an input of the model). Keep it for the day the wall must show screens.

**C. An attribute on the controller**, such as `#[DecidedFrom(ConsultMyBookingPage::class)]`. Cost: small to read, but it is written by hand and nothing checks it, so it can lie. It contradicts "drawn from the code, never by hand". Not recommended while A is available.

With A in the model, the "?" disappears wherever a link exists, and stays only where the generator still guesses.

### What was driven for real

`build/drive.mjs` drives headless Chrome over the DevTools protocol with real mouse clicks and key presses, then takes the screenshots. 32 checks pass, with no script error:

- the fold button: click folds, the link holds the fold, the folded lane names its steps, click unfolds;
- a pinned story: a click on `Book slot` pins it, opens its card, lights the story from `Anyone with the link` to `Identify booker`, and writes the link; Escape lets go;
- the keyboard: Tab, →, ↓, Enter, `C` twice, `/` then typed text then Enter, Back, `D`, `F`;
- a short window opens the wall folded;
- the fixture (`mockup.html?model=fixture`, `build/fixture.json`): `Member` reads `Requests` and sends `Approve request`; `Admin` reads nothing, sends `Approve request` and `Reject request`; `Auditor` reads nothing and only sends the shared command. Each stands once, the two without a view get their hotspot, three lines are drawn and one is guessed, and the shared command lights its three senders.

Still not tested: Firefox and Safari, touch, a screen reader, the print layout, and a wall of Prospecting's real size (the fixture is small).

## 9. Iteration 3: what a view offers, read from the API

The dotted stroke with a "?" is gone. It was not event storming grammar, and it is no longer needed: for Booking the link from a read model to its commands is now exact.

### The data

Section 8 proposed to read the links of the API's resources. I did it by hand for Booking, as the generator would:

- `BookingPageResourcePresenter` presents `My booking page`. It builds `links->to('api_booking_page_set_windows')`, `…_add_inactive_period`, `…_offer_channels`, and, on each inactive period, `…_remove_inactive_period`: four links that are always there. It builds `offered('OpenBooking', …)`, `offered('CloseBooking', …)` and `offered('ChangeBookingLink', …)`, which return no link in some states: three conditional links.
- Each route name is one controller's `#[Route(name:)]`, hence one command.
- `PublicBookingPageResourcePresenter` builds no link.

The seven links sit next to the embedded model in `mockup.html`, as `OFFERS`, with a comment that says the generator does not produce them yet and where it would read them. In `model.json` they would be `readModel → command` links with `via` (the link's name) and `certain`.

### The drawing

- **actor → read model → solid marker lines** to the commands that view offers. The line leaves the read model's right edge and branches.
- **A conditional offer is the wall's "only sometimes" dashed arrow**, the same as a conditional reaction. `Open booking`, `Close booking` and `Change booking link` are dashed. No "?" anywhere.
- **A story follows the offer.** Pinning `Open booking` lights `Member`, `My booking page`, its own line only, and what follows. Its card says "Offered by My booking page, link `open`, only in some states". Hovering a read model lights everything it offers.
- **A command no view offers.** The rule I chose:
  - It is a **gap** when its actor does read a view in the module and that view carries no link to it. The command then gets the standard hotspot at its head, with a short question: **"Offered where?"**. The actor's line goes over the view, straight to the command, because the actor does send it and the view does not lead to it.
  - It is **not a gap** when its actor reads nothing in the module. The actor then stands alone at the head of its commands, with solid lines and no hotspot. This replaces the "Reads what?" hotspot of iteration 2, which questioned something that may be normal.
  - A command only the AI or a reaction sends has no view and no hotspot.
- **Booking has one real gap: `Book slot`.** Anyone with the link reads `Public booking page`, and that resource has no link to the booking endpoint: the front knows the address by itself. The member's resource carries its links, the public one does not. The wall shows it, which is what a hotspot is for. Whether to add the link is the maintainer's call.

### Checked

`build/drive.mjs` now runs 41 checks by real clicks and key presses, all green, no script error. New ones: no "?" on the wall; `My booking page` offers seven commands, three dashed, and they are open, close and change link; the only gap is on `Book slot`; a view that does not offer a command stays out of its story; an offered command lights its view and only its own line; the card names the link and its condition; in the fixture an actor that reads nothing stands alone without a hotspot.

One limit of the mockup: the dashed lines to the commands do not carry the word "maybe" that the dashed reaction arrows carry, for lack of room between the branch and the command.

## 10. Iteration 4: a dashed line says when, and nothing covers a label

### A dashed line shows its condition

- **On hover and on keyboard focus**, a dashed line shows a small tooltip beside it, and the line thickens.
  - A conditional offer (read model → command): `Offered only while the page is closed` on `Open booking`, `Offered only while the page is open` on `Close booking`, `Offered only once the page has a link` on `Change booking link`.
  - A conditional reaction (policy → command) keeps its handwritten "maybe" and adds the condition: `Maybe: only when the exchange was erased`.
  - When the code states no condition the generator can read, the tooltip says `only in some states`. The fixture has such a link.
- **The target is wide.** Each dashed line has an invisible 28 px stroke over it that takes the mouse and the focus. These targets come after the post-its and the lane tapes in the tab order, so Tab still enters the wall on a post-it.
- The condition is also in the command's card ("Offered by My booking page, link `open`, only while the page is closed") and in the text view.

**Where the conditions come from.** I read them by hand, and the mockup holds them beside the links (`when` on an offer, `WHEN` for a reaction), with a comment that says so.

- Offers: `BookingPageResourcePresenter::offered()` only tests `in_array($intent, $page->possibleIntents)`. The words are in `BookingPageLinksResource` (its docblock, and each nullable link's `#[OA\Property(description:)]`: "Open the page, while it is closed"), and the rule itself is `Domain/Rule/BookingPagePossibilities` (`$open ? CloseBooking : OpenBooking`, `$hasALink ? ChangeBookingLink`).
- Reaction: `ForgetAppointmentOfErasedExchangePolicy` returns no command while `$event->present`.

For the generator, the cheap and honest source is the description already written on the nullable link (a string the collector copies). A policy's condition is an expression, not a sentence: the generator can copy the expression (`not $event->present`) or the policy's docblock, but it cannot write "when the exchange was erased" by itself. So `when` is an optional text in the model, and the fallback stays "only in some states".

### Nothing overlaps or clips a label

The two defects of `shots/pin.png`, and the others the new check found:

- **The pinned ring** was drawn outside the post-it and covered its neighbour's text ("ooking page"). The ring of a pinned, focused or found post-it is now drawn **inside** its own paper. The lift under the mouse went from 5 % to 2.5 %.
- **Hotspots** no longer touch any post-it: "Offered where?" stands before its command with a gap, "No event recorded" and "Who reads this?" stand after theirs with a gap, and they tilt less (about 6°).
- **The aggregate's label** has wider margins, so the command and the event that overlap its edges never reach its text. It wraps on two lines when needed.
- **A published event** no longer covers the edge of its policy's text.
- **Port strips** no longer overlap each other, and their text starts below the edge of the post-it they are tucked under.
- **The actor** overlaps its read model by one pixel instead of five.

`build/drive.mjs` measures it: for every label of every post-it (name, method, "whenever…", "asks…"), the label must fit its own paper, and its box must cross the bounding box of no post-it painted above it. A hotspot's box must cross no post-it at all. Bounding boxes include the tilt, so the check is stricter than the eye. It runs on the first look, with `Book slot` pinned under the mouse, with `Open booking` pinned under the mouse, in compact rows and on the fixture. A second check verifies that the names of a folded lane do not run into each other.

### Checked

53 checks by real mouse moves, clicks and key presses, all green, no script error. New screenshots: `shots/condition.png` (the offer's condition) and `shots/condition-reaction.png` (the reaction's).

Limits: the overlap check runs at real size, not at every zoom level, and not on the folded or far-zoom states of the post-its themselves. The tooltip is transient and may pass over a neighbouring post-it while it shows.
