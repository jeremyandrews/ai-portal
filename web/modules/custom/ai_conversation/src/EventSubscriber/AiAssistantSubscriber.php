<?php

declare(strict_types=1);

namespace Drupal\ai_conversation\EventSubscriber;

use Drupal\ai\Event\PostGenerateResponseEvent;
use Drupal\ai\Event\PreGenerateResponseEvent;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
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
      
      // First check for portal mode (conversation_id in request).
      $portalConversationId = $request ? $request->query->get('conversation') : null;
      
      if ($portalConversationId) {
        // Portal mode - use specific conversation.
        $conversation = $this->conversationManager->loadConversation((int) $portalConversationId);
        
        if ($conversation && $conversation->getOwnerId() == $this->currentUser->id()) {
          $conversationId = (int) $conversation->id();
          $threadId = (int) $conversation->getDefaultThreadId();
          $this->logger->info('Using portal conversation @id', [
            '@id' => $conversationId,
          ]);
        } else {
          $this->logger->warning('Invalid portal conversation @id requested', [
            '@id' => $portalConversationId,
          ]);
        }
      } elseif ($session && $session->has('ai_conversation_active')) {
        // Session mode - use session-based conversation.
        $activeConversation = $session->get('ai_conversation_active');
        if (isset($activeConversation['conversation_id']) && isset($activeConversation['thread_id'])) {
          // Verify the conversation still exists.
          $existingConversation = $this->conversationManager->loadConversation((int) $activeConversation['conversation_id']);
          if ($existingConversation) {
            $conversationId = (int) $activeConversation['conversation_id'];
            $threadId = (int) $activeConversation['thread_id'];
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

      // Build conversation history for context.
      $chatMessages = [];
      
      // Load existing messages from the thread.
      if ($threadId) {
        $thread = \Drupal::entityTypeManager()->getStorage('ai_conversation_thread')->load($threadId);
        if ($thread) {
          $existingMessages = $thread->getMessages();
          foreach ($existingMessages as $existingMessage) {
            if (isset($existingMessage['role']) && isset($existingMessage['content'])) {
              $chatMessages[] = new ChatMessage(
                $existingMessage['role'],
                $existingMessage['content']
              );
            }
          }
        }
      }
      
      // Add the current user message.
      $chatMessages[] = new ChatMessage('user', $message);
      
      // Update the event input with full conversation history.
      $chatInput = new ChatInput($chatMessages);
      $event->setInput($chatInput);
      
      $this->logger->info('Updated input with @count messages for conversation @id', [
        '@count' => count($chatMessages),
        '@id' => $conversationId,
      ]);
      
      // Add the user message to the thread for persistence.
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

      // Extract the AI response with enhanced logging.
      $output = $event->getOutput();
      $message = '';
      
      // Log the output type for debugging.
      $this->logger->debug('AI Response output type: @type, class: @class', [
        '@type' => gettype($output),
        '@class' => is_object($output) ? get_class($output) : 'not_an_object',
      ]);
      
      // Try multiple extraction methods.
      $extractionAttempts = [];
      
      // Method 1: Direct string
      if (is_string($output)) {
        $message = $output;
        $extractionAttempts[] = 'direct_string';
      }
      // Method 2: ChatOutput object (expected from AI module)
      elseif (is_object($output) && method_exists($output, 'getNormalized')) {
        try {
          $normalized = $output->getNormalized();
          $extractionAttempts[] = 'getNormalized: ' . (is_object($normalized) ? get_class($normalized) : gettype($normalized));
          
          // Check if normalized is a ChatMessage object
          if (is_object($normalized) && method_exists($normalized, 'getText')) {
            $message = $normalized->getText();
            $extractionAttempts[] = 'ChatMessage->getText()';
          }
          // Legacy array format
          elseif (is_array($normalized) && isset($normalized[0]['text'])) {
            $message = $normalized[0]['text'];
            $extractionAttempts[] = 'normalized_array_text';
          }
          // Direct string
          elseif (is_string($normalized)) {
            $message = $normalized;
            $extractionAttempts[] = 'normalized_string';
          }
        } catch (\Exception $e) {
          $this->logger->error('getNormalized failed: @error', ['@error' => $e->getMessage()]);
        }
      }
      // Method 3: Direct getText method
      elseif (is_object($output) && method_exists($output, 'getText')) {
        try {
          $message = $output->getText();
          $extractionAttempts[] = 'direct_getText';
        } catch (\Exception $e) {
          $this->logger->error('getText failed: @error', ['@error' => $e->getMessage()]);
        }
      }
      // Method 4: __toString method
      elseif (is_object($output) && method_exists($output, '__toString')) {
        try {
          $message = (string) $output;
          $extractionAttempts[] = '__toString';
        } catch (\Exception $e) {
          $this->logger->error('__toString failed: @error', ['@error' => $e->getMessage()]);
        }
      }
      
      // Log extraction attempts and result.
      $this->logger->info('AI Response extraction attempts: @attempts, message length: @length, preview: @preview', [
        '@attempts' => implode(', ', $extractionAttempts),
        '@length' => strlen($message),
        '@preview' => substr($message, 0, 100),
      ]);
      
      // If we still have no message, log available methods for debugging.
      if (empty($message) && is_object($output)) {
        $methods = get_class_methods($output);
        $this->logger->warning('Failed to extract AI response. Available methods: @methods', [
          '@methods' => implode(', ', array_slice($methods, 0, 10)),
        ]);
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

      $this->logger->info('Added AI response to conversation @id (length: @length)', [
        '@id' => $conversationInfo['conversation_id'],
        '@length' => strlen($message),
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error capturing post-generate response: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
  }

}
