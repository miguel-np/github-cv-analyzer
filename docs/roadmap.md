# Roadmap — GitHub CV Analyzer

## Resumen de fases

| # | Fase | Esfuerzo | Complejidad | Impacto | Versión |
|---|------|:--------:|:-----------:|:-------:|:-------:|
| 1 | Foundation | 3-4h | Baja | Crítico | v0.1.0 |
| 2 | GitHub Integration | 4-5h | Media | Alto | v0.2.0 |
| 3 | Data Collection | 8-10h | Media | Crítico | v0.3.0 |
| 4 | LLM Analysis Engine | 6-8h | Alta | Muy Alto | v0.4.0 |
| 5 | Dashboard & UI | 8-12h | Media | Muy Alto | v0.5.0 |
| 6 | CV Analysis | 4-6h | Baja | Alto | v0.6.0 |
| 7 | Scheduler & Automation | 1-2h | Baja | Medio | v0.6.0 |

**Total estimado**: 36-44 horas | **Versión actual**: v0.6.0

## Progreso

```
✓ F1  ✓ F2  ✓ F3  ✓ F4  ✓ F5  ✓ F6  ✓ F7
───  ───  ───  ───  ───  ═══  ═══
 Fase completada (✓)  │  En progreso (═══)
```

## Dependencias

```
F1 ──▶ F2 ──▶ F3 ──▶ F4 ──▶ F5 ──▶ F6
  │                      │
  └──────────────────────┘  (F5 puede iniciar en paralelo con mock data)
```

---

## Fase 1: Foundation ✓

**Objetivo**: Proyecto Symfony 7.4 funcional con stack de desarrollo completo.

**Entregables**:
- [x] Documentación de proyecto (AGENTS.md, README.md, docs/)
- [x] Proyecto Symfony 7.4 creado con `composer create-project`
- [x] Docker Compose con PHP 8.5 + PostgreSQL 16
- [x] Bundles instalados: ORM, Messenger, AssetMapper, Turbo, Stimulus, Tailwind, Scheduler
- [x] Doctrine configurado con PostgreSQL
- [x] Entidades base creadas (User, GithubAccount, GithubRepo, SyncJob, Commit, PullRequest, Issue, Technology, AnalysisResult)
- [x] Migraciones iniciales generadas y ejecutadas
- [x] Estructura de directorios: Service/ (GitHub/, Analysis/Provider/, Analysis/Prompt/, Analysis/Shared/), Message/, MessageHandler/
- [x] AssetMapper + Turbo + Stimulus + Tailwind funcionando
- [x] Layout base con Tailwind y navegación
- [x] Página dashboard placeholder

Ver detalle en [docs/phases/01-foundation.md](phases/01-foundation.md).

---

## Fase 2: GitHub Integration ✓

**Objetivo**: Conexión con GitHub API y almacenamiento seguro del token.

**Entregables**:
- [x] Instalación e integración de `knplabs/github-api`
- [x] Servicio `GitHubClient` con autenticación por token
- [x] Cifrado/descifrado de token con libsodium
- [x] Formulario de settings para configurar token
- [x] Endpoint de verificación de token (lista repos del usuario)
- [x] Sincronización básica: listar repos y guardar en BD

---

## Fase 3: Data Collection ✓

**Objetivo**: Sistema asíncrono de recolección de datos de GitHub.

**Entregables**:
- [x] Configuración de Symfony Messenger (Doctrine transport)
- [x] Mensajes: `SyncAccountMessage`, `SyncRepositoryMessage`, `TriggerDailySyncMessage`
- [x] Handlers con rate limiting y reintentos
- [x] Sincronización de commits por repositorio (paginación)
- [x] Sincronización de PRs e issues
- [x] Sincronización incremental (solo nuevos desde `last_synced_at`)
- [x] Entidades: Commit, PullRequest, Issue
- [x] Interfaz básica para disparar sync y ver progreso

---

## Fase 4: LLM Analysis Engine ✓

**Objetivo**: Motor de análisis de contribuciones usando IA.

**Entregables**:
- [x] Interfaz `LlmClientInterface` y `LlmFactoryInterface`
- [x] `LlmFactory` — crea provider según configuración del usuario
- [x] `OllamaProvider` (local, gratis)
- [x] `OpenAiProvider` (nube, GPT-4o)
- [x] `AnthropicProvider` (nube, Claude)
- [x] Sistema de prompt templates con structured output (JSON Schema)
- [x] `CommitAnalyzer` — clasifica commits, detecta tecnologías
- [x] `TechnologyDetector` — extrae stack tecnológico por repo
- [x] `CvImprovementAnalyzer` — sugiere mejoras de CV
- [x] Caché de análisis por commit SHA
- [x] Tracking de costes y tokens por provider
- [x] Entidad `AnalysisResult`
- [x] Interfaz de settings para configurar provider IA

---

## Fase 5: Dashboard & UI ✓ (parcial)

**Objetivo**: Interfaz de usuario completa con estadísticas y visualizaciones.

**Entregables**:
- [x] Dashboard principal: stats agregadas (commits, repos, PRs, issues)
- [x] Línea de tiempo de contribuciones (Chart.js)
- [x] Tech radar: tecnologías más usadas (Chart.js radar)
- [x] Distribución por tipo de contribución (Chart.js doughnut)
- [x] Página de detalle de repositorio con lista de commits analizados
- [x] Responsive design con Tailwind
- [x] Modo oscuro
- [ ] Filtros por tecnología, fecha, tipo de contribución
- [ ] Turbo Streams para actualizaciones en vivo durante sync

---

## Fase 6: CV Analysis & Export ✓ (parcial)

**Objetivo**: Análisis final del perfil y generación de informes.

**Entregables**:
- [x] Página de análisis de CV con sugerencias generadas por IA
- [x] Detección de gaps (áreas débiles, tecnologías ausentes)
- [x] Recomendaciones de proyectos o tecnologías a aprender
- [ ] Exportación de informe en PDF/HTML
- [ ] Comparativa con perfiles de referencia
- [ ] Métricas de mejora a lo largo del tiempo

---

## Fase 7: Scheduler & Automation ✓

**Objetivo**: Sincronización periódica automática sin intervención del usuario.

**Entregables**:
- [x] Configuración de Symfony Scheduler
- [x] Clase `Schedule` con `TriggerDailySyncMessage` recurrente
- [x] Intervalo configurable vía `SYNC_INTERVAL` (default: 12h)
- [x] `TriggerDailySyncHandler` itera todas las cuentas activas
- [x] Persistencia en caché para evitar duplicados entre deploys
- [x] Procesamiento de solo la última ejecución perdida

---

## Próximas iteraciones

### v0.7.0 — Pulido y filtros
- [ ] Filtros avanzados en dashboard (tecnología, fecha, tipo)
- [ ] Turbo Streams para updates en vivo
- [ ] Búsqueda de repositorios y commits
- [ ] Paginación avanzada en listados

### v0.8.0 — Export y reportes
- [ ] Exportación de informe en PDF
- [ ] Comparativa con perfiles de referencia
- [ ] Métricas de evolución temporal
- [ ] Sharing de dashboard público
