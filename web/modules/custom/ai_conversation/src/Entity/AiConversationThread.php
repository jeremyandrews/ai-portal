<?php

declare(strict_types=1);

namespace Drupal\ai_conversation\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\ai_conversation\AiConversationThreadInterface;

/**
 * Defines the AI conversation thread entity.
 *
 * @ContentEntityType(
 *   id = "ai_conversation_thread",
 *   label = @Translation("AI Conversation Thread"),
 *   label_collection = @Translation("AI Conversation Threads"),
 *   label_singular = @Translation("AI conversation thread"),
 *   label_plural = @Translation("AI conversation threads"),
 *   label_count = @PluralTranslation(
 *     singular = "@count AI conversation thread",
 *     plural = "@count AI conversation threads",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ai_conversation\AiConversationThreadListBuilder",
 *     "views_data" = "Drupal\ai_conversation\Entity\AiConversationThreadViewsData",
 *     "form" = {
 *       "default" = "Drupal\ai_conversation\Form\AiConversationThreadForm",
 *       "add" = "Drupal\ai_conversation\Form\AiConversationThreadForm",
 *       "edit" = "Drupal\ai_conversation\Form\AiConversationThreadForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\ai_conversation\AiConversationThreadHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\ai_conversation\AiConversationThreadAccessControlHandler",
 *   },
 *   base_table = "ai_conversation_thread",
 *   translatable = FALSE,
 *   admin_permission = "administer ai conversations",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *   },
 *   links = {
 *     "canonical" = "/user/conversations/{ai_conversation}/thread/{ai_conversation_thread}",
 *     "add-form" = "/user/conversations/{ai_conversation}/thread/add",
 *     "edit-form" = "/user/conversations/{ai_conversation}/thread/{ai_conversation_thread}/edit",
 *     "delete-form" = "/user/conversations/{ai_conversation}/thread/{ai_conversation_thread}/delete",
 *   },
 *   field_ui_base_route = "ai_conversation.settings"
 * )
 */
final class AiConversationThread extends ContentEntityBase implements AiConversationThreadInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getTitle(): ?string {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle(?string $title): AiConversationThreadInterface {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConversationId(): int {
    return (int) $this->get('conversation_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setConversationId(int $conversation_id): AiConversationThreadInterface {
    $this->set('conversation_id', $conversation_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentThreadId(): ?int {
    $value = $this->get('parent_thread_id')->value;
    return $value ? (int) $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setParentThreadId(?int $parent_thread_id): AiConversationThreadInterface {
    $this->set('parent_thread_id', $parent_thread_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBranchPointMessageId(): ?string {
    return $this->get('branch_point_message_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setBranchPointMessageId(?string $message_id): AiConversationThreadInterface {
    $this->set('branch_point_message_id', $message_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessages(): array {
    $messages_json = $this->get('messages')->value;
    return $messages_json ? json_decode($messages_json, TRUE) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function setMessages(array $messages): AiConversationThreadInterface {
    $this->set('messages', json_encode($messages));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addMessage(array $message): AiConversationThreadInterface {
    $messages = $this->getMessages();
    $messages[] = $message;
    $this->setMessages($messages);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime(): int {
    return (int) $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime(int $timestamp): AiConversationThreadInterface {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['conversation_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Conversation ID'))
      ->setDescription(t('The parent conversation ID.'))
      ->setRequired(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['parent_thread_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Parent Thread ID'))
      ->setDescription(t('The parent thread ID if this is a branch.'))
      ->setSetting('unsigned', TRUE);

    $fields['branch_point_message_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Branch Point Message ID'))
      ->setDescription(t('The message ID where this thread branched from.'))
      ->setSetting('max_length', 128);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('Optional thread title.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['messages'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Messages'))
      ->setDescription(t('JSON array of messages in this thread.'))
      ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time when the thread was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time when the thread was last edited.'));

    return $fields;
  }

}
