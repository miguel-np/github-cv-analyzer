# AGENTS.md — GitHub CV Analyzer

Instrucciones para agentes de IA que trabajen en este proyecto. Cumplir estrictamente.

---

## Stack y versiones

| Componente | Versión / Detalle |
|---|---|
| PHP | 8.4+ (strict types en todos los archivos) |
| Symfony | 7.4 LTS |
| PostgreSQL | 16 — queries Doctrine con soporte jsonb |
| Tailwind CSS | v4 (vía symfonycasts/tailwind-bundle) |
| Symfony UX | Turbo, Stimulus |
| AssetMapper | Para JS/CSS (no Webpack Encore) |
| Messenger | Symfony Messenger con transporte Doctrine |
| Scheduler | Symfony Scheduler para tareas periódicas |

### Variables de entorno relevantes

| Variable | Propósito | Default |
|---|---|---|
| `DATABASE_URL` | Conexión PostgreSQL | — |
| `SYNC_INTERVAL` | Frecuencia de auto-sync | `12 hours` |
| `GITHUB_WEBHOOK_SECRET` | Secreto para webhooks de GitHub | — |
| `APP_ENV` | Entorno (`dev`, `test`, `prod`) | `dev` |
| `APP_SECRET` | Secreto de aplicación | — |

> **Nota**: Las claves de proveedores LLM (OpenAI, Anthropic, Ollama) no son variables de entorno. Se configuran por usuario vía la UI y se almacenan en el campo `settings` (jsonb) de la entidad `User`.

---

## Reglas de código

### Ubicación de clases

```
src/
├── Controller/         # Solo orquestación, sin lógica de negocio
├── Entity/             # Entidades Doctrine, sin lógica más allá de getters/setters
├── Repository/         # Queries Doctrine, métodos findBy* personalizados
├── Service/            # Toda la lógica de negocio
│   ├── GitHub/          # Cliente GitHub y servicios de sincronización
│   ├── Analysis/        # Motor de análisis con LLMs
│   │   ├── Provider/    # OllamaProvider, OpenAiProvider, AnthropicProvider
│   │   ├── Prompt/      # Templates de prompts
│   │   └── Shared/      # Helpers (JsonHelper, TechnologyHelper)
│   └── Health/          # Health check
├── Message/            # Clases DTO de Messenger (final, readonly)
├── MessageHandler/     # Handlers asíncronos
├── Schedule.php        # Tarea periódica (Scheduler) — auto-sync diario
└── Kernel.php
```

### Convenciones

- **PHPStan level 8** como objetivo (strict types en todos los archivos)
- Respuestas de controlador: solo `return $this->render()` o `RedirectResponse`
- Entities con `#[ORM\Entity]` y repositorio asociado
- Servicios inyectados por constructor con `readonly`
- Sin mutación de entidades en controladores — usar servicios para lógica de escritura
- Mensajes Messenger: `final readonly class` con propiedades públicas
- Twig: templates con PascalCase
- Tailwind: utility-first, evitar CSS custom innecesario
- Controladores Stimulus en `assets/controllers/` (AssetMapper)

### Git conventions

**Commits**: Conventional Commits v1.0.0 en español.

```
<type>(<scope>): <description>
```

| Type | Propósito |
|---|---|
| `feat` | Nueva funcionalidad |
| `fix` | Corrección de bug |
| `refactor` | Reestructuración de código |
| `docs` | Documentación |
| `test` | Tests |
| `chore` | Dependencias, tooling |
| `perf` | Mejora de rendimiento |
| `ci` | Configuración de CI/CD |
| `style` | Formato solo |

Descripción en minúscula, modo imperativo, sin punto final, máximo 72 caracteres.

**Ramas**: `<type>/<short-description>` (kebab-case).

```
feature/nombre-funcionalidad
fix/descripcion-bug
hotfix/bug-urgente
refactor/componente-objetivo
test/descripcion-tests
chore/mejora-tooling
docs/cambio-docs
security/mejora-seguridad
```

Nunca commit directo a `main`. Eliminar ramas después del merge.

### Patrones

- **Token de GitHub**: cifrado con libsodium antes de persistir en BD
- **LLM multi-provider**: interfaz `LlmClientInterface` con implementaciones por provider
- **LlmFactory**: `LlmFactoryInterface` crea el provider adecuado según configuración del usuario
- **Caché de análisis**: cada commit se analiza una vez (SHA único), resultado cacheado
- **Rate limiting**: respetar límites de GitHub API (caché + delays)
- **Async primero**: toda operación costosa (sync, análisis) va por Messenger
- **Scheduler**: auto-sync diario configurable vía `SYNC_INTERVAL` (default: 12h)

---

## Comandos disponibles

```bash
# Docker (desarrollo sin PHP local)
docker compose up -d                    # Levantar PHP + PostgreSQL
docker compose exec php bash            # Shell dentro del contenedor PHP

# Symfony (dentro del contenedor o local)
php bin/console make:entity             # Crear entidad
php bin/console make:migration          # Generar migración
php bin/console doctrine:migrations:migrate
php bin/console messenger:consume async -vv
php bin/console scheduler:run --verbose  # Procesar tareas programadas
php bin/console cache:clear

# AssetMapper
php bin/console importmap:require       # Añadir paquete JS
php bin/console tailwind:build --watch  # Compilar Tailwind en dev
php bin/console asset-map:compile       # Compilar assets para prod

# Calidad
php vendor/bin/phpstan analyse          # Análisis estático (level 8)
php vendor/bin/php-cs-fixer fix         # Formateo
php vendor/bin/phpunit                  # Tests
php bin/console lint:twig templates/    # Lint Twig
```

### Quality gates (obligatorios antes de PR)

```bash
php vendor/bin/php-cs-fixer fix    # Zero violations
php vendor/bin/phpstan analyse     # Zero errors
php vendor/bin/phpunit             # 100% pass, >=80% cobertura
php bin/console lint:twig templates/
```

---

## Tests

- **Framework**: PHPUnit 13
- **Factories**: Foundry (zenstruck/foundry) para tests funcionales
- **BD en tests**: DamaDoctrineTestBundle (transacciones aisladas)
- **Mocks**: Los tests de integración del motor LLM usan mocks del provider

### Comandos

```bash
php vendor/bin/phpunit                          # Suite completa
php vendor/bin/phpunit --testsuite unit         # Solo unitarios
php vendor/bin/phpunit --testsuite integration  # Solo integración
php vendor/bin/phpunit --coverage-html var/coverage  # Cobertura HTML
```

### Estructura de tests

```
tests/
├── Unit/           # Clases aisladas, sin BD ni contenedor (archivos planos)
└── Integration/    # Con contenedor Symfony + BD de test (archivos planos)
```

---

## No hacer

- No usar `bin/console make:controller` con lógica — los controladores delegan en servicios
- No exponer secretos en entities ni usar `json_encode` en entities — usar tipos Doctrine json
- No commits con credenciales o tokens reales
- No crear nuevos bundles sin discutirlo primero
- No modificar `composer.json` añadiendo dependencias sin verificar compatibilidad con PHP 8.4+ y Symfony 7.4
- No `var_dump` / `dd()` en código mergeado — usar el logger de Symfony
- No hacer push de `.env.local` o archivos con secretos
