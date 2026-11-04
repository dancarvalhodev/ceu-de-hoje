# Host user's ids, read by compose.yaml: the php image and the node container run with them
export HOST_UID := `id -u`
export HOST_GID := `id -g`

[private]
default:
    @just --list --unsorted

# First run: setup + up + composer install + app key + migrate + npm install + build
[group('environment')]
install: setup up (composer "install --no-interaction") _key-if-missing migrate (npm "install") (npm "run build")
    @echo "✔ Install complete: http://localhost (Mailpit: http://localhost:8025)"

# Create .env from .env.example
[group('environment')]
setup:
    #!/usr/bin/env bash
    set -euo pipefail
    [ -f .env ] || { cp .env.example .env; echo "✔ .env created"; }
    echo "✔ Setup complete"

# Start the containers (with build)
[group('environment')]
up: _docker _env
    docker compose up -d --build
    @echo "🌐 http://localhost"

# Stop the containers
[group('environment')]
stop: _docker _env
    docker compose down

# Show the containers' status
[group('environment')]
status: _docker _env
    docker compose ps

# Follow the logs of all containers
[group('environment')]
logs: _docker _env
    docker compose logs -f

# Open a shell in a container (default: php)
[group('environment')]
shell service="php": (_running service)
    docker compose exec {{ if service == "php" { "-u www-data" } else { "" } }} {{ service }} sh

# Run an Artisan command (e.g. just artisan make:model Location)
[group('laravel')]
artisan *args: (_running "php")
    docker compose exec -u www-data php php artisan {{ args }}

# Apply pending migrations
[group('laravel')]
migrate: (_running "php")
    docker compose exec -u www-data php php artisan migrate

# Drop all tables and run every migration again
[group('laravel')]
[confirm("This deletes ALL data in the dev database. Continue? [y/N]")]
fresh: (_running "php")
    docker compose exec -u www-data php php artisan migrate:fresh

# Open Tinker (Laravel REPL)
[group('laravel')]
tinker: (_running "php")
    docker compose exec -u www-data php php artisan tinker

# Run Composer in the php container
[group('php')]
composer *args: (_running "php")
    docker compose exec -u www-data php composer {{ args }}

# Run the tests (optional filter by test name)
[group('php')]
test filter="": (_running "php")
    docker compose exec -u www-data php php artisan test {{ if filter == "" { "" } else { "--filter " + quote(filter) } }}

# Static analysis (Larastan)
[group('php')]
stan: (_running "php")
    docker compose exec -u www-data php vendor/bin/phpstan analyse

# Check code style (Pint)
[group('php')]
cs: (_running "php")
    docker compose exec -u www-data php vendor/bin/pint --test

# Fix code style (Pint)
[group('php')]
cs-fix: (_running "php")
    docker compose exec -u www-data php vendor/bin/pint

# Run npm in a throwaway node container
[group('frontend')]
npm *args: _docker _env
    docker compose run --rm node npm {{ args }}

# Start the Vite dev server (hot reload)
[group('frontend')]
vite: _docker _env
    docker compose run --rm --service-ports node npm run dev

# Remove the containers and the volumes (database and Redis data)
[group('cleanup')]
[confirm("This deletes the containers AND the database and Redis volumes. Continue? [y/N]")]
reset: _docker _env
    docker compose down -v --remove-orphans

_docker:
    @command -v docker > /dev/null || { echo "✖ docker not found in PATH." >&2; exit 1; }
    @docker compose version > /dev/null 2>&1 || { echo "✖ docker compose v2 not found." >&2; exit 1; }
    @docker info > /dev/null 2>&1 || { echo "✖ The Docker daemon did not respond. Is it running?" >&2; exit 1; }

_env:
    @[ -f .env ] || { echo "✖ .env not found. Run: just setup" >&2; exit 1; }

# key:generate always overwrites, so only run it while APP_KEY is empty
_key-if-missing: (_running "php")
    @grep -q '^APP_KEY=base64:' .env || docker compose exec -u www-data php php artisan key:generate

_running service: _docker _env
    @[ -n "$(docker compose ps --status running --quiet {{ service }})" ] || { echo "✖ The '{{ service }}' container is not running. Run: just up" >&2; exit 1; }
