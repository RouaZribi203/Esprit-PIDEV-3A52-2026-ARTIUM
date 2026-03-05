# ARTIUM

## Overview

ARTIUM is a Symfony-based web platform dedicated to cultural and artistic events. It allows artists to publish events, amateurs to discover and join them, and administrators to manage the ecosystem from a moderation dashboard.

## Features

- User authentication and role-based access (`admin`, `artiste`, `amateur`)
- Event creation, update, and management workflows
- Stripe ticket purchase flow with secure checkout
- Ticket generation with QR code and PDF export
- Email notifications after ticket purchase
- Search integration with Meilisearch
- Media uploads and gallery support
- Multi-language interface support
- reCAPTCHA protection on signup

## Tech Stack

### Frontend

- Twig templates
- Symfony UX (Stimulus, Turbo, Dropzone, Chart.js)
- Asset Mapper
- HTML/CSS/JavaScript

### Backend

- PHP 8.1+
- Symfony 6.4
- Doctrine ORM + Doctrine Migrations
- MySQL (default local setup) / PostgreSQL (Docker option)
- Stripe API, Meilisearch, Symfony Mailer
- PHPUnit + PHPStan

## Architecture

The project follows a layered Symfony architecture:

- `src/Controller` handles HTTP requests and route actions
- `src/Service` contains business logic and integrations
- `src/Entity` and `src/Repository` manage persistence with Doctrine
- `src/Form` encapsulates form definitions and validation flows
- `templates/` provides Twig-based UI for front office and admin
- `config/` centralizes framework, package, and service configuration

## Contributors

- ARTIUM student project team (add names and GitHub profiles here)

## Academic Context

- Program: 3ème année (PI Dev)
- Semester: Semestre 2
- Project Type: Academic web engineering project
- Objective: Build a complete cultural event platform using modern Symfony practices

## Getting Started

1. Install dependencies:

```bash
composer install
```

2. Create local environment file:

```powershell
Copy-Item .env .env.local
```

3. Configure `.env.local` with at least:
- `APP_SECRET`
- `DATABASE_URL`
- `MAILER_DSN`
- `STRIPE_SECRET_KEY`, `STRIPE_PUBLISHABLE_KEY`, `STRIPE_WEBHOOK_SECRET`
- `MEILISEARCH_URL`, `MEILISEARCH_API_KEY`, `MEILISEARCH_PREFIX`
- `RECAPTCHA_SITE_KEY`, `RECAPTCHA_SECRET_KEY`

4. Create database and apply migrations:

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

5. Run the application:

```bash
symfony server:start
```

6. Optional quality checks:

```bash
php bin/phpunit
vendor/bin/phpstan analyse --configuration=phpstan.dist.neon
```

## Acknowledgments

- Symfony and the open-source PHP ecosystem
- Doctrine, Twig, and Symfony UX contributors
- Stripe for payment infrastructure
- Meilisearch for fast search capabilities
- Faculty and mentors supporting the ARTIUM project
