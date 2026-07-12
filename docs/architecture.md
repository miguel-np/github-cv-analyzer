# Arquitectura — GitHub CV Analyzer

## Visión general

```
┌─────────────┐     ┌──────────────────────────────────────┐
│   Usuario   │────▶│  Twig + Turbo + Stimulus (Frontend)  │
│ (Navegador) │     └──────────────┬───────────────────────┘
└─────────────┘                    │
                                   ▼
┌──────────────────────────────────────────────────────────┐
│                  Symfony 7.4 (Backend)                     │
│                                                          │
│  ┌──────────┐  ┌──────────┐  ┌───────────────────────┐  │
│  │Controllers│  │ Messenger│  │    LLM Engine         │  │
│  │(thin)    │  │(async)   │  │  ┌─────────────────┐  │  │
│  └────┬─────┘  │          │  │  │ CommitAnalyzer  │  │  │
│       │        │┌────────┐│  │  ├─────────────────┤  │  │
│  ┌────▼─────┐  ││Queue   ││  │  │ TechDetector    │  │  │
│  │ Services │  │├────────┤│  │  ├─────────────────┤  │  │
│  │(business)│  ││Handler ││──┤  │ CvImprovement   │  │  │
│  └────┬─────┘  │└────────┘│  │  └────────┬────────┘  │  │
│       │        └──────────┘  │           │           │  │
│  ┌────▼──────────────────────▼───────────▼────────┐  │  │
│  │              Doctrine ORM + PostgreSQL          │  │  │
│  └──────────────────────┬─────────────────────────┘  │  │
│                         │                            │  │
│  ┌──────────────────────▼─────────────────────────┐  │  │
│  │             GitHub API Client                   │  │  │
│  │           (knplabs/github-api)                  │  │  │
│  └─────────────────────────────────────────────────┘  │  │
└──────────────────────────────────────────────────────────┘
         │                    │
         ▼                    ▼
   ┌──────────┐     ┌─────────────────┐
   │  GitHub  │     │  LLM Providers  │
   │  API     │     │  Ollama/OpenAI  │
   └──────────┘     └─────────────────┘
```

## Diagrama de base de datos

```
users ──1:N──▶ sync_jobs

users ──1:N──▶ github_accounts
                 │
                 └──N:N──▶ github_repos ──1:N──▶ commits
                                     │
                                     ├──1:N──▶ pull_requests
                                     │
                                     ├──1:N──▶ issues
                                     │
                                     └──N:N──▶ technologies

commits ──1:1──▶ analysis_results
```

## Entidades

### User
Tabla de usuario local (single-user en MVP, multi-user futuro).
- `id`, `email`, `settings (jsonb)`

### GithubAccount
Una cuenta de GitHub vinculada (token cifrado con libsodium).
- `id`, `user_id`, `github_username`, `encrypted_token`, `last_synced_at`

### GithubRepo
Repositorio de GitHub donde el usuario ha contribuido.
- `id`, `github_id`, `full_name`, `description`, `language`
- `stars`, `forks`, `is_fork`, `is_private`
- `metadata (jsonb)` — topics, license, homepage...
- `created_at`, `updated_at`, `last_synced_at`

### Commit
Un commit del usuario en un repositorio.
- `id`, `repository_id`, `sha`, `author_email`
- `message`, `date`
- `additions`, `deletions`, `files_changed`
- `is_merge_commit`, `diff_stats (jsonb)`

### AnalysisResult
Resultado del análisis de IA para un commit.
- `id`, `commit_id`, `provider (string)`
- `classification (jsonb)` — tipo, complejidad, tecnologías, tags...
- `tokens_used`, `cost`, `duration_ms`
- `created_at`

### PullRequest / Issue
PRs e issues creados por el usuario.
- Datos estándar de GitHub + `metadata (jsonb)`

### Technology
Lenguajes, frameworks y herramientas detectadas.
- `id`, `name`, `category`, `version`

### SyncJob
Trabajo de sincronización para tracking y reintentos.
- `id`, `github_account_id`, `type (full|incremental)`
- `status (pending|running|completed|failed)`
- `items_processed`, `error_log (jsonb)`
- `started_at`, `finished_at`

## Motor de LLM

### Interfaz

```php
interface LlmClientInterface
{
    public function chat(string $systemPrompt, string $userPrompt, ?array $jsonSchema = null): array;
    public function getProviderName(): string;
    public function getModelName(): string;
}
```

### Factory

```php
interface LlmFactoryInterface
{
    public function create(User $user): LlmClientInterface;
}
```

`LlmFactory` instancia el provider adecuado según la configuración del usuario (settings jsonb).

### Providers

- **OllamaProvider**: LLM local, endpoint configurable, modelo configurable (default: `llama3.2`)
- **OpenAiProvider**: API de OpenAI, modelos GPT-4o/GPT-4o-mini (default: `gpt-4o-mini`)
- **AnthropicProvider**: API de Anthropic, Claude (default: `claude-3-5-haiku-latest`)

### Prompt templates

Cada tipo de análisis tiene su clase de prompt que construye el mensaje estructurado:

- **CommitAnalyzerPrompt**: clasifica un commit a partir de su mensaje + diff
- **CvImprovementPrompt**: sugiere mejoras de CV basadas en todas las contribuciones

### Flujo de análisis

```
1. SyncService obtiene commits nuevos de GitHub
2. Por cada commit → dispatch(AnalyzeCommitMessage)
3. Messenger consume → AnalyzeCommitHandler
4. Handler obtiene CommitAnalyzer via DI
5. CommitAnalyzer construye prompt → llama a LlmClient
6. Respuesta JSON parseada → persistida en AnalysisResult
7. Cache commit SHA → no se re-analiza
```

## Mensajería asíncrona

### Mensajes

| Mensaje | Descripción | Handler |
|---------|-------------|---------|
| `SyncAccountMessage` | Sincronizar todos los repos de una cuenta | `SyncAccountHandler` |
| `SyncRepositoryMessage` | Sincronizar commits/PRs/issues de un repo | `SyncRepositoryHandler` |
| `AnalyzeCommitMessage` | Analizar un commit con LLM | `AnalyzeCommitHandler` |
| `TriggerDailySyncMessage` | Señal de sync diario automático (Scheduler) | `TriggerDailySyncHandler` |

### Transporte

Doctrine transport en desarrollo (simple, sin dependencias extra).
Redis/RabbitMQ recomendado para producción.

## Scheduler (auto-sync diario)

```php
#[AsSchedule]
class Schedule implements ScheduleProviderInterface
{
    public function getSchedule(): SymfonySchedule
    {
        return (new SymfonySchedule())
            ->stateful($this->cache)
            ->processOnlyLastMissedRun(true)
            ->add(
                RecurringMessage::every(
                    $this->syncInterval,       // SYNC_INTERVAL (default: '12 hours')
                    new TriggerDailySyncMessage()
                )
            );
    }
}
```

El `TriggerDailySyncHandler` itera todas las `GithubAccount` activas y dispara `SyncAccountMessage` para cada una.

Ejecución manual: `php bin/console scheduler:run --verbose`
