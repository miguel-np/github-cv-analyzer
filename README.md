# GitHub CV Analyzer

Analiza tus contribuciones en GitHub con IA para mejorar tu CV.

## Concepto

Conectas tu cuenta de GitHub mediante token. El sistema indexa todos los repositorios donde has contribuido, analiza commits, PRs e issues, y una IA clasifica cada contribución por tipo, complejidad, tecnologías utilizadas y calidad del código. El resultado es un dashboard con insights accionables y sugerencias concretas para mejorar tu perfil profesional.

## Stack

| Capa | Tecnología |
|------|-----------|
| Backend | Symfony 7.4 + PHP 8.5 |
| UI | Twig + Turbo + Stimulus + Tailwind CSS v4 |
| BD | PostgreSQL 16 + Doctrine ORM |
| GitHub API | knplabs/github-api |
| Async | Symfony Messenger (Doctrine transport) |
| Scheduler | Symfony Scheduler (daily auto-sync) |
| IA / LLM | Ollama (local), OpenAI, Anthropic — abstracción multi-provider |
| JS/CSS | Symfony AssetMapper |

## Requisitos

- PHP 8.4+ con extensiones: pdo_pgsql, xml, intl, curl, mbstring, zip
- Composer 2
- PostgreSQL 16+
- Docker (opcional, recomendado para desarrollo)
- Ollama (opcional, para análisis con IA local)

## Instalación rápida

```bash
# Clonar
git clone <repo-url> github-cv-analyzer
cd github-cv-analyzer

# Instalar dependencias
composer install

# Configurar entorno
cp .env .env.local
# Editar .env.local con tu DATABASE_URL y GitHub token

# Crear BD y migraciones
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Arrancar servidor de desarrollo y scheduler
symfony server:start -d
php bin/console messenger:consume async -vv &
php bin/console scheduler:run --verbose
```

## Uso

1. Accede a `http://localhost:8000`
2. Ve a Settings y configura tu token de GitHub
3. Configura tu proveedor de IA preferido (Ollama local, OpenAI, Claude)
4. Dispara la sincronización inicial
5. Explora el dashboard con tus estadísticas de contribución
6. Revisa las sugerencias de mejora de CV generadas por IA

## Documentación

- [Arquitectura](docs/architecture.md)
- [Roadmap de fases](docs/roadmap.md)
- [Guía para agentes IA](AGENTS.md)
