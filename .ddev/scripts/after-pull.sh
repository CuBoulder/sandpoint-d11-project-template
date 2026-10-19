#!/usr/bin/env bash
# Runs after `ddev pull`
# Makes the imported Pantheon database usable on this local site: local admin login, no domain masking, and this site's public URLs rewritten to local paths.
set -eu -o pipefail

if ! command -v drush >/dev/null; then
  echo "Drush is not installed yet. Run 'ddev composer install', then 'ddev exec bash /var/www/html/.ddev/scripts/after-pull.sh'."
  exit 1
fi
# set admin account
if drush user:information admin >/dev/null 2>&1; then
  drush -y user:password admin 'sandpoint.test'
  drush -y user:unblock admin
else
  drush -y user:create admin --mail='admin@example.com' --password='sandpoint.test'
fi

# localize links, no domain masking, no SAML
drush -y config:set pantheon_domain_masking.settings enabled no
drush -y php:eval "\Drupal::configFactory()->getEditable('samlauth.authentication')->set('login_auto_redirect', FALSE)->save();"
# TODO: check script? - fix links
drush php:script /var/www/html/.ddev/scripts/localize-links.php
drush cache:rebuild

echo "Local login: admin / sandpoint.test"
