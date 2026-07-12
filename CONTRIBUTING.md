# Contributing to GitHub CV Analyzer

Thank you for your interest in contributing. This document outlines the conventions and workflow for the project.

## Code of Conduct

Be respectful, constructive, and collaborative. Harassment of any kind will not be tolerated.

## How to contribute

### Reporting bugs

1. Search [existing issues](https://github.com/miguel-np/github-cv-analyzer/issues) to avoid duplicates
2. Use the **Bug report** template
3. Include steps to reproduce, expected vs actual behavior, and environment details

### Suggesting features

1. Search [existing issues](https://github.com/miguel-np/github-cv-analyzer/issues) for similar ideas
2. Use the **Feature request** template
3. Explain the problem, proposed solution, and alternatives

### Submitting code

1. **Fork** the repository and create a branch from `main`
2. Follow the [branch naming convention](#branch-naming)
3. Write code following the [project conventions](AGENTS.md)
4. Add/update tests for your changes
5. Run the full quality suite before submitting:

```bash
php vendor/bin/php-cs-fixer fix
php vendor/bin/phpstan analyse
php vendor/bin/phpunit
php bin/console lint:twig templates/
```

6. Commit using [Conventional Commits](#commit-messages)
7. Push and open a Pull Request using the PR template

## Development setup

```bash
# Docker (recommended)
docker compose up -d
docker compose exec php bash

# Inside container
composer install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console tailwind:build --watch &
php bin/console messenger:consume async -vv &
symfony server:start
```

## Git conventions

### Branch naming

Format: `<type>/<short-description>` (lowercase kebab-case, 2-4 words)

| Prefix | Purpose |
|--------|---------|
| `feature/` | New functionality |
| `fix/` | Bug fix |
| `hotfix/` | Urgent production fix |
| `refactor/` | Code restructuring |
| `test/` | Test additions |
| `chore/` | Tooling, dependencies |
| `docs/` | Documentation |
| `security/` | Security improvements |

### Commit messages

Follow [Conventional Commits v1.0.0](https://www.conventionalcommits.org/):

```
<type>(<scope>): <description>

[optional body]

[optional BREAKING CHANGE: description]
```

| Type | Purpose |
|------|---------|
| `feat` | New feature |
| `fix` | Bug fix |
| `refactor` | Code restructure |
| `docs` | Documentation |
| `test` | Tests |
| `chore` | Tooling/deps |
| `perf` | Performance |
| `ci` | CI/CD |
| `style` | Formatting only |

Rules:
- Description in Spanish (es) for this project
- Lowercase, imperative mood
- No period at end of first line
- Max 72 characters for the summary line

## Code review

All PRs require review before merging. The reviewer will check:

- [ ] PHPStan level 8: no errors
- [ ] PHP-CS-Fixer: no violations
- [ ] Tests pass with >80% coverage
- [ ] Code follows project conventions (see AGENTS.md)
- [ ] No secrets or tokens exposed
- [ ] Documentation updated if needed

## Questions?

Open a [Discussion](https://github.com/miguel-np/github-cv-analyzer/discussions) or tag `@miguel-np` in your issue.
