# 0140. A push writes the connection only while it is the one connected at the moment it read

- Date: 2026-11-17
- Type: architecture
- Status: Accepted
- Supersedes: for a member disconnecting then connecting again during a push, the former connection written over the new one, [0133](0133-the-calendar-entry-keeps-the-state-of-the-push-of-its-exchange.md)
- Implemented by: `packages/core/src/Connectors/Domain/Model/Connection.php` (`isTheSameAs()`), `packages/core/src/Connectors/Domain/Repository/ConnectionRepository.php`, `apps/api/src/Connectors/Persistence/DynamoConnectionRepository.php`, `apps/api/src/Shared/Persistence/Table.php` (`replacing()` naming its attributes), `packages/core/features/synchroniser-mes-echanges-avec-google.feature`

## Context

A push writes the connection it pushed through after reaching Google: the calendar it created, the connection lost when Google refused its token. Since ADR 0133 it writes it only while the item is still there, a connection disconnected meanwhile not brought back. A member disconnecting then connecting again while the push of their exchanges to come is under way still finds the former connection written over the new one: its token, revoked at the disconnection, and its state, to reconnect once Google refused that token (#161). No aggregate is written under an optimistic lock (`persistence.md`).

## Decision

- **A connection is the same while it was connected at the same moment** (`Connection::isTheSameAs()`): connected again, after a disconnection or over a connection to reconnect, it is another, its `connectedAt` the moment of the new connection.
- **A push replaces the connection only while the one kept is the one it read** (`ConnectionRepository::replace()`): in the table, the put is conditioned on `attribute_exists(PK) AND #connection.connectedAt = :connectedAt`, the moment read; `connection` a reserved word of DynamoDB, named by a placeholder (`Table::replacing()` takes the names of its condition).
- **A condition refused fails the reaction**, as a connection disconnected meanwhile did: the unit of work of the push is cancelled with its entries, and the reaction, tried again, pushes through the new connection, or through none.

## Consequences

- The new connection wins: the push that read the former one writes nothing of it, nor the entries it wrote with it, the retry writing them for the new one.
- A reaction failing on a reconnection is tried again: one more push of the exchanges to come, through the new connection, which its own push at the connection already filled.
- A check making the connection to reconnect while a push of the same connection is under way may still be written over by that push, the connection read connected: the next push meeting the token refused makes it to reconnect again. Two pushes of one member writing at once still write the last one read.

## Alternatives considered

- A revision kept on the item, incremented at each write and the write conditioned on the revision read (the ticket #161): a disconnection deletes the item, and the connection made again starts its revision again, equal to the one the former push read; the moment of the connection tells the two apart without a new attribute.
- The write of the push given up silently once refused, the push succeeding (the ticket #161): the condition is part of the transaction of the push, which a refusal cancels with the entries; giving it up alone would take writing the connection out of that transaction, and keep entries of a calendar the new connection no longer pushes into.

## Revisit when

Another write of the connection needs not to be lost to a push (a check under way), or an aggregate gets an optimistic lock.
