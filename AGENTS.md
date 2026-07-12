# AGENTS.md — GitHub CV Analyzer

Instrucciones para agentes de IA que trabajen en este proyecto. Cumplir estrictamente.

## Stack y versiones

- Symfony 7.4 (LTS) + PHP 8.5
- PostgreSQL 16 — queries Doctrine con soporte jsonb
- Tailwind CSS v4 (vía symfonycasts/tailwind-bundle)
- Symfony UX: Turbo, Stimulus
- AssetMapper para JS/CSS (no Webpack Encore)
- Symfony Messenger con transporte Doctrine para async
- Symfony Scheduler para tareas periódicas (auto-sync diario)

## Reglas de código

### Ubicación de clases

```
src/
├── Controller/         # Solo orquestación, sin lógica de negocio
├── Entity/             # Entidades Doctrine, sin lógica más allá de getters/setters
├── Repository/         # Queries Doctrine, métodos findBy* personalizados
├── Service/            # Toda la lógica de negocio
│   ├── GitHub/         # Cliente GitHub y servicios de sincronización
│   └── Analysis/       # Motor de análisis con LLMs
│       ├── Provider/   # OllamaProvider, OpenAiProvider, AnthropicProvider
│       ├── Prompt/     # Templates de prompts
│       └── Shared/     # Helpers (JsonHelper, TechnologyHelper)
├── Message/            # Clases DTO de Messenger (final, readonly)
├── MessageHandler/     # Handlers asíncronos
├── Twig/Components/    # Componentes Twig reutilizables
├── Stimulus/           # Controladores Stimulus
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
- Twig: templates con PascalCase, componentes en `components/` con snake_case
- Tailwind: utility-first, evitar CSS custom innecesario

### Patrones

- **Token de GitHub**: cifrado con libsodium antes de persistir en BD
- **LLM multi-provider**: interfaz `LlmClientInterface` con implementaciones por provider
- **LlmFactory**: `LlmFactoryInterface` crea el provider adecuado según configuración del usuario
- **Caché de análisis**: cada commit se analiza una vez (SHA único), resultado cacheados
- **Rate limiting**: respetar límites de GitHub API (caché + delays)
- **Async primero**: toda operación costosa (sync, análisis) va por Messenger
- **Scheduler**: auto-sync diario configurable vía `SYNC_INTERVAL` (default: 12h)

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
php vendor/bin/phpstan analyse          # Análisis estático
php vendor/bin/php-cs-fixer fix         # Formateo
php bin/console lint:twig templates/    # Lint Twig
```

## No hacer

- No usar `bin/console make:controller` con lógica — los controladores delegan en servicios
- No exponer secretos en entities ni usar `json_encode` en entities — usar tipos Doctrine json
- No commits con credenciales o tokens reales
- No crear nuevos bundles sin discutirlo primero
- No modificar `composer.json` añadiendo dependencias sin verificar compatibilidad con PHP 8.5 y Symfony 7.4

## Tests

- PHPUnit para unitarios
- Foundry (zenstruck/foundry) para factories en tests funcionales
- Los tests de integración del motor LLM usan mocks del provider
