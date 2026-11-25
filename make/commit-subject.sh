#!/bin/sh
# The rule of a commit's subject (docs/architecture/workflow.md): a conventional type, then an imperative subject of 72
# characters at most. The commit-msg hook checks the message being written, the CI the commits of a branch.
#   make/commit-subject.sh <message file>           the hook: the subject of the message git is about to commit
#   make/commit-subject.sh --commits <revisions>    the CI: each commit these revisions list (git log), merges aside
TYPES="feat fix refactor chore docs test"
MAX_LENGTH=72

# a commit that git rebase --autosquash folds into another
to_squash() {
	case $1 in
	"fixup! "* | "squash! "* | "amend! "*) return 0 ;;
	*) return 1 ;;
	esac
}

# the subjects git writes itself: a merge, a revert, a commit to squash
written_by_git() {
	case $1 in
	"Merge "* | "Revert \""*) return 0 ;;
	*) to_squash "$1" ;;
	esac
}

has_a_type() {
	for type in $TYPES; do
		case $1 in
		"$type: "?*) return 0 ;;
		esac
	done
	return 1
}

length_of() {
	printf '%s' "$1" | LC_ALL=C.UTF-8 wc -m | tr -d ' '
}

# prints each rule the subject breaks, prefixed by $2
check_subject() {
	subject=$1
	prefix=$2
	written_by_git "$subject" && return 0
	breaks=0
	if ! has_a_type "$subject"; then
		echo "${prefix}the subject \"$subject\" has no conventional type: start it with one of $(echo "$TYPES" | sed 's/ /, /g'), then \": \"."
		breaks=1
	fi
	length=$(length_of "$subject")
	if [ "$length" -gt "$MAX_LENGTH" ]; then
		echo "${prefix}the subject has $length characters, over the limit of $MAX_LENGTH."
		breaks=1
	fi
	return $breaks
}

# the first line that is neither empty nor a comment of git's template
subject_of_message() {
	grep -v '^#' "$1" | grep -m 1 -v '^[[:space:]]*$'
}

check_message() {
	check_subject "$(subject_of_message "$1")" "Commit refused: " && return 0
	echo "Your message is kept in $1."
	return 1
}

check_commits() {
	broken=0
	tab=$(printf '\t')
	commits=$(git log --no-merges --format="%h$tab%s" "$@") || return 1
	while IFS="$tab" read -r commit subject; do
		[ -n "$commit" ] || continue
		if to_squash "$subject"; then
			echo "Commit $commit: \"$subject\" is still to squash: git rebase --autosquash origin/dev."
			broken=1
		else
			check_subject "$subject" "Commit $commit: " || broken=1
		fi
	done <<EOF
$commits
EOF
	return $broken
}

if [ "$1" = --commits ]; then
	shift
	check_commits "$@"
else
	check_message "$1"
fi
