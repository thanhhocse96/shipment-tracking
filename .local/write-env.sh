#!/usr/bin/env bash
set -euo pipefail

SECRET_FILE="/root/.skvn-local/minhhaifish.env"
ENV_FILE="/mnt/d/Github/skvn-marine/.local/ENVIRONMENT.md"

# shellcheck disable=SC1090
. "$SECRET_FILE"

{
	printf '%s\n' '# Local Environment'
	printf '%s\n' ''
	printf '%s\n' '# Do not commit this file. It is ignored by git.'
	printf '%s\n' ''
	printf '%s\n' 'WP_RUNTIME_ROOT_WINDOWS=D:\Github\minhhaifish'
	printf '%s\n' 'WP_RUNTIME_ROOT_WSL=/mnt/d/Github/minhhaifish'
	printf '%s\n' "WP_URL=$WP_URL"
	printf '%s\n' ''
	printf '%s\n' "WP_ADMIN_USER=$WP_ADMIN_USER"
	printf '%s\n' "WP_ADMIN_PASSWORD=$WP_ADMIN_PASSWORD"
	printf '%s\n' "WP_ADMIN_EMAIL=$WP_ADMIN_EMAIL"
	printf '%s\n' ''
	printf '%s\n' "DB_NAME=$DB_NAME"
	printf '%s\n' "DB_USER=$DB_USER"
	printf '%s\n' "DB_PASSWORD=$DB_PASSWORD"
	printf '%s\n' "DB_HOST=$DB_HOST"
	printf '%s\n' ''
	printf '%s\n' 'WP_CLI_BASE=wp --path=/mnt/d/Github/minhhaifish --allow-root'
	printf '%s\n' ''
	printf '%s\n' 'NOTES=Runtime root has WordPress core, GeneratePress parent theme, SKVN child theme, SKVN custom blocks plugin, and required external plugins installed.'
} > "$ENV_FILE"

chmod 600 "$ENV_FILE"
