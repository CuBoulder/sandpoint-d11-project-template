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

# uninstall klaro
drush pmu klaro

# turn on dev tools and link localization
drush en ucb_dev_tools ucb_dev_localized_links

# no domain masking, no SAML
drush -y config:set pantheon_domain_masking.settings enabled no
drush -y config:set samlauth.authentication login_auto_redirect 0

# disables antibot from running on forms (can affect login)
drush -y config:set antibot.settings excluded_form_ids "['*']" --input-format=yaml

# disable JS/CSS aggregation, turn on twig debug settings
drush -y theme:dev on

drush cache:rebuild

echo "\nLogin with Username: admin\nPassword: sandpoint.test"
echo "Site URL: ${DDEV_PRIMARY_URL}\n"