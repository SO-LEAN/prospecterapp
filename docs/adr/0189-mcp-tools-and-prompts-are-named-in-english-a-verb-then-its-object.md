# 0189. MCP tools and prompts are named in English, a verb then its object

- Date: 2026-11-21
- Type: architecture
- Status: Accepted
- Implemented by: `apps/api/src/*/Mcp/*Tool.php`, `apps/api/src/*/Mcp/*Prompt.php`, `apps/api/src/Identification/Mcp/SynchronizedSince.php`, `apps/api/src/Campaigning/NextRecipientReader.php`, `apps/api/tests/Architecture/McpNamesTest.php`, `docs/architecture/mcp.md`

## Context

The MCP server came with a rule in `docs/architecture/mcp.md` and no record: tools and prompts are named in French kebab-case, and prompts are written in French. The server grew to 114 tools and 3 prompts under that rule. An AI reads the names first, and they clashed with everything else it reads: the tool descriptions, the refusals (ADR 0073) and the resources are all English. The names also mixed verbs for one intent (`lire`, `consulter`, `lister` to read; `changer`, `reviser`, `corriger` to change), and some translated French words that read badly in English (`civilite`, `mettre-en-conformite`). The prod release waits on a server an AI can read in one language.

## Decision

- Each tool and prompt name is English kebab-case: an imperative verb, then its object. The name says the business intent; it does not translate the French word for word (`list-exchanges-pending-redaction` for the exchanges of a forgotten contact whose text is still to redact, ADR 0079).
- Each intent has one verb across the server:
  - reading: `get` for one item or one view (`get-calendar`), `list` for a collection, `search` for a search by criteria. A tool that adapts a `GET` uses only these three;
  - changing: `set` replaces one attribute (`set-contact-title`), `update` revises several fields (`update-campaign`);
  - erasing: `delete`.
  - The other gestures keep their own business verb: `add`/`remove` for a membership, `create`, `log` for a past exchange or a sent message, `schedule`, `reschedule`, `cancel`, `mark`, `tag`/`untag`, `start`/`stop`, `dismiss`, `complete`/`reopen`, and the rest of the list in the test.
- A prompt is English end to end: its name, title, description, argument names and descriptions, and steps.
- `tests/Architecture/McpNamesTest` holds the closed list of verbs. It fails on a name outside the list, and on a reading tool named by another verb. A new verb is added to the list on purpose, never by accident.
- No exception.

## Consequences

- An AI guesses a tool's name from the ones it has already read, and the prompts, tools and refusals speak one language.
- MCP clients that name tools in their configuration need reconfiguring once. No stored data holds a tool name.
- The prompts are now a message in English from the member to their AI. The AI still answers its member in the member's language.
- Class names stay as they are (`ShowExchangesToMakeConformTool`): they move to English names when a branch touches them. Stored and exposed values (the campaign message variables `{civilité}`…) are another ticket.

## Alternatives considered

None. The ticket asked for English names, and the French rule had no record behind it.

## Revisit when

An MCP client or the protocol imposes a naming scheme of its own, or the verb list grows past what an AI can guess.
