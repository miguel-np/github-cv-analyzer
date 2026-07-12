# Fase 1: Foundation

## Estado: Completada

## Objetivo

Crear el proyecto Symfony 7 desde cero con todo el stack de desarrollo configurado y funcionando:
- PHP 8.3 + PostgreSQL 16 (Docker)
- Symfony 7 con bundles esenciales
- AssetMapper + Turbo + Stimulus + Tailwind CSS v4
- Entidades Doctrine base con migraciones
- Estructura de directorios del proyecto

## Paso a paso

### 1. Crear proyecto Symfony

```bash
docker run --rm -v $(pwd):/app composer create-project symfony/skeleton:"7.2.*" /tmp/symfony
```

### 2. Docker Compose (PHP + PostgreSQL + Redis)

```yaml
services:
  php:
    image: php:8.3-cli
    # ...
  database:
    image: postgres:16
    # ...
```

### 3. Instalar bundles

```
doctrine/doctrine-bundle
doctrine/doctrine-migrations-bundle
symfony/messenger
symfony/asset-mapper
symfony/stimulus-bundle
symfony/ux-turbo
symfonycasts/tailwind-bundle
symfony/twig-bundle
symfony/security-csrf
symfony/validator
symfony/serializer
```

### 4. Configurar Doctrine (PostgreSQL)

`.env`:
```
DATABASE_URL="postgresql://app:app@database:5432/github_cv?serverVersion=16&charset=utf8"
```

### 5. Crear entidades base

- `App\Entity\User`
- `App\Entity\GithubAccount`
- `App\Entity\Repository`
- `App\Entity\SyncJob`

### 6. Migraciones

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### 7. Estructura de directorios

```
src/
├── Controller/
├── Entity/
├── Repository/
├── Service/
│   ├── GitHub/
│   └── Analysis/
│       ├── Provider/
│       └── Prompt/
├── Message/
├── MessageHandler/
├── Twig/Components/
└── Stimulus/
```

### 8. Layout base + Dashboard placeholder

- `templates/base.html.twig` — layout con Tailwind
- `templates/dashboard/index.html.twig` — página inicial
- `assets/controllers/hello_controller.js` — verificar Stimulus
- `assets/app.js` — entry point

## Verificación

```bash
# Comprobar que Symfony arranca
php bin/console about

# Verificar conexión BD
php bin/console doctrine:database:create --if-not-exists

# Verificar migraciones
php bin/console doctrine:migrations:status

# Verificar AssetMapper
php bin/console debug:asset-map

# Verificar Tailwind
php bin/console tailwind:build

# Verificar Messenger
php bin/console debug:messenger

# Navegador
open http://localhost:8000
```
