#!/usr/bin/env bash
set -e

if [[ -z "$WP_ORG_USERNAME" || -z "$WP_ORG_PASSWORD" || -z "$WP_ORG_SLUG" ]]; then
	echo "Missing WP.org config."
	exit 1
fi

echo $( pwd )
ls -lah
