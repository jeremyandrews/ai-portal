# System Patterns - Drupal 11 AI Portal

## AI Module Architecture Analysis

### Core Components Discovered
1. **AI Base Module**: Provides plugin system and core services
2. **AI Assistant API**: Manages LLM interactions and plugin-based actions
3. **AI Chatbot**: Frontend interface using Deepchat component
4. **Provider System**: Pluggable AI service integrations

### Key Architectural Patterns

#### Plugin-Based Provider System
- Each AI service (OpenAI, Anthropic, etc.) is a separate plugin
- Providers implement standardized interfaces for different operation types
- Configuration managed through Drupal's configuration system

#### Operation Types Supported
- **Chat**: Conversational AI interactions
- **Embeddings**: Vector embeddings for semantic search
- **Audio to Audio**: Voice-to-voice processing
- **Image and Audio to Video**: Multimodal processing
- Additional types available through provider plugins

#### Security & Configuration
- **Key Module Integration**: Secure API key management
- **Configuration Management**: YAML-based exportable settings
- **Permission System**: Drupal's role-based access control

### Frontend Architecture
- **Deepchat Component**: Modern chat interface
- **Block-based Deployment**: Drupal block system integration
- **Real-time Messaging**: AJAX-powered interactions
- **Markdown Rendering**: CommonMark library for response formatting

### Modern Drupal Patterns in Use
- **Service Container**: Dependency injection for AI services
- **Plugin Manager**: Extensible provider system
- **Event System**: Hooks for extending functionality
- **Configuration Entities**: Exportable AI assistant configurations
- **Twig Templates**: Secure templating for chat interface

## Current State Analysis

### What's Working
✅ Base AI module installed and enabled
✅ Provider modules (OpenAI, Anthropic) installed
✅ Chatbot frontend module enabled
✅ Key management system available
✅ Configuration interface accessible

### What Needs Configuration
🔧 AI provider API keys (OpenAI, Anthropic)
🔧 Default provider selection for each operation type
🔧 AI Assistant creation and configuration
🔧 Chat block placement and theming
🔧 User permissions and access control

### Gap Analysis: Custom Development Needed

#### Minimal Custom Development Required
The AI module provides most functionality we need:
- ✅ Chat interface (ai_chatbot)
- ✅ Multi-provider support (provider_openai, provider_anthropic)
- ✅ User authentication integration (Drupal core)
- ✅ Conversation persistence (likely built-in)

#### Potential Custom Requirements
- **User Registration Workflow**: Configure self-registration with admin approval
- **Custom Theming**: Style the chat interface to match portal design
- **Additional Permissions**: Fine-tune access control if needed
- **Recipe Creation**: Package configuration for deployment

## Next Implementation Steps

### Phase 1: Provider Configuration
1. Set up API keys for OpenAI and Anthropic
2. Configure default providers for Chat operations
3. Test basic AI connectivity

### Phase 2: Assistant Configuration
1. Create AI assistants using the AI Assistant API
2. Configure chat personalities/behaviors
3. Test multi-provider switching

### Phase 3: Frontend Integration
1. Place chat blocks on appropriate pages
2. Configure user permissions
3. Test conversation persistence

### Phase 4: User Management
1. Configure self-registration with admin approval
2. Set up appropriate user roles
3. Test multi-user scenarios

### Phase 5: Recipe Creation
1. Export all configuration
2. Create Drupal Recipe
3. Document deployment process

## Technical Insights

### Configuration Storage
- AI settings stored in `ai.settings.yml`
- Provider configurations in individual provider modules
- Assistant configurations as configuration entities

### API Integration Points
- Provider plugins implement `AiProviderInterface`
- Chat operations use standardized request/response format
- Event system allows for custom processing hooks

### Deployment Considerations
- API keys managed through Key module (environment variables)
- Configuration exportable via `drush config:export`
- Recipe format packages entire setup for redeployment
