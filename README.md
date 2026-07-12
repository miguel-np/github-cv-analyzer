# GitHub CV Analyzer

[![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php)](https://php.net)
[![Symfony](https://img.shields.io/badge/Symfony-7.4%20LTS-black?logo=symfony)](https://symfony.com)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql)](https://postgresql.org)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-v4-06B6D4?logo=tailwindcss)](https://tailwindcss.com)
[![License](https://img.shields.io/badge/license-Proprietary-red)](./LICENSE)
[![Version](https://img.shields.io/badge/version-0.6.0-blue)](./CHANGELOG.md)

Analiza tus contribuciones en GitHub con IA para mejorar tu CV profesional.

Aplicación Symfony 7.4 que indexa tus repositorios, analiza cada commit con LLMs (Ollama, OpenAI, Anthropic), y genera un dashboard con insights accionables y sugerencias concretas para potenciar tu perfil.

## Características

- **Sincronización asíncrona** de repositorios, commits, PRs e issues con rate limiting
- **Motor de análisis multi-LLM** — Ollama (local), OpenAI (GPT-4o), Anthropic (Claude)
- **Clasificación inteligente** de commits por tipo, complejidad y tecnologías
- **Dashboard interactivo** con Chart.js: timeline, tech radar, distribución de contribuciones
- **Análisis de CV** con detección de gaps, recomendaciones de mejora y sugerencias de tecnologías
- **Sincronización diaria automática** vía Symfony Scheduler
- **Caché de análisis** por commit SHA — cada commit se analiza solo una vez
- **Modo oscuro** nativo y responsive design

## Stack

| Capa | Tecnología |
|------|-----------|
| Backend | Symfony 7.4 LTS + PHP 8.5 |
| UI | Twig + Turbo + Stimulus + Tailwind CSS v4 |
| Base de datos | PostgreSQL 16 + Doctrine ORM |
| GitHub API | knplabs/github-api |
| Colas | Symfony Messenger (Doctrine transport) |
| Scheduler | Symfony Scheduler (auto-sync diario) |
| IA / LLM | Ollama (local), OpenAI, Anthropic — abstracción multi-proveedor |
| Assets | Symfony AssetMapper (sin Webpack) |

## Requisitos

- PHP 8.4+ con extensiones: `pdo_pgsql`, `xml`, `intl`, `curl`, `mbstring`, `zip`, `sodium`
- Composer 2
- PostgreSQL 16+
- [Docker](https://docs.docker.com/compose/) (recomendado para desarrollo)
- [Ollama](https://ollama.ai) (opcional, para análisis con IA local)

## Instalación

### Con Docker (recomendado)

```bash
git clone https://github.com/miguel-np/github-cv-analyzer.git
cd github-cv-analyzer

# Copiar y configurar entorno
cp .env .env.local
# Edita .env.local con tus valores (ver Configuración)

# Levantar servicios
docker compose up -d

# Instalar dependencias y migrar
docker compose exec php bash
composer install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console tailwind:build

# Worker asíncrono (terminal separado)
php bin/console messenger:consume async -vv
```

### Sin Docker

```bash
git clone https://github.com/miguel-np/github-cv-analyzer.git
cd github-cv-analyzer

composer install
cp .env .env.local

# Requiere PostgreSQL 16+ corriendo, configura DATABASE_URL en .env.local

php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

symfony server:start -d
php bin/console messenger:consume async -vv &
php bin/console tailwind:build --watch
```

## Configuración

Variables de entorno en `.env.local`:

```bash
# Base de datos
DATABASE_URL="postgresql://user:pass@127.0.0.1:5432/github_cv?serverVersion=16&charset=utf8"

# Scheduler
SYNC_INTERVAL="12 hours"   # Frecuencia de auto-sync (default: 12h)

# GitHub (opcional, se configura vía UI)
GITHUB_DEFAULT_TOKEN=""    # Token por defecto para nuevos usuarios

# LLM Providers (opcional, se configuran vía UI)
OLLAMA_BASE_URL="http://localhost:11434"
OLLAMA_DEFAULT_MODEL="llama3.2"
OPENAI_API_KEY=""
ANTHROPIC_API_KEY=""

# Entorno
APP_ENV=dev
APP_SECRET=<generado>
```

## Uso

1. Accede a `http://localhost:8000`
2. Ve a **Settings**: configura tu token de GitHub y el proveedor de IA
3. Dispara la **sincronización inicial** desde el panel
4. Explora el **dashboard** con tus estadísticas de contribución
5. Revisa las **sugerencias de CV** generadas por IA
6. El scheduler ejecutará **sincronizaciones diarias** automáticamente

## Comandos principales

```bash
php bin/console doctrine:migrations:migrate     # Migrar BD
php bin/console messenger:consume async -vv      # Worker asíncrono
php bin/console scheduler:run --verbose          # Ejecutar tareas programadas
php bin/console tailwind:build --watch           # Compilar CSS en dev
php bin/console cache:clear                      # Limpiar caché

# Calidad
php vendor/bin/phpstan analyse                   # Análisis estático (level 8)
php vendor/bin/php-cs-fixer fix                  # Formatear código
php vendor/bin/phpunit                           # Tests
php bin/console lint:twig templates/             # Lint templates
```

## Documentación

| Documento | Descripción |
|-----------|-------------|
| [AGENTS.md](AGENTS.md) | Guía para agentes de IA |
| [CONTRIBUTING.md](CONTRIBUTING.md) | Cómo contribuir |
| [CHANGELOG.md](CHANGELOG.md) | Historial de cambios |
| [docs/architecture.md](docs/architecture.md) | Arquitectura del sistema |
| [docs/roadmap.md](docs/roadmap.md) | Roadmap de desarrollo |

## Licencia

Propietario. Todos los derechos reservados.
