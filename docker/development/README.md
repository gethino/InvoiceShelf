# InvoiceShelf v2 development environment

This Docker environment is for local development only. For a production
deployment, use the maintained
[InvoiceShelf Docker images](https://github.com/InvoiceShelf/docker).

## Requirements

- Docker Engine with Docker Compose v2 (or Docker Desktop)
- A local checkout of this repository

On Linux, if your user or group ID is not `1000`, export the IDs before the
first start so files created in the container remain writable on the host:

```bash
export USRID=$(id -u)
export GRPID=$(id -g)
```

## Quick start

```bash
git clone https://github.com/InvoiceShelf/InvoiceShelf.git
cd InvoiceShelf
git checkout 2.x
cp .env.example .env
./devenv
```

`./devenv` checks Docker, adds `invoiceshelf.test` to your hosts file when
needed (and may ask for `sudo`), lets you choose MySQL/MariaDB, PostgreSQL, or
SQLite, and asks whether to enable Gotenberg for PDF generation. It saves that
choice in the ignored `.devenvconfig` file and starts the selected Compose
environment.

Install PHP dependencies and generate an application key after the containers
start:

```bash
./devenv run composer install
./devenv run php artisan key:generate
```

The frontend runs on the host with Node.js 24 and the pnpm version pinned in
`package.json`:

```bash
corepack enable
pnpm install --frozen-lockfile
pnpm dev
```

Keep `pnpm dev` running, then open <http://invoiceshelf.test> and complete the
installation wizard.

## Everyday commands

Run these commands from the repository root:

```bash
./devenv start             # start the last selected environment
./devenv stop              # stop the environment
./devenv logs              # follow logs from all services
./devenv rebuild           # rebuild images without cache and restart
./devenv shell             # open a shell in the PHP container
./devenv run php artisan about
./devenv test              # run Pest
./devenv format            # run Pint
./devenv destroy           # remove the environment, images, and volumes
```

`./devenv logs` also accepts service names, for example
`./devenv logs php-fpm`.

## Local services

InvoiceShelf is served at <http://invoiceshelf.test>.

Adminer is available at <http://localhost:8080> for every database choice.

Mailpit is available at <http://localhost:8025>. From the application container,
use SMTP host `mail` and port `1025`.

MySQL/MariaDB or PostgreSQL starts only for the corresponding database choice.
Their host ports are `3306` and `5432`, respectively.

The selected Compose file defines the database credentials. For SQLite, set
`DB_DATABASE` to `/var/www/html/database/database.sqlite`; no database server
is started.

## Advanced: Docker Compose directly

Use `./devenv` for normal work because it records the selected configuration.
You can run a specific Compose file yourself when you need to inspect or
customise it:

```bash
docker compose -f docker/development/docker-compose.mysql.yml up -d --build
docker compose -f docker/development/docker-compose.mysql.yml down
```

The available files cover MySQL/MariaDB, PostgreSQL, and SQLite, with optional
`.gotenberg` variants. When working directly with Compose, manage the host entry
and database configuration yourself.

## Production

Do not deploy the development Compose files. They mount the repository into
containers and expose local development services. Follow the
[production Docker repository](https://github.com/InvoiceShelf/docker) for
supported deployment instructions.
