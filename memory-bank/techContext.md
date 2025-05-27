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

## Custom AI Conversation Module (NEW)
### Module Information
- **Name**: ai_conversation
- **Location**: web/modules/custom/ai_conversation/
- **Version**: 1.0.0
- **Dependencies**: Core AI modules, User module

### Database Schema
- **ai_conversation**: Main conversation entities
  - id, uuid, title, user_id, default_thread_id, created, updated
  - Indexes: user_id, created
- **ai_conversation_thread**: Individual conversation threads  
  - id, uuid, conversation_id, parent_thread_id, branch_point_message_id
  - title, messages (LONGTEXT JSON), created, updated
  - Indexes: conversation_id, parent_thread_id, created

### Entity Architecture
- **AiConversation**: ContentEntityBase with user ownership
- **AiConversationThread**: ContentEntityBase with conversation relationship
- **Interfaces**: AiConversationInterface, AiConversationThreadInterface
- **Access Control**: Custom handlers with granular permissions
- **List Builders**: Admin and user interfaces for entity management

### Service Layer
- **AiConversationManager**: Core service for conversation/thread operations
  - Create, update, delete conversations and threads
  - Branch thread creation with message copying
  - Session context management for AI integration
- **Event Integration**: AiAssistantSubscriber for AI interaction capture

### Permission System (11 permissions)
- **Conversations**: view own/any, create, edit own/any, delete own/any
- **Threads**: view, create, edit, delete
- **Admin**: administer ai conversations

### Routes & Controllers
- **User Interface**: /user/conversations
- **Admin Interface**: /admin/content/ai-conversations
- **Conversation Management**: Resume, branch, navigation
- **API Endpoints**: REST-style operations for conversation management

### Integration Points
- **AI Assistant API**: Event subscriber hooks for message capture
- **User System**: Per-user conversation isolation
- **Session Management**: Load conversation context into chat sessions
- **Provider Tracking**: Store AI model/provider metadata per message

### Technical Features
- **Message Structure**: JSON storage with full metadata and timestamps
- **Branching Logic**: Copy conversation history up to branch point
- **Thread Hierarchy**: Parent/child relationships for conversation trees
- **Resume Functionality**: Load any conversation/thread into active session
- **Search Support**: Framework for conversation search and filtering

## Completed Implementation Areas ✅
1. ✅ AI provider configuration interface
2. ✅ Conversation persistence mechanisms (Custom module)
3. ✅ User permission integration (11 granular permissions)
4. ✅ Block placement and theming (DeepChat integration)
5. ✅ API key management workflow (DDEV environment variables)
6. ✅ Custom entity system for conversation storage
7. ✅ Branching conversation thread support
8. ✅ Service layer for conversation management
9. ✅ Access control and user isolation
10. ✅ Admin and user interfaces
