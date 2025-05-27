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

    // Add user relationship
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
    $data['ai_conversation']['ai_conversation_thread'] = [
      'title' => $this->t('Threads'),
      'help' => $this->t('All threads belonging to this conversation.'),
      'relationship' => [
        'base' => 'ai_conversation_thread',
        'base field' => 'conversation_id',
        'field' => 'id',
        'id' => 'standard',
        'label' => $this->t('Conversation threads'),
      ],
    ];

    // Default thread relationship
    if (isset($data['ai_conversation']['default_thread_id'])) {
      $data['ai_conversation']['default_thread_id']['relationship'] = [
        'title' => $this->t('Default thread'),
        'help' => $this->t('The default/active thread for this conversation.'),
        'base' => 'ai_conversation_thread',
        'base field' => 'id',
        'id' => 'standard',
        'label' => $this->t('Default thread'),
      ];
    }

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
