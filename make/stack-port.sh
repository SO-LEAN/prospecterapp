#!/bin/sh
# The port of the host the stack of a worktree is published on (ADR 0116): kept in .stack-port at its root once
# chosen, the first from 8443 that no other worktree of the repository keeps and nothing listens on.
set -eu
root=$1
file="$root/.stack-port"

if [ -s "$file" ]; then
	cat "$file"
	exit 0
fi

kept=$(git -C "$root" worktree list --porcelain | sed -n 's/^worktree //p' | while read -r tree; do
	cat "$tree/.stack-port" 2>/dev/null || true
done)
listening=$(ss -Hltn 2>/dev/null | awk '{ sub(/.*:/, "", $4); print $4 }')

port=8443
while printf '%s\n%s\n' "$kept" "$listening" | grep -qx "$port"; do
	port=$((port + 1))
done
echo "$port" | tee "$file"
