# Changelog

Todos los cambios notables del proyecto se documentan en este archivo.

El formato sigue [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

- Exportación de informe en PDF/HTML (Fase 6)
- Filtros por tecnología, fecha, tipo de contribución (Fase 5)
- Comparativa con perfiles de referencia (Fase 6)
- Métricas de mejora a lo largo del tiempo (Fase 6)

## [0.6.0] — 2026-07

### Added

- Página de análisis de CV con sugerencias generadas por IA
- Detección de gaps (áreas débiles, tecnologías ausentes)
- Recomendaciones de proyectos o tecnologías a aprender

### Fixed

- Stats muertos en repositorios y dashboard
- Type hint incorrecto en CvAnalysisController
- Caché de análisis no invalidando resultados stale

## [0.5.0] — 2026-07

### Added

- Dashboard principal con stats agregadas (commits, repos, PRs, issues)
- Línea de tiempo de contribuciones (Chart.js)
- Tech radar: tecnologías más usadas (Chart.js radar)
- Distribución por tipo de contribución (Chart.js doughnut)
- Página de detalle de repositorio con commits analizados
- Modo oscuro
- Responsive design con Tailwind

### Fixed

- `reduce` inexistente en OrganizationAnalyzer
- N+1 queries en DashboardController
- SQL nativo con operador `?|` de jsonb

## [0.4.0] — 2026-07

### Added

- Motor de análisis con LLMs multi-proveedor
- Interfaz `LlmClientInterface` y `LlmFactoryInterface`
- `OllamaProvider` (local)
- `OpenAiProvider` (GPT-4o/GPT-4o-mini)
- `AnthropicProvider` (Claude)
- Sistema de prompt templates con structured output (JSON Schema)
- `CommitAnalyzer` — clasificación de commits
- `TechnologyDetector` — extracción de stack tecnológico
- `CvImprovementAnalyzer` — sugerencias de CV
- Caché de análisis por commit SHA
- Tracking de costes y tokens por provider
- Entidad `AnalysisResult`

### Fixed

- Dead code y DRY en providers
- Robustez de providers ante errores de red
- Null safety en respuestas de LLM

## [0.3.0] — 2026-07

### Added

- Recolección de datos asíncrona con Symfony Messenger
- Transporte Doctrine para colas de mensajes
- Mensajes: `SyncAccountMessage`, `SyncRepositoryMessage`, `TriggerDailySyncMessage`
- Handlers con rate limiting y reintentos
- Sincronización de commits con paginación
- Sincronización de PRs e issues
- Sincronización incremental (`last_synced_at`)
- Interfaz para disparar sync y ver progreso

### Fixed

- Correcciones de rendimiento y arquitectura post code review
- Rate limiting en handlers

## [0.2.0] — 2026-07

### Added

- Integración con GitHub API (`knplabs/github-api`)
- Servicio `GitHubClient` con autenticación por token
- Cifrado/descifrado de token con libsodium
- Formulario de settings para configurar token
- Endpoint de verificación de token
- Sincronización básica de repositorios

### Fixed

- Correcciones de arquitectura y rendimiento

## [0.1.0] — 2026-07

### Added

- Proyecto Symfony 7.4 con stack de desarrollo completo
- Docker Compose con PHP 8.5 + PostgreSQL 16
- Bundles: ORM, Messenger, AssetMapper, Turbo, Stimulus, Tailwind, Scheduler
- Entidades base (User, GithubAccount, GithubRepo, SyncJob, Commit, PullRequest, Issue, Technology, AnalysisResult)
- Migraciones iniciales
- Estructura de directorios `src/`
- Layout base con Tailwind y navegación
- Página dashboard placeholder
