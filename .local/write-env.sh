#!/usr/bin/env bash
set -euo pipefail

SECRET_FILE="/root/.skvn-local/minhhaifish.env"
ENV_FILE="/mnt/d/Github/shipment-tracking/.local/ENVIRONMENT.md"

# shellcheck disable=SC1090
. "$SECRET_FILE"

{
	printf '%s\n' '# Local Environment'
	printf '%s\n' ''
	printf '%s\n' '# Do not commit this file. It is ignored by git.'
	printf '%s\n' ''
	printf '%s\n' 'WP_RUNTIME_ROOT_WINDOWS=D:\Github\minhhaifish'
	printf '%s\n' 'WP_RUNTIME_ROOT_WSL=/mnt/d/Github/minhhaifish'
	printf '%s\n' 'PROJECT_ROOT_WINDOWS=D:\Github\shipment-tracking'
	printf '%s\n' 'PROJECT_ROOT_WSL=/mnt/d/Github/shipment-tracking'
	printf '%s\n' 'PLUGIN_RUNTIME_ROOT_WSL=/mnt/d/Github/minhhaifish/wp-content/plugins/skvn-shipment-tracking'
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
	printf '%s\n' 'NOTES=Runtime root has WordPress core, GeneratePress, SKVN child theme, and SKVN custom blocks. Thumbpress is required and must be verified separately. Shipment tracking is synced when its bootstrap exists.'
} > "$ENV_FILE"

chmod 600 "$ENV_FILE"
