#!/bin/bash
#
# Functions for the install/start/stop scripts
#
# Installed by the elkuku/symfony-utils Flex recipe.
#
# License: WTFPL
# Author: Nikolai Plath
#

function checkIsInstalled {
  if ! command -v "$1" &> /dev/null
  then
      echo "❌ ERROR: Command '$1' could not be found on your system :("
      exit 1
  fi
}

function checkDockerCompose {
  checkIsInstalled docker
  if ! docker compose version &> /dev/null
  then
      echo "❌ ERROR: 'docker compose' (Compose v2) is not available on your system :("
      exit 1
  fi
}

# True if the project has a Docker Compose file.
function hasComposeFile {
  [ -f compose.yaml ] || [ -f compose.yml ] || [ -f docker-compose.yaml ] || [ -f docker-compose.yml ]
}

# True if the given console command (or namespace) exists, e.g. "doctrine:migrations".
function hasConsoleCommand {
  symfony console list "$1" &> /dev/null
}

# Starts the Docker Compose services and waits until they are healthy.
function startServices {
  if hasComposeFile; then
    checkDockerCompose
    printf "Starting the Docker services and waiting for them to get ready ... "
    docker compose up -d --wait || exit 1
    echo "✅"
  fi
}

# Prints the number of executed Doctrine migrations, or nothing if it cannot be determined.
function executedMigrations {
  symfony console doctrine:migrations:status 2>/dev/null \
    | awk -F'|' '{ gsub(/^ +| +$/, "", $3); gsub(/ /, "", $4) } $3 == "Executed" { print $4 }'
}
