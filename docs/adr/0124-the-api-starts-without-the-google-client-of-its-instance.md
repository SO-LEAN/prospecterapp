# 0124. The api starts without the Google client of its instance, reading it only once it is in SSM

- Date: 2026-11-15
- Type: architecture
- Status: Accepted
- Supersedes: for the start of the function without the client of Google, [0120](0120-the-connectors-context-connects-a-member-to-google-through-the-spa.md)
- Implemented by: `apps/api/infra/google.tf`, `apps/api/infra/lambda.tf`

## Context

ADR 0120 had the function read the id and the secret of the Google client of its instance at its start (`bref-ssm:`), set by hand in SSM before the apply. Bref fails the start of the function when a parameter it reads is missing: once Connectors was applied on dev, where the client was not yet set, every request of the api answered the 500 of the API Gateway, without the headers of CORS, the SPA unable to sign in. The apply did not tell it: the parameters are read only when the function starts. A connector is optional to a member; it cannot take the whole api down.

## Decision

The Terraform of the api lists the names of the parameters under `/prospecter/<instance>/api` (without their values) and gives the function `bref-ssm:` for the client of Google only when both its id and its secret are there; otherwise `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` are empty, as `ORIGIN_SECRET` is without the secret of the edge (ADR 0067). Setting the client afterwards needs an apply to reach the function.

## Consequences

- An instance without its Google client runs: everything but the connection to Google, which Google refuses for its missing client.
- The client set in SSM is not seen until the next apply of the api.
- The api still lets a member ask an authorization the instance cannot give; a refusal of its own, told by the SPA, is left to a ticket.

## Alternatives considered

None weighed apart from creating the two parameters with a placeholder by hand on dev, which repairs the instance, not the next one.

## Revisit when

Connectors refuses by itself a provider its instance has no client of.
