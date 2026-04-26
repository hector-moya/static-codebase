# Static Codebase

A Symfony application for managing comments and replies with pagination and soft-delete support.

## Requirements

- [DDEV](https://ddev.readthedocs.io/en/stable/) — local development environment
- A Docker provider (Rancher Desktop recommended, but any compatible provider works)

## Getting Started

### 1. Install DDEV

Follow the [official DDEV installation guide](https://ddev.readthedocs.io/en/stable/users/install/ddev-installation/). This project uses Rancher Desktop as the Docker provider, but use whatever works for your setup.

### 2. Start the app

Run these commands from the project directory:

```bash
ddev start
ddev composer install
ddev launch
```

The app will open in your browser automatically after `ddev launch`.

## Database Setup & Seeding

Once the app is running, create the database schema and populate it with fake data:

```bash
ddev console doctrine:schema:create
ddev console doctrine:fixtures:load
```

This seeds 30 parent comments (each with 0–5 replies) with a percentage soft-deleted, giving you enough data to test pagination and the deleted comments filter.

To **reset** the database at any point:

```bash
ddev console doctrine:schema:drop --force
ddev console doctrine:schema:create
ddev console doctrine:fixtures:load
```

## Running the Test Suite

```bash
ddev exec php bin/phpunit
```

## Useful DDEV Commands

| Command | Description |
|---|---|
| `ddev start` | Start the local environment |
| `ddev stop` | Stop the local environment |
| `ddev console <command>` | Run a Symfony console command |
| `ddev ssh` | SSH into the container |
| `ddev composer <command>` | Run a Composer command |
