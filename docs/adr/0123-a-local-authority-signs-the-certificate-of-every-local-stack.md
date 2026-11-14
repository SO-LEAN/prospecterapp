# 0123. A local authority of the machine, restricted to local.prospecter.io, signs the certificate every local stack serves

- Date: 2026-11-14
- Type: architecture
- Status: Accepted
- Supersedes: for the self-signed certificate Chrome asks to accept once per port, [0116](0116-each-checkout-runs-its-own-stack-the-journeys-run-in-the-ci.md)
- Implemented by: `Makefile` (`ca`), `make/common.mk` (`LOCAL_CA_DIR`), `docker/nginx/local-ca.sh`, `docker/nginx/05-generate-certificate.sh`, `docker/nginx/templates/`, `docker-compose.yml`, `README.md` (« Certificat local »)

## Context

nginx generated a self-signed certificate per checkout. Since each worktree runs its own stack on a port of its own (0116), Chrome asked to accept it once per host and per port, so again for each new worktree: an agent walking its QA journey in Chrome waited until the user had accepted the certificate of its port.

## Decision

- One local authority per machine: `make ca`, openssl in the image of nginx, creates it once in `docker/data/ssl` of the main checkout, whichever checkout runs it, valid twenty years, with a critical name constraint permitting `local.prospecter.io` and its subdomains only; its key readable by the host user alone. It signs the certificate of `local.prospecter.io`, `api.local.prospecter.io` and `static.local.prospecter.io`, valid ten years. Run again, it keeps both.
- The nginx of every checkout mounts the signed certificate read only, never the key of the authority, and serves it when it exists; the self-signed certificate of the checkout otherwise, as before.
- The authority is imported once in the browser. Neither its key nor the certificates are committed: the repository is public (0059), and whoever held the key would sign certificates the browser accepts for these hosts.

## Consequences

- The stacks of every checkout, whatever their port, are trusted without a warning once the authority is imported: a port is no name in a certificate.
- The durations spare renewals: Chrome bounds to 398 days the certificates of the public authorities only, not those of an authority imported by hand.
- A machine without `make ca` keeps the former behaviour; a lost or leaked key means removing the authority, running `make ca` again and importing the new one.
- The stack of a worktree reads a file of the main checkout, out of its own directory.

## Alternatives considered

None: the user decided on a local authority per machine, restricted to the local domain, of a very long duration.

## Revisit when

A browser refuses a certificate of this duration or the name constraint, or the local stack gets hosts outside `local.prospecter.io`.
