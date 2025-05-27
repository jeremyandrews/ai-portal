<?php

declare(strict_types=1);

namespace Drupal\ai_conversation\EventSubscriber;

use Drupal\ai\Event\PostGenerateResponseEvent;
use Drupal\ai\Event\PreGenerateResponseEvent;
use Drupal\ai_conversation\Service\AiConversationManager;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

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
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

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
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(
    AiConversationManager $conversation_manager,
    AccountProxyInterface $current_user,
    LoggerInterface $logger,
    RequestStack $request_stack
  ) {
    $this->conversationManager = $conversation_manager;
    $this->currentUser = $current_user;
    $this->logger = $logger;
    $this->requestStack = $request_stack;
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

      // Get the current session.
      $request = $this->requestStack->getCurrentRequest();
      $session = $request ? $request->getSession() : null;
      
      // Check if there's an active conversation in the session.
      $conversationId = null;
      $threadId = null;
      
      if ($session && $session->has('ai_conversation_active')) {
        $activeConversation = $session->get('ai_conversation_active');
        if (isset($activeConversation['conversation_id']) && isset($activeConversation['thread_id'])) {
          // Verify the conversation still exists.
          $existingConversation = $this->conversationManager->loadConversation($activeConversation['conversation_id']);
          if ($existingConversation) {
            $conversationId = $activeConversation['conversation_id'];
            $threadId = $activeConversation['thread_id'];
            $this->logger->info('Using existing conversation @id from session', [
              '@id' => $conversationId,
            ]);
          }
        }
      }
      
      // If no active conversation, create a new one.
      if (!$conversationId) {
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

        $conversationId = $conversation->id();
        $threadId = $conversation->get('default_thread_id')->value;
        
        // Store in session for future messages.
        if ($session) {
          $session->set('ai_conversation_active', [
            'conversation_id' => $conversationId,
            'thread_id' => $threadId,
            'started' => time(),
          ]);
        }
        
        $this->logger->info('Created new conversation @id', [
          '@id' => $conversationId,
        ]);
      }

      // Store the conversation and thread IDs for the response handler.
      $this->conversationMap[$event->getRequestThreadId()] = [
        'conversation_id' => $conversationId,
        'thread_id' => $threadId,
      ];

      // Add the user message to the thread.
      $this->conversationManager->addMessage(
        (int) $threadId,
        'user',
        $message,
        null,
        null,
        ['timestamp' => time()]
      );

      $this->logger->info('Added user message to conversation @id', [
        '@id' => $conversationId,
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
