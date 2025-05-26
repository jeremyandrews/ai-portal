# Drupal 11 AI Portal - Project Brief

## Project Overview
Building a Drupal 11-powered AI portal that allows multiple users to interact with various AI chatbots through a web interface.

## Core Requirements
- **Platform**: Drupal 11 with AI module integration
- **Development**: DDEV for local development
- **Production**: Kubernetes deployment with Helm charts
- **AI Providers**: OpenAI and Anthropic (initially)
- **Users**: Multi-user with self-registration and admin approval
- **Features**: Chat interface, conversation history, multiple AI bots
- **Deployment**: Drupal Recipe for easy redeployment

## Key Constraints
- Build as portable Drupal application (not DDEV-dependent)
- Leverage existing AI module capabilities (avoid reinventing)
- Focus on modern Drupal patterns (8+)
- Package as Recipe for company-wide deployment

## Success Criteria
1. Working Drupal 11 installation with AI module
2. Functional chat interface with OpenAI and Anthropic
3. User registration and conversation persistence
4. Deployable Recipe package
5. Documentation for K8s deployment

## User Context
- 20+ years Drupal experience (D4-D7)
- Limited experience with modern Drupal (8+)
- Needs guidance on contemporary patterns
- Experienced with deployment and architecture
