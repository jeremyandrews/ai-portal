<?php

declare(strict_types=1);

namespace Drupal\ai_conversation;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining AI conversation entities.
 */
interface AiConversationInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the conversation title.
   *
   * @return string
   *   Title of the conversation.
   */
  public function getTitle(): string;

  /**
   * Sets the conversation title.
   *
   * @param string $title
   *   The conversation title.
   *
   * @return \Drupal\ai_conversation\AiConversationInterface
   *   The called conversation entity.
   */
  public function setTitle(string $title): AiConversationInterface;

  /**
   * Gets the conversation creation timestamp.
   *
   * @return int
   *   Creation timestamp of the conversation.
   */
  public function getCreatedTime(): int;

  /**
   * Sets the conversation creation timestamp.
   *
   * @param int $timestamp
   *   The conversation creation timestamp.
   *
   * @return \Drupal\ai_conversation\AiConversationInterface
   *   The called conversation entity.
   */
  public function setCreatedTime(int $timestamp): AiConversationInterface;

  /**
   * Gets the default thread ID.
   *
   * @return int|null
   *   The default thread ID, or NULL if not set.
   */
  public function getDefaultThreadId(): ?int;

  /**
   * Sets the default thread ID for the conversation.
   *
   * @param int|null $thread_id
   *   The thread ID to set as default.
   *
   * @return \Drupal\ai_conversation\AiConversationInterface
   *   The called conversation entity.
   */
  public function setDefaultThreadId(?int $thread_id): AiConversationInterface;

  /**
   * Gets the AI model for this conversation.
   *
   * @return string
   *   The AI model name.
   */
  public function getModel(): string;

  /**
   * Sets the AI model for this conversation.
   *
   * @param string $model
   *   The AI model name.
   *
   * @return \Drupal\ai_conversation\AiConversationInterface
   *   The called conversation entity.
   */
  public function setModel(string $model): AiConversationInterface;

  /**
   * Gets the AI provider for this conversation.
   *
   * @return string
   *   The AI provider name.
   */
  public function getProvider(): string;

  /**
   * Sets the AI provider for this conversation.
   *
   * @param string $provider
   *   The AI provider name.
   *
   * @return \Drupal\ai_conversation\AiConversationInterface
   *   The called conversation entity.
   */
  public function setProvider(string $provider): AiConversationInterface;

  /**
   * Gets the temperature setting for this conversation.
   *
   * @return float
   *   The temperature value.
   */
  public function getTemperature(): float;

  /**
   * Sets the temperature setting for this conversation.
   *
   * @param float $temperature
   *   The temperature value.
   *
   * @return \Drupal\ai_conversation\AiConversationInterface
   *   The called conversation entity.
   */
  public function setTemperature(float $temperature): AiConversationInterface;

  /**
   * Gets the maximum tokens for this conversation.
   *
   * @return int
   *   The maximum tokens.
   */
  public function getMaxTokens(): int;

  /**
   * Sets the maximum tokens for this conversation.
   *
   * @param int $max_tokens
   *   The maximum tokens.
   *
   * @return \Drupal\ai_conversation\AiConversationInterface
   *   The called conversation entity.
   */
  public function setMaxTokens(int $max_tokens): AiConversationInterface;

  /**
   * Gets the metadata for this conversation.
   *
   * @return array
   *   The metadata array.
   */
  public function getMetadata(): array;

  /**
   * Sets the metadata for this conversation.
   *
   * @param array $metadata
   *   The metadata array.
   *
   * @return \Drupal\ai_conversation\AiConversationInterface
   *   The called conversation entity.
   */
  public function setMetadata(array $metadata): AiConversationInterface;

}
