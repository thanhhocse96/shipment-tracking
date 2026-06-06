#!/usr/bin/env bash
set -euo pipefail

WP_ROOT="/mnt/d/Github/minhhaifish"
REPO_ROOT="/mnt/d/Github/skvn-marine"
SECRET_FILE="/root/.skvn-local/minhhaifish.env"

mkdir -p /root/.skvn-local
chmod 700 /root/.skvn-local

service mariadb start >/dev/null

if [ ! -f "$SECRET_FILE" ]; then
	DB_PASS="$(openssl rand -base64 24 | tr -d '/+=' | cut -c1-24)"
	ADMIN_PASS="$(openssl rand -base64 24 | tr -d '/+=' | cut -c1-24)"

	cat > "$SECRET_FILE" <<EOF
DB_NAME=skvn_minhhaifish
DB_USER=skvn_local
DB_PASSWORD=$DB_PASS
DB_HOST=localhost
WP_URL=http://localhost:8080
WP_TITLE='Minh Hai Fish Local'
WP_ADMIN_USER=skvn_admin
WP_ADMIN_PASSWORD=$ADMIN_PASS
WP_ADMIN_EMAIL=admin@example.test
EOF

	chmod 600 "$SECRET_FILE"
fi

# shellcheck disable=SC1090
. "$SECRET_FILE"

mariadb <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';
ALTER USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
SQL

mkdir -p "$WP_ROOT"

if [ ! -f "$WP_ROOT/wp-load.php" ]; then
	wp core download --path="$WP_ROOT" --allow-root --quiet
fi

if [ ! -f "$WP_ROOT/wp-config.php" ]; then
	wp config create \
		--path="$WP_ROOT" \
		--dbname="$DB_NAME" \
		--dbuser="$DB_USER" \
		--dbpass="$DB_PASSWORD" \
		--dbhost="$DB_HOST" \
		--skip-check \
		--allow-root \
		--quiet
fi

if ! wp core is-installed --path="$WP_ROOT" --allow-root >/dev/null 2>&1; then
	wp core install \
		--path="$WP_ROOT" \
		--url="$WP_URL" \
		--title="$WP_TITLE" \
		--admin_user="$WP_ADMIN_USER" \
		--admin_password="$WP_ADMIN_PASSWORD" \
		--admin_email="$WP_ADMIN_EMAIL" \
		--skip-email \
		--allow-root \
		--quiet
fi

wp plugin install \
	woocommerce \
	contact-form-7 \
	contact-form-cfdb7 \
	seo-by-rank-math \
	antispam-bee \
	windpress \
	--activate \
	--path="$WP_ROOT" \
	--allow-root

wp theme install generatepress --activate --path="$WP_ROOT" --allow-root

rsync -a --delete "$REPO_ROOT/wp-content/themes/skvn-marine/" "$WP_ROOT/wp-content/themes/skvn-marine/"
rsync -a --delete "$REPO_ROOT/wp-content/plugins/skvn-marine-blocks/" "$WP_ROOT/wp-content/plugins/skvn-marine-blocks/"

wp plugin activate skvn-marine-blocks --path="$WP_ROOT" --allow-root
wp theme activate skvn-marine --path="$WP_ROOT" --allow-root

wp rewrite structure '/%postname%/' --path="$WP_ROOT" --allow-root --quiet
wp rewrite flush --path="$WP_ROOT" --allow-root --quiet

wp option update timezone_string 'Asia/Bangkok' --path="$WP_ROOT" --allow-root --quiet
wp option update blog_public '0' --path="$WP_ROOT" --allow-root --quiet

wp plugin list --path="$WP_ROOT" --allow-root --fields=name,status,version
wp theme list --path="$WP_ROOT" --allow-root --fields=name,status,version
