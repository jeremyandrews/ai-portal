<?php

declare(strict_types=1);

namespace Drupal\ai_conversation\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\ai_conversation\AiConversationInterface;
use Drupal\ai_conversation\AiConversationThreadInterface;

/**
 * Service for managing AI conversations and threads.
 */
class AiConversationManager {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new AiConversationManager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   The UUID service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, UuidInterface $uuid, TimeInterface $time) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->uuid = $uuid;
    $this->time = $time;
  }

  /**
   * Creates a new conversation with an initial thread.
   *
   * @param string $title
   *   The conversation title.
   * @param array $initial_message
   *   The initial message to add to the thread.
   * @param int|null $user_id
   *   The user ID. If not provided, uses current user.
   *
   * @return \Drupal\ai_conversation\AiConversationInterface
   *   The created conversation.
   */
  public function createConversation(string $title, array $initial_message = [], ?int $user_id = NULL): AiConversationInterface {
    $user_id = $user_id ?: $this->currentUser->id();
    
    /** @var \Drupal\ai_conversation\AiConversationInterface $conversation */
    $conversation = $this->entityTypeManager->getStorage('ai_conversation')->create([
      'title' => $title,
      'user_id' => $user_id,
    ]);
    $conversation->save();

    // Create the initial thread.
    $thread = $this->createThread((int) $conversation->id(), $initial_message);
    
    // Set this as the default thread.
    $conversation->setDefaultThreadId((int) $thread->id());
    $conversation->save();

    return $conversation;
  }

  /**
   * Creates a new thread for a conversation.
   *
   * @param int $conversation_id
   *   The conversation ID.
   * @param array $initial_messages
   *   Initial messages for the thread.
   * @param int|null $parent_thread_id
   *   Parent thread ID if this is a branch.
   * @param string|null $branch_point_message_id
   *   The message ID where this thread branches from.
   * @param string|null $title
   *   Optional thread title.
   *
   * @return \Drupal\ai_conversation\AiConversationThreadInterface
   *   The created thread.
   */
  public function createThread(int $conversation_id, array $initial_messages = [], ?int $parent_thread_id = NULL, ?string $branch_point_message_id = NULL, ?string $title = NULL): AiConversationThreadInterface {
    /** @var \Drupal\ai_conversation\AiConversationThreadInterface $thread */
    $thread = $this->entityTypeManager->getStorage('ai_conversation_thread')->create([
      'conversation_id' => $conversation_id,
      'parent_thread_id' => $parent_thread_id,
      'branch_point_message_id' => $branch_point_message_id,
      'title' => $title,
      'messages' => json_encode($initial_messages),
    ]);
    $thread->save();

    return $thread;
  }

  /**
   * Adds a message to a thread.
   *
   * @param int $thread_id
   *   The thread ID.
   * @param string $role
   *   The message role (user, assistant).
   * @param string $content
   *   The message content.
   * @param string|null $ai_provider
   *   The AI provider used.
   * @param string|null $ai_model
   *   The AI model used.
   * @param array $metadata
   *   Additional metadata.
   *
   * @return string
   *   The generated message ID.
   */
  public function addMessage(int $thread_id, string $role, string $content, ?string $ai_provider = NULL, ?string $ai_model = NULL, array $metadata = []): string {
    $thread = $this->entityTypeManager->getStorage('ai_conversation_thread')->load($thread_id);
    
    if (!$thread) {
      throw new \InvalidArgumentException("Thread $thread_id not found.");
    }

    $message_id = $this->uuid->generate();
    $message = [
      'id' => $message_id,
      'role' => $role,
      'content' => $content,
      'timestamp' => $this->time->getRequestTime(),
      'ai_provider' => $ai_provider,
      'ai_model' => $ai_model,
      'metadata' => $metadata,
    ];

    $thread->addMessage($message);
    $thread->save();

    // Update the parent conversation's updated time.
    $conversation = $this->entityTypeManager->getStorage('ai_conversation')->load($thread->getConversationId());
    if ($conversation) {
      $conversation->setChangedTime($this->time->getRequestTime());
      $conversation->save();
    }

    return $message_id;
  }

  /**
   * Branches a thread from a specific message.
   *
   * @param int $source_thread_id
   *   The source thread ID.
   * @param string $branch_point_message_id
   *   The message ID to branch from.
   * @param string|null $title
   *   Optional title for the new branch.
   *
   * @return \Drupal\ai_conversation\AiConversationThreadInterface
   *   The new branched thread.
   */
  public function branchThread(int $source_thread_id, string $branch_point_message_id, ?string $title = NULL): AiConversationThreadInterface {
    $source_thread = $this->entityTypeManager->getStorage('ai_conversation_thread')->load($source_thread_id);
    
    if (!$source_thread) {
      throw new \InvalidArgumentException("Source thread $source_thread_id not found.");
    }

    // Get messages up to the branch point.
    $source_messages = $source_thread->getMessages();
    $branch_messages = [];
    
    foreach ($source_messages as $message) {
      $branch_messages[] = $message;
      if ($message['id'] === $branch_point_message_id) {
        break;
      }
    }

    return $this->createThread(
      $source_thread->getConversationId(),
      $branch_messages,
      $source_thread_id,
      $branch_point_message_id,
      $title
    );
  }

  /**
   * Gets conversations for a user.
   *
   * @param int|null $user_id
   *   The user ID. If not provided, uses current user.
   * @param int $limit
   *   Number of conversations to return.
   * @param int $offset
   *   Offset for pagination.
   *
   * @return \Drupal\ai_conversation\AiConversationInterface[]
   *   Array of conversations.
   */
  public function getUserConversations(?int $user_id = NULL, int $limit = 50, int $offset = 0): array {
    $user_id = $user_id ?: $this->currentUser->id();
    
    $query = $this->entityTypeManager->getStorage('ai_conversation')->getQuery()
      ->accessCheck(TRUE)
      ->condition('user_id', $user_id)
      ->sort('changed', 'DESC')
      ->range($offset, $limit);
    
    $ids = $query->execute();
    
    return $this->entityTypeManager->getStorage('ai_conversation')->loadMultiple($ids);
  }

  /**
   * Gets threads for a conversation.
   *
   * @param int $conversation_id
   *   The conversation ID.
   *
   * @return \Drupal\ai_conversation\AiConversationThreadInterface[]
   *   Array of threads.
   */
  public function getConversationThreads(int $conversation_id): array {
    $query = $this->entityTypeManager->getStorage('ai_conversation_thread')->getQuery()
      ->accessCheck(TRUE)
      ->condition('conversation_id', $conversation_id)
      ->sort('created', 'ASC');
    
    $ids = $query->execute();
    
    return $this->entityTypeManager->getStorage('ai_conversation_thread')->loadMultiple($ids);
  }

  /**
   * Generates a conversation title from the first message.
   *
   * @param array $messages
   *   Array of messages.
   * @param int $max_length
   *   Maximum title length.
   *
   * @return string
   *   Generated title.
   */
  public function generateTitle(array $messages, int $max_length = 50): string {
    if (empty($messages)) {
      return t('New Conversation');
    }

    $first_user_message = '';
    foreach ($messages as $message) {
      if ($message['role'] === 'user') {
        $first_user_message = $message['content'];
        break;
      }
    }

    if (empty($first_user_message)) {
      return t('New Conversation');
    }

    // Clean and truncate the message.
    $title = strip_tags($first_user_message);
    $title = preg_replace('/\s+/', ' ', $title);
    $title = trim($title);
    
    if (strlen($title) > $max_length) {
      $title = substr($title, 0, $max_length - 3) . '...';
    }

    return $title ?: t('New Conversation');
  }

}
