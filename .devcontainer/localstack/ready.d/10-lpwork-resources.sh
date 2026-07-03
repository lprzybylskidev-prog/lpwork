#!/usr/bin/env bash
set -euo pipefail

awslocal sqs create-queue --queue-name lpwork >/dev/null
awslocal s3 mb s3://lpwork >/dev/null || true
