# Local Environment

# Do not commit this file. It is ignored by git.

WP_RUNTIME_ROOT_WINDOWS=D:\Github\minhhaifish
WP_RUNTIME_ROOT_WSL=/mnt/d/Github/minhhaifish
WP_URL=http://localhost:8080

WP_ADMIN_USER=skvn_admin
WP_ADMIN_PASSWORD=YnSyU9ezTQkNCdc95FZR9Ofu
WP_ADMIN_EMAIL=admin@example.test

DB_NAME=skvn_minhhaifish
DB_USER=skvn_local
DB_PASSWORD=aexDXzZxpGO7HnoJa8tPuj3A
DB_HOST=localhost

WP_CLI_BASE=wp --path=/mnt/d/Github/minhhaifish --allow-root

WSL_DISTRO=Debian
WSL_USER=shinkuro
WSL_HOME=/home/shinkuro

PHP_BIN_WSL=/usr/bin/php
PHP_VERSION=8.4.21
WP_CLI_BIN_WSL=/usr/local/bin/wp
WP_CLI_VERSION=2.12.0
MARIADB_BIN_WSL=/usr/bin/mariadb
MARIADB_VERSION=11.8.6

NVM_DIR_WSL=/home/shinkuro/.nvm
NODE_VERSION=20.20.2
NPM_VERSION=10.8.2
NODE_USE_CMD=. /home/shinkuro/.nvm/nvm.sh && nvm use 20

WP_DEV_SERVER_START_CMD=wsl -d Debian -- bash -lc "cd /mnt/d/Github/minhhaifish && setsid -f wp server --host=0.0.0.0 --port=8080 --allow-root"
WP_DEV_SERVER_URL=http://localhost:8080
WP_DEV_ADMIN_URL=http://localhost:8080/wp-admin/

NOTES=Runtime root has WordPress core, GeneratePress parent theme, SKVN child theme, SKVN custom blocks plugin, and required external plugins installed.
