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

## 1. Token
Have your token set @ `/Users/<user>/.ddev/global_config.yaml`
  
```yaml
web_environment:
  - TERMINUS_MACHINE_TOKEN=<your_generated_token>
```

These expire and will need to be regenerated occasionally.

----
# 2. Configuring DDEV / Pantheon Link
Clone the `sandpoint_template` and run `ddev start` as usual. Once that's finished, then we will edit some config:

- Create a copy of `.dev/providers/pantheon.yaml.example` as `pantheon.yaml`
- Under `.ddev/providers/pantheon.yaml`, add the following lines
```yml
environment_variables:
  project: <machine_name><.dev/.live>
```

**Note:  Spaces are important for the indent, it rejects tabs as invalid yaml**

This will be the machine name of the container you get from Pantheon like so:

```yml
environment_variables:
  project: ucbprod-strategicrelations.live
```

To pull a container, it must have a backup. 
- You can use .live, .test, or .dev here, currently not using .test or .dev, but .live usually has a backup
---

 Next under `web/sites/default/settings.ddev.php`, add this

```yml
// Stuff for Pantheon pulls
// Turns off Domain Masking
$config['pantheon_domain_masking.settings']['enabled'] = 'no';

// Turns off samlauth
$config['samlauth.authentication']['login_auto_redirect'] = FALSE;

// Disables antibot from running on forms (can affect login)
$config['antibot.settings']['excluded_form_ids'] = ['*'];
```

# Pull Database on container


```bash
ddev pull pantheon -y
```

There is no longer a need to run `ddev install-site`, your site will be ready to go!

