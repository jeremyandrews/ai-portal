<?php

declare(strict_types=1);

namespace Drupal\ai_conversation\EventSubscriber;

use Drupal\ai\Event\PostGenerateResponseEvent;
use Drupal\ai\Event\PreGenerateResponseEvent;
use Drupal\ai_conversation\Service\AiConversationManager;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;
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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Temporary storage for conversation IDs by request thread ID.
   *
   * @var array
   */
  protected $conversationMap = [];

  /**
   * Constructs a new AiAssistantSubscriber object.
   *
   * @param \Drupal\ai_conversation\Service\AiConversationManager $conversation_manager
   *   The conversation manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(
    AiConversationManager $conversation_manager,
    AccountProxyInterface $current_user,
    LoggerInterface $logger
  ) {
    $this->conversationManager = $conversation_manager;
    $this->currentUser = $current_user;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      PreGenerateResponseEvent::EVENT_NAME => 'onPreGenerateResponse',
      PostGenerateResponseEvent::EVENT_NAME => 'onPostGenerateResponse',
    ];
  }

  /**
   * Handles pre-generate response events to capture user messages.
   *
   * @param \Drupal\ai\Event\PreGenerateResponseEvent $event
   *   The pre-generate response event.
   */
  public function onPreGenerateResponse(PreGenerateResponseEvent $event) {
    // Only capture chat operations.
    if ($event->getOperationType() !== 'chat') {
      return;
    }

    try {
      // Extract user message.
      $userInput = $event->getInput();
      if (is_array($userInput) && isset($userInput[0]['text'])) {
        // Handle array input format.
        $message = $userInput[0]['text'];
      } else {
        // Handle string input format.
        $message = (string) $userInput;
      }

      // Create a conversation with the first message.
      $title = $this->conversationManager->generateTitle([
        ['role' => 'user', 'content' => $message]
      ]);
      
      $conversation = $this->conversationManager->createConversation($title);
      
      // Store provider and model info on the conversation.
      $config = $event->getConfiguration();
      $conversation->set('provider', $event->getProviderId());
      $conversation->set('model', $event->getModelId());
      $conversation->set('temperature', $config['temperature'] ?? 1.0);
      $conversation->set('max_tokens', $config['max_tokens'] ?? 1000);
      $conversation->set('metadata', json_encode([
        'request_thread_id' => $event->getRequestThreadId(),
        'tags' => $event->getTags(),
        'configuration' => $config,
      ]));
      $conversation->save();

      // Store the conversation and thread IDs for later use.
      $defaultThreadId = $conversation->get('default_thread_id')->value;
      $this->conversationMap[$event->getRequestThreadId()] = [
        'conversation_id' => $conversation->id(),
        'thread_id' => $defaultThreadId,
      ];

      // Add the user message to the thread.
      $this->conversationManager->addMessage(
        (int) $defaultThreadId,
        'user',
        $message,
        null,
        null,
        ['timestamp' => time()]
      );

      $this->logger->info('Created conversation @id for request @request_id', [
        '@id' => $conversation->id(),
        '@request_id' => $event->getRequestThreadId(),
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error capturing pre-generate response: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Handles post-generate response events to capture AI responses.
   *
   * @param \Drupal\ai\Event\PostGenerateResponseEvent $event
   *   The post-generate response event.
   */
  public function onPostGenerateResponse(PostGenerateResponseEvent $event) {
    // Only capture chat operations.
    if ($event->getOperationType() !== 'chat') {
      return;
    }

    try {
      $requestThreadId = $event->getRequestThreadId();
      
      // Retrieve the conversation and thread IDs.
      if (!isset($this->conversationMap[$requestThreadId])) {
        $this->logger->warning('No conversation found for request thread @id', [
          '@id' => $requestThreadId,
        ]);
        return;
      }

      $conversationInfo = $this->conversationMap[$requestThreadId];
      $threadId = $conversationInfo['thread_id'];

      // Extract the AI response.
      $output = $event->getOutput();
      $message = '';
      
      if (is_object($output) && method_exists($output, 'getNormalized')) {
        $normalized = $output->getNormalized();
        if (is_array($normalized) && isset($normalized[0]['text'])) {
          $message = $normalized[0]['text'];
        } elseif (is_string($normalized)) {
          $message = $normalized;
        }
      } elseif (is_string($output)) {
        $message = $output;
      }

      // Add the AI response to the thread.
      $this->conversationManager->addMessage(
        (int) $threadId,
        'assistant',
        $message,
        $event->getProviderId(),
        $event->getModelId(),
        ['timestamp' => time()]
      );

      // Clean up the mapping.
      unset($this->conversationMap[$requestThreadId]);

      $this->logger->info('Added AI response to conversation @id', [
        '@id' => $conversationInfo['conversation_id'],
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error capturing post-generate response: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
  }

}
