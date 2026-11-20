# 0168. An AI gives an image by its address on the web, which the API downloads

- Date: 2026-11-20
- Type: architecture
- Status: Accepted
- Implemented by: `apps/api/src/Shared/Mcp/{ImagesOnTheWeb,ImageAddressInput}.php`, `apps/api/src/Shared/Images.php`, `apps/api/src/Prospecting/Mcp/ChangeLogoTool.php`, `app.images_on_the_web_http` in `apps/api/config/services.yaml`, `apps/api/tests/Double/ImagesOnTheWeb.php`

## Context

Every endpoint of a member has its tool (0163), the logo of an organization among them: the screen sends its image as a file (multipart, 2 MB at most, a JPEG, PNG or WebP). A tool of the Model Context Protocol takes JSON arguments only, and the ticket managing the organizations and the contacts through the MCP server left open whether the tool takes the address of an image on the web or its content in base64, by what the clients of the protocol can send.

## Decision

- A tool taking an image takes the address of the image on the web (`url`, http or https), which the API downloads, then gives the core the same command as the endpoint (`changer-logo-organisation`, `ChangeLogo`).
- The download never reaches a private network (`NoPrivateNetworkHttpClient`, as the documents of the clients of the AIs, 0157), lasts 5 seconds at most, follows no redirect, stops beyond 2 MB; the image is then checked as the screen's is (`Shared\Images`: 2 MB, JPEG, PNG or WebP). An address out of reach is a violation of `url`, an image refused the violations of the screen, both 422.
- The tool says `openWorldHint`: it reaches outside Prospecter.
- The member is checked before the image is downloaded; the file downloaded is removed once the command used it, within the call (`ImagesOnTheWeb::whileDownloaded()`): the API runs under PHP-FPM, a kernel per request, its services never reset.

## Consequences

- Claude web gives the logo it found on the site or the page of the organization; Claude Code the address of an image as well. An image the AI holds only as a file of its machine has first to be published somewhere.
- The API makes requests to addresses a member chooses: bounded by the private networks refused, the time, the size and the redirects, as for the documents of the clients of the AIs.
- The tools of the pictures and of the logo of the organization of the member, when they come, take the same input and the same download.

## Alternatives considered

- The content of the image in base64, weighed by the maintainer: Claude web has no bytes of an image to give as an argument, and for Claude Code an image of 2 MB is 2.7 MB of base64 in its context.
- Both, the address or the content: the cost of the second for no client that needs it.

## Revisit when

- A client of the protocol sends files (a resource or a content of the protocol carrying a file the server can read), or an AI needs to give an image that no address serves.
