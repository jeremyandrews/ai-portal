<?php

declare(strict_types=1);

namespace Drupal\ai_conversation\EventSubscriber;

use Drupal\ai_conversation\Service\AiConversationManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for AI Assistant API integration.
 */
class AiAssistantSubscriber implements EventSubscriberInterface {

  /**
   * The AI conversation manager.
   *
   * @var \Drupal\ai_conversation\Service\AiConversationManager
   */
  protected $conversationManager;

  /**
   * Constructs a new AiAssistantSubscriber object.
   *
   * @param \Drupal\ai_conversation\Service\AiConversationManager $conversation_manager
   *   The conversation manager.
   */
  public function __construct(AiConversationManager $conversation_manager) {
    $this->conversationManager = $conversation_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // For now, return empty array. We'll add AI Assistant API events later
    // when we integrate with the actual AI module events.
    return [];
  }

  /**
   * Handles AI message requests to capture conversations.
   *
   * This will be implemented when we integrate with AI Assistant API events.
   */
  public function onAiMessageRequest($event) {
    // TODO: Implement conversation capture logic
    // This will capture user messages and AI responses to store in our entities
  }

  /**
   * Handles AI message responses to store conversation data.
   *
   * This will be implemented when we integrate with AI Assistant API events.
   */
  public function onAiMessageResponse($event) {
    // TODO: Implement response storage logic
    // This will store AI assistant responses with provider/model metadata
  }

}
