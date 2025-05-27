<?php

declare(strict_types=1);

namespace Drupal\ai_conversation;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining AI conversation thread entities.
 */
interface AiConversationThreadInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the thread title.
   *
   * @return string|null
   *   Title of the thread, or NULL if not set.
   */
  public function getTitle(): ?string;

  /**
   * Sets the thread title.
   *
   * @param string|null $title
   *   The thread title.
   *
   * @return \Drupal\ai_conversation\AiConversationThreadInterface
   *   The called thread entity.
   */
  public function setTitle(?string $title): AiConversationThreadInterface;

  /**
   * Gets the conversation ID.
   *
   * @return int
   *   The parent conversation ID.
   */
  public function getConversationId(): int;

  /**
   * Sets the conversation ID.
   *
   * @param int $conversation_id
   *   The parent conversation ID.
   *
   * @return \Drupal\ai_conversation\AiConversationThreadInterface
   *   The called thread entity.
   */
  public function setConversationId(int $conversation_id): AiConversationThreadInterface;

  /**
   * Gets the parent thread ID.
   *
   * @return int|null
   *   The parent thread ID, or NULL if this is not a branch.
   */
  public function getParentThreadId(): ?int;

  /**
   * Sets the parent thread ID.
   *
   * @param int|null $parent_thread_id
   *   The parent thread ID.
   *
   * @return \Drupal\ai_conversation\AiConversationThreadInterface
   *   The called thread entity.
   */
  public function setParentThreadId(?int $parent_thread_id): AiConversationThreadInterface;

  /**
   * Gets the branch point message ID.
   *
   * @return string|null
   *   The message ID where this thread branched from.
   */
  public function getBranchPointMessageId(): ?string;

  /**
   * Sets the branch point message ID.
   *
   * @param string|null $message_id
   *   The message ID where this thread branched from.
   *
   * @return \Drupal\ai_conversation\AiConversationThreadInterface
   *   The called thread entity.
   */
  public function setBranchPointMessageId(?string $message_id): AiConversationThreadInterface;

  /**
   * Gets the messages array.
   *
   * @return array
   *   Array of messages in this thread.
   */
  public function getMessages(): array;

  /**
   * Sets the messages array.
   *
   * @param array $messages
   *   Array of messages to set.
   *
   * @return \Drupal\ai_conversation\AiConversationThreadInterface
   *   The called thread entity.
   */
  public function setMessages(array $messages): AiConversationThreadInterface;

  /**
   * Adds a message to the thread.
   *
   * @param array $message
   *   The message to add.
   *
   * @return \Drupal\ai_conversation\AiConversationThreadInterface
   *   The called thread entity.
   */
  public function addMessage(array $message): AiConversationThreadInterface;

  /**
   * Gets the thread creation timestamp.
   *
   * @return int
   *   Creation timestamp of the thread.
   */
  public function getCreatedTime(): int;

  /**
   * Sets the thread creation timestamp.
   *
   * @param int $timestamp
   *   The thread creation timestamp.
   *
   * @return \Drupal\ai_conversation\AiConversationThreadInterface
   *   The called thread entity.
   */
  public function setCreatedTime(int $timestamp): AiConversationThreadInterface;

}
