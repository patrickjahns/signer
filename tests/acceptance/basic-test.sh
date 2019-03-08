#!/usr/bin/env bash
set -eo pipefail

if [[ "$(pwd)" == "$(cd "$(dirname "$0")"; pwd -P)" ]]; then
  echo "Can only be executed from project root!"
  exit 1
fi
SERVER="${1}"

if [[ -z "${SERVER}"  ]]; then
	echo "Syntax: $0 serverurl" >&2
	exit 1
fi
TOKEN=$(php bin/console signer:create-token --valid 3600 tester sign:*)
echo "testing simple upload to ${SERVER}/sign"
curl -X POST ${SERVER}/sign --silent --output /dev/null --fail --show-error  -F "file=@./tests/data/theme-example.tar.gz" -H "Authorization: Bearer ${TOKEN}"
echo "ok"