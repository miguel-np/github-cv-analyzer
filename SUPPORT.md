# Support

## Getting Help

- **Documentation**: Start with the [README](README.md) for setup and usage instructions
- **Issues**: Search [existing issues](https://github.com/miguel-np/github-cv-analyzer/issues) and open a new one if needed
- **Discussions**: Ask questions on [GitHub Discussions](https://github.com/miguel-np/github-cv-analyzer/discussions)

## Common Issues

### Docker
Ensure Docker Compose v2+ is installed and port 8000 is free.

### Database
PostgreSQL 16+ is required. The `DATABASE_URL` environment variable must be correctly configured.

### LLM Providers
- **Ollama**: Ensure the service is running and the model is pulled (`ollama pull llama3.2`)
- **OpenAI / Anthropic**: Verify API keys are set in Settings or environment variables

## Reporting Bugs

Use the [Bug Report](https://github.com/miguel-np/github-cv-analyzer/issues/new?template=bug_report.md) template with detailed steps to reproduce.
