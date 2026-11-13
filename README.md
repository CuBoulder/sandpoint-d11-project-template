# sandpoint-d11-project-template
University of Colorado Boulder Drupal 11 project template

Starting place for the development and testing of the Drupal 11 version of Web Expres. 

## Installation
```bash
composer -v             # verify that you have have composer 2.x installed

composer self-update    # update composer to the latest version (if necessary)

git clone <project-name> # This will become the name of your containers (ddev-<project-name>)

cd <project-name>

ddev start              # will spin up the containers: ddev-<project-name>

ddev composer install   # will pull in all the modules

ddev install-site       # will run a `site-install` script, located .ddev/commands/web/install-site

ddev describe           # will return the urls for your project, in addition to other info

ddev poweroff           # shuts the container down
ddev delete -Oy         # destroys the container, without backing up the database. You can remove the flags to create a backup if you wish
```

### Updating this repository
If new versions of drupal core/contrib modules need to be installed, you should run composer update or composer require as needed and check in the new version of composer.json.  If you need to update the version of composer itself, that is found in the .lando.yml file configuration.

---

# Pull a Pantheon Container

Sync your local install with the db and files of a designated Pantheon site.

## Prerequisites

In addition to Composer and DDEV, you will need:
- Terminus installed locally. See [Pantheon's documentation](https://docs.pantheon.io/terminus/install) for installation steps.
- [Personal Access Token](https://docs.pantheon.io/personal-access-tokens) (aka machine token) on your Pantheon account.

## 1. Token
Have your token set @ `/Users/<user>/.ddev/global_config.yaml`
  
```yaml
web_environment:
  - TERMINUS_MACHINE_TOKEN=<your_generated_token>
```

These expire and will need to be regenerated occasionally.

----

## 2. Configuring DDEV / Pantheon Link

Clone this repo, run the usual `ddev start && ddev composer install && ddev install-site` chain, then set up your config or supply your target site's info in the pull command directly.

You can configure which site and environment to sync to through:
- CLI argument with the pull command (sufficient for one-off syncs)
- and/or by setting a few values in `.ddev/config.yaml` (best if you plan to periodically re-sync to the same site).

See [DDEV's Pantheon docs](https://docs.ddev.com/en/stable/users/providers/pantheon/#pantheon-quickstart) for more info.

You'll generally want to sync to `live`.

### In DDEV Config

In `.ddev/config.yaml`:
```yml
web_environment:
  # machine name of site you want to sync to, e.g. ucbprod-strategicrelations
  - DDEV_PANTHEON_SITE=<site-machine-name>

  # which Pantheon environment to sync to (e.g. dev, test, live, or a multidev)
  # for multidevs, use the multidev's name as found in its platform domain
  - DDEV_PANTHEON_ENVIRONMENT=live

  # [Optional] (but recommended)
  # when 'true', it will use the most recent backup for the site env being pulled (requires a backup to exist)
  # when 'false' or omitted, the `ddev pull pantheon` command will create a new backup
  - DDEV_USE_PANTHEON_BACKUP=true
```

### As Command Args

The `ddev pull pantheon` command accepts an `--environment` flag which can be used in place of **or to override** the values set in `.ddev/config.yaml`.

**Examples:**
```bash
ddev pull pantheon --environment=DDEV_USE_PANTHEON_BACKUP=true -y
ddev pull pantheon --environment="DDEV_PANTHEON_SITE=ucbprod-aerospace,DDEV_PANTHEON_ENVIRONMENT=test" -y
```
  
---

## 3. Pull Database on container


```bash
ddev pull pantheon -y
```

This automatically runs [`after-pull.sh`](.ddev/scripts/after-pull.sh) at the end to adjust modules and config for local dev.

There is no longer a need to run `ddev install-site`, your site will be ready to go!

