<?php

declare(strict_types=1);

namespace Drupal\ai_conversation\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\ai_conversation\AiConversationInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the AI conversation entity.
 *
 * @ContentEntityType(
 *   id = "ai_conversation",
 *   label = @Translation("AI Conversation"),
 *   label_collection = @Translation("AI Conversations"),
 *   label_singular = @Translation("AI conversation"),
 *   label_plural = @Translation("AI conversations"),
 *   label_count = @PluralTranslation(
 *     singular = "@count AI conversation",
 *     plural = "@count AI conversations",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ai_conversation\AiConversationListBuilder",
 *     "views_data" = "Drupal\ai_conversation\Entity\AiConversationViewsData",
 *     "form" = {
 *       "default" = "Drupal\ai_conversation\Form\AiConversationForm",
 *       "add" = "Drupal\ai_conversation\Form\AiConversationForm",
 *       "edit" = "Drupal\ai_conversation\Form\AiConversationForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\ai_conversation\AiConversationHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\ai_conversation\AiConversationAccessControlHandler",
 *   },
 *   base_table = "ai_conversation",
 *   translatable = FALSE,
 *   admin_permission = "administer ai conversations",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "user_id",
 *   },
 *   links = {
 *     "canonical" = "/user/conversations/{ai_conversation}",
 *     "add-form" = "/user/conversations/add",
 *     "edit-form" = "/user/conversations/{ai_conversation}/edit",
 *     "delete-form" = "/user/conversations/{ai_conversation}/delete",
 *     "collection" = "/user/conversations",
 *   },
 *   field_ui_base_route = "ai_conversation.settings"
 * )
 */
final class AiConversation extends ContentEntityBase implements AiConversationInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // Default to the current user.
      $this->setOwnerId(\Drupal::currentUser()->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(): string {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle(string $title): AiConversationInterface {
    $this->set('title', $title);
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
  public function setCreatedTime(int $timestamp): AiConversationInterface {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultThreadId(): ?int {
    $value = $this->get('default_thread_id')->value;
    return $value ? (int) $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultThreadId(?int $thread_id): AiConversationInterface {
    $this->set('default_thread_id', $thread_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getModel(): string {
    return $this->get('model')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function setModel(string $model): AiConversationInterface {
    $this->set('model', $model);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider(): string {
    return $this->get('provider')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function setProvider(string $provider): AiConversationInterface {
    $this->set('provider', $provider);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTemperature(): float {
    return (float) ($this->get('temperature')->value ?? 0.7);
  }

  /**
   * {@inheritdoc}
   */
  public function setTemperature(float $temperature): AiConversationInterface {
    $this->set('temperature', $temperature);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxTokens(): int {
    return (int) ($this->get('max_tokens')->value ?? 1000);
  }

  /**
   * {@inheritdoc}
   */
  public function setMaxTokens(int $max_tokens): AiConversationInterface {
    $this->set('max_tokens', $max_tokens);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(): array {
    $value = $this->get('metadata')->value;
    return $value ? json_decode($value, TRUE) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function setMetadata(array $metadata): AiConversationInterface {
    $this->set('metadata', json_encode($metadata));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The conversation title.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['model'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Model'))
      ->setDescription(t('The AI model used for this conversation.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 100)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['provider'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Provider'))
      ->setDescription(t('The AI provider (e.g., openai, anthropic).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 100)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['temperature'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Temperature'))
      ->setDescription(t('The temperature setting for AI responses.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDefaultValue(0.7)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_decimal',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['max_tokens'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Max Tokens'))
      ->setDescription(t('Maximum number of tokens for AI responses.'))
      ->setSetting('min', 1)
      ->setDefaultValue(1000)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => 3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['metadata'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Metadata'))
      ->setDescription(t('Additional metadata for the conversation.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['default_thread_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Default Thread ID'))
      ->setDescription(t('The ID of the default/active thread.'))
      ->setSetting('unsigned', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time when the conversation was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time when the conversation was last edited.'));

    return $fields;
  }

}
