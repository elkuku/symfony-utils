# Symfony Utilities

## Dev environment scripts

The package ships shell scripts for setting up and running a Symfony project locally.
A [Symfony Flex](https://symfony.com/doc/current/setup/flex.html) recipe copies them into the project's `bin/` folder:

| Script             | What it does                                                                                                                                             |
|--------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------|
| `bin/install`      | `composer install`, starts the Docker Compose services, runs the Doctrine migrations, loads the fixtures on a fresh database, starts the Symfony server |
| `bin/start`        | Starts the Docker Compose services and the Symfony server                                                                                                |
| `bin/stop`         | Stops the Symfony server and the Docker Compose services                                                                                                 |
| `bin/deploy`       | Runs on the server: `composer install --no-dev`, `dump-env prod`, compiles the assets, runs the Doctrine migrations. Set `PHP_BIN`/`COMPOSER_BIN` to use other binaries |
| `bin/functions.sh` | Helpers used by the scripts above                                                                                                                        |

Each step is skipped when the project doesn't use it (no `compose.yaml`, no Doctrine migrations, no fixtures).
Fixtures are only loaded when no migration had been run before, because loading them purges the database.

The local scripts need the [Symfony CLI](https://symfony.com/download), and Docker with Compose v2 (`docker compose`) if the project has a Compose file. `bin/deploy` only needs PHP and Composer.

### Installing the scripts

The recipe lives in a custom Flex endpoint, [elkuku/recipes](https://github.com/elkuku/recipes). Add it to your project's `composer.json`:

```json
"extra": {
    "symfony": {
        "endpoint": [
            "https://api.github.com/repos/elkuku/recipes/contents/index.json?ref=flex/main",
            "flex://defaults"
        ]
    }
}
```

Then install the package, or, if it is already installed, install its recipe:

```shell
composer require elkuku/symfony-utils
# or
composer recipes:install elkuku/symfony-utils
```

Flex asks before overwriting scripts that already exist in `bin/`; add `--force` to replace them.
