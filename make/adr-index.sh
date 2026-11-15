#!/bin/sh
# The index of the decision records (docs/adr/README.md) written from their headers (ADR 0127): one line per record in
# the order of their numbers, its title, its type and its date those of its header. Its status column begins with the
# status of the header, or says that a later record superseded it: what the journal, immutable, cannot say in the
# record itself (ADR 0090). A record written before the type was asked keeps the type of its line. With --check, writes
# nothing and fails while the index differs.
set -eu
cd "$(dirname "$0")/../docs/adr"
index=README.md

header() {
	sed -n "s/^- $1: //p" "$2" | head -1
}

records() {
	for record in [0-9][0-9][0-9][0-9]-*.md; do
		title=$(head -1 "$record" | sed -E 's/^# [0-9]{4}\. //')
		printf '%s\t%s\t%s\t%s\t%s\t%s\n' "${record%%-*}" "$record" "$title" "$(header Type "$record")" \
			"$(header Status "$record")" "$(header Date "$record")"
	done
}

written=$(records | awk -F'\t' '
	FNR == NR {
		if ($0 ~ /^\| \[[0-9][0-9][0-9][0-9]\]/) {
			split($0, cell, / \| /)
			number = substr(cell[1], 4, 4)
			type[number] = cell[3]
			status[number] = cell[4]
			rows = 1
		} else if (rows) after = after $0 "\n"
		else before = before $0 "\n"
		next
	}
	function word(text) { sub(/[ ,;].*/, "", text); return text }
	function status_of(number, own) {
		if (word(status[number]) == word(own) || (word(own) == "Accepted" && word(status[number]) == "Superseded"))
			return status[number]
		return own
	}
	FNR == 1 { printf "%s", before }
	{
		recordType = $4 != "" ? $4 : type[$1]
		if (recordType == "") { print "No type in " $2 > "/dev/stderr"; failed = 1 }
		printf "| [%s](%s) | %s | %s | %s | %s |\n", $1, $2, $3, recordType, status_of($1, $5), $6
	}
	END { printf "%s", after; exit failed }
' "$index" -)

if [ "${1:-}" = --check ]; then
	printf '%s\n' "$written" | diff -u "$index" - >&2 || {
		echo "The index of the records differs from their headers: make adr-index writes it again" >&2
		exit 1
	}
else
	printf '%s\n' "$written" >"$index"
fi
