<?php

declare(strict_types=1);

namespace Drupal\ai_conversation\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for AI Conversation entities.
 */
class AiConversationViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Ensure all base fields are properly exposed
    $data['ai_conversation']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('AI Conversation'),
      'help' => $this->t('AI Conversation entities containing conversation metadata.'),
      'weight' => -10,
    ];

    // Ensure the ID field is properly defined
    $data['ai_conversation']['id']['field']['id'] = 'field';
    $data['ai_conversation']['id']['sort']['id'] = 'standard';
    $data['ai_conversation']['id']['filter']['id'] = 'numeric';
    $data['ai_conversation']['id']['argument']['id'] = 'numeric';

    // User relationship
    $data['ai_conversation']['user_id']['relationship'] = [
      'base' => 'users_field_data',
      'base field' => 'uid',
      'id' => 'standard',
      'label' => $this->t('Conversation owner'),
      'title' => $this->t('User'),
      'help' => $this->t('The user who owns this conversation.'),
    ];

    // Thread count (computed field)
    $data['ai_conversation']['thread_count'] = [
      'title' => $this->t('Thread count'),
      'help' => $this->t('Number of threads in this conversation.'),
      'field' => [
        'id' => 'numeric',
        'click sortable' => TRUE,
      ],
      'sort' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
    ];

    // Relationship to threads
    $data['ai_conversation']['id']['relationship'] = [
      'title' => $this->t('Threads'),
      'help' => $this->t('All threads belonging to this conversation.'),
      'base' => 'ai_conversation_thread',
      'base field' => 'conversation_id',
      'relationship' => 'standard',
      'id' => 'standard',
      'label' => $this->t('Conversation threads'),
    ];

    // Default thread relationship
    $data['ai_conversation']['default_thread_id']['relationship'] = [
      'title' => $this->t('Default thread'),
      'help' => $this->t('The default/active thread for this conversation.'),
      'base' => 'ai_conversation_thread',
      'base field' => 'id',
      'relationship' => 'standard',
      'id' => 'standard',
      'label' => $this->t('Default thread'),
    ];

    // Title field enhancements
    $data['ai_conversation']['title']['field']['id'] = 'field';
    $data['ai_conversation']['title']['field']['click sortable'] = TRUE;
    $data['ai_conversation']['title']['sort']['id'] = 'standard';
    $data['ai_conversation']['title']['filter']['id'] = 'string';
    $data['ai_conversation']['title']['filter']['title'] = $this->t('Conversation title');
    $data['ai_conversation']['title']['filter']['help'] = $this->t('Filter by conversation title.');
    $data['ai_conversation']['title']['argument']['id'] = 'string';

    // Created/Changed timestamp fields
    $data['ai_conversation']['created']['field']['id'] = 'date';
    $data['ai_conversation']['created']['sort']['id'] = 'date';
    $data['ai_conversation']['created']['filter']['id'] = 'date';

    $data['ai_conversation']['changed']['field']['id'] = 'date';
    $data['ai_conversation']['changed']['sort']['id'] = 'date';
    $data['ai_conversation']['changed']['filter']['id'] = 'date';

    // Operations links
    $data['ai_conversation']['operations'] = [
      'title' => $this->t('Operations'),
      'help' => $this->t('Provides operation links for the conversation.'),
      'field' => [
        'id' => 'entity_operations',
      ],
    ];

    // Bulk operations
    $data['ai_conversation']['ai_conversation_bulk_form'] = [
      'title' => $this->t('Bulk operations'),
      'help' => $this->t('Provides a checkbox for bulk operations.'),
      'field' => [
        'id' => 'bulk_form',
      ],
    ];

    return $data;
  }

}
