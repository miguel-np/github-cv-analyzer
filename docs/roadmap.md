# Roadmap — GitHub CV Analyzer

## Resumen de fases

| # | Fase | Esfuerzo | Complejidad | Impacto |
|---|------|:--------:|:-----------:|:-------:|
| 1 | Foundation | 🟡 3-4h | 🟢 Baja | 🔴 Crítico | &#10003; |
| 2 | GitHub Integration | 🟡 4-5h | 🟡 Media | 🟠 Alto | &#10003; |
| 3 | Data Collection | 🔴 8-10h | 🟡 Media | 🔴 Crítico | &#10003; |
| 4 | LLM Analysis Engine | 🔴 6-8h | 🔴 Alta | 🟣 Muy Alto | &#10003; |
| 5 | Dashboard & UI | 🔴 8-12h | 🟡 Media | 🟣 Muy Alto | &#10003; |
| 6 | CV Analysis | 🟡 4-6h | 🔵 Baja | 🟠 Alto | &#10003; |

**Total estimado**: 35-42 horas

## Dependencias

```
F1 ──▶ F2 ──▶ F3 ──▶ F4 ──▶ F5 ──▶ F6
  │                      │
  └──────────────────────┘  (F5 puede iniciar en paralelo con mock data)
```

---

## Fase 1: Foundation

**Objetivo**: Proyecto Symfony 7 funcional con stack de desarrollo completo.

**Entregables**:
- [x] Documentación de proyecto (AGENTS.md, README.md, docs/)
- [ ] Proyecto Symfony 7 creado con `composer create-project`
- [ ] Docker Compose con PHP 8.3 + PostgreSQL 16
- [ ] Bundles instalados: ORM, Messenger, AssetMapper, Turbo, Stimulus, Tailwind
- [ ] Doctrine configurado con PostgreSQL
- [ ] Entidades base creadas (User, GithubAccount, Repository, SyncJob)
- [ ] Migraciones iniciales generadas y ejecutadas
- [ ] Estructura de directorios: Service/, Message/, MessageHandler/
- [ ] AssetMapper + Turbo + Stimulus + Tailwind funcionando
- [ ] Layout base con Tailwind y navegación
- [ ] Página dashboard placeholder

---

## Fase 2: GitHub Integration

**Objetivo**: Conexión con GitHub API y almacenamiento seguro del token.

**Entregables**:
- [x] Instalación e integración de `knplabs/github-api`
- [x] Servicio `GitHubClient` con autenticación por token
- [x] Cifrado/descifrado de token con libsodium
- [x] Formulario de settings para configurar token
- [x] Endpoint de verificación de token (lista repos del usuario)
- [x] Sincronización básica: listar repos y guardar en BD

---

## Fase 3: Data Collection

**Objetivo**: Sistema asíncrono de recolección de datos de GitHub.

**Entregables**:
- [ ] Configuración de Symfony Messenger (Doctrine transport)
- [ ] Mensajes: `SyncAccountMessage`, `SyncRepositoryMessage`
- [ ] Handlers con rate limiting y reintentos
- [ ] Sincronización de commits por repositorio (paginación)
- [ ] Sincronización de PRs e issues
- [ ] Sincronización incremental (solo nuevos desde `last_synced_at`)
- [ ] Entidades: Commit, PullRequest, Issue
- [ ] Interfaz básica para disparar sync y ver progreso

---

## Fase 4: LLM Analysis Engine

**Objetivo**: Motor de análisis de contribuciones usando IA.

**Entregables**:
- [ ] Interfaz `LlmClientInterface`
- [ ] `OllamaProvider` (local, gratis)
- [ ] `OpenAiProvider` (nube, GPT-4o)
- [ ] `AnthropicProvider` (nube, Claude)
- [ ] Sistema de prompt templates con structured output (JSON Schema)
- [ ] `CommitAnalyzer` — clasifica commits, detecta tecnologías
- [ ] `TechnologyDetector` — extrae stack tecnológico por repo
- [ ] `CvImprovementAnalyzer` — sugiere mejoras de CV
- [ ] Caché de análisis por commit SHA
- [ ] Tracking de costes y tokens por provider
- [ ] Entidad `AnalysisResult`
- [ ] Interfaz de settings para configurar provider IA

---

## Fase 5: Dashboard & UI

**Objetivo**: Interfaz de usuario completa con estadísticas y visualizaciones.

**Entregables**:
- [ ] Dashboard principal: stats agregadas (commits, repos, PRs, issues)
- [ ] Línea de tiempo de contribuciones (Chart.js)
- [ ] Tech radar: tecnologías más usadas (Chart.js radar)
- [ ] Distribución por tipo de contribución (Chart.js doughnut)
- [ ] Página de detalle de repositorio con lista de commits analizados
- [ ] Filtros por tecnología, fecha, tipo de contribución
- [ ] Turbo Streams para actualizaciones en vivo durante sync
- [ ] Responsive design con Tailwind
- [ ] Modo oscuro

---

## Fase 6: CV Analysis & Export

**Objetivo**: Análisis final del perfil y generación de informes.

**Entregables**:
- [ ] Página de análisis de CV con sugerencias generadas por IA
- [ ] Detección de gaps (áreas débiles, tecnologías ausentes)
- [ ] Recomendaciones de proyectos o tecnologías a aprender
- [ ] Exportación de informe en PDF/HTML
- [ ] Comparativa con perfiles de referencia (opcional)
- [ ] Métricas de mejora a lo largo del tiempo
