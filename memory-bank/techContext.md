# Technical Context - Drupal 11 AI Portal

## Development Environment
- **Platform**: macOS with OrbStack (Docker alternative)
- **Container Management**: DDEV v1.24.1
- **PHP Version**: 8.3+ (via DDEV)
- **Database**: MariaDB 10.11 (via DDEV)
- **Web Server**: Apache/Nginx (via DDEV)

## Drupal Installation
- **Version**: Drupal 11.1.7
- **Installation Method**: drupal/recommended-project via Composer
- **Document Root**: web/
- **Site URL**: http://portal.ddev.site
- **Admin User**: admin/admin

## AI Module Architecture
### Core Modules Installed
- **ai**: Base AI module providing plugin system
- **ai_assistant_api**: API for LLM interactions and plugin-based actions
- **ai_chatbot**: Frontend chatbot interface using Deepchat
- **provider_openai**: OpenAI integration
- **provider_anthropic**: Anthropic Claude integration
- **key**: Secure API key management

### Dependencies
- **league/commonmark**: Required for proper chatbot response rendering
- **drush/drush**: Command-line administration

## AI Module Capabilities Discovered
### Frontend Components
- Deepchat-based chat interface
- Block-based deployment
- Real-time messaging
- Markdown response rendering

### Backend Architecture
- Plugin-based provider system
- Secure API key management via Key module
- Event-driven architecture
- Service-based API interactions

### Available Providers
- OpenAI (GPT models)
- Anthropic (Claude models)
- Groq, Hugging Face, Mistral, Ollama, LM Studio

## Modern Drupal Patterns in Use
- **Configuration Management**: YAML-based exportable config
- **Entity API**: Typed data system with field API
- **Dependency Injection**: Service container architecture
- **Twig Templating**: Secure template system
- **Composer Architecture**: All dependencies managed via Composer
- **Plugin System**: Extensible provider architecture

## Next Investigation Areas
1. AI provider configuration interface
2. Conversation persistence mechanisms
3. User permission integration
4. Block placement and theming
5. API key management workflow
