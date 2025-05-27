<?php

declare(strict_types=1);

namespace Drupal\ai_conversation\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for AI Conversation Thread entities.
 */
class AiConversationThreadViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Ensure all base fields are properly exposed
    $data['ai_conversation_thread']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('AI Conversation Thread'),
      'help' => $this->t('AI Conversation Thread entities containing message threads.'),
      'weight' => -10,
    ];

    // Ensure the ID field is properly defined
    $data['ai_conversation_thread']['id']['field']['id'] = 'field';
    $data['ai_conversation_thread']['id']['sort']['id'] = 'standard';
    $data['ai_conversation_thread']['id']['filter']['id'] = 'numeric';
    $data['ai_conversation_thread']['id']['argument']['id'] = 'numeric';

    // Relationship to parent conversation
    $data['ai_conversation_thread']['conversation_id']['relationship'] = [
      'base' => 'ai_conversation',
      'base field' => 'id',
      'id' => 'standard',
      'label' => $this->t('Parent conversation'),
      'title' => $this->t('Conversation'),
      'help' => $this->t('The conversation this thread belongs to.'),
    ];

    // Relationship to parent thread (for branching)
    $data['ai_conversation_thread']['parent_thread_id']['relationship'] = [
      'base' => 'ai_conversation_thread',
      'base field' => 'id',
      'id' => 'standard',
      'label' => $this->t('Parent thread'),
      'title' => $this->t('Parent thread'),
      'help' => $this->t('The thread this was branched from.'),
    ];

    // Relationship to child threads
    $data['ai_conversation_thread']['id']['relationship'] = [
      'title' => $this->t('Child threads'),
      'help' => $this->t('Threads branched from this thread.'),
      'base' => 'ai_conversation_thread',
      'base field' => 'parent_thread_id',
      'relationship' => 'standard',
      'id' => 'standard',
      'label' => $this->t('Child threads'),
    ];

    // Title field enhancements
    $data['ai_conversation_thread']['title']['field']['id'] = 'field';
    $data['ai_conversation_thread']['title']['field']['click sortable'] = TRUE;
    $data['ai_conversation_thread']['title']['sort']['id'] = 'standard';
    $data['ai_conversation_thread']['title']['filter']['id'] = 'string';
    $data['ai_conversation_thread']['title']['filter']['title'] = $this->t('Thread title');
    $data['ai_conversation_thread']['title']['filter']['help'] = $this->t('Filter by thread title.');
    $data['ai_conversation_thread']['title']['argument']['id'] = 'string';

    // Branch point message ID
    $data['ai_conversation_thread']['branch_point_message_id']['field']['id'] = 'field';
    $data['ai_conversation_thread']['branch_point_message_id']['filter']['id'] = 'string';

    // Messages field (JSON)
    $data['ai_conversation_thread']['messages'] = [
      'title' => $this->t('Messages'),
      'help' => $this->t('JSON array of messages in this thread.'),
      'field' => [
        'id' => 'serialized',
        'click sortable' => FALSE,
      ],
    ];

    // Message count (computed field)
    $data['ai_conversation_thread']['message_count'] = [
      'title' => $this->t('Message count'),
      'help' => $this->t('Number of messages in this thread.'),
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
    ];

    // Created/Changed timestamp fields
    $data['ai_conversation_thread']['created']['field']['id'] = 'date';
    $data['ai_conversation_thread']['created']['sort']['id'] = 'date';
    $data['ai_conversation_thread']['created']['filter']['id'] = 'date';

    $data['ai_conversation_thread']['changed']['field']['id'] = 'date';
    $data['ai_conversation_thread']['changed']['sort']['id'] = 'date';
    $data['ai_conversation_thread']['changed']['filter']['id'] = 'date';

    // Operations links
    $data['ai_conversation_thread']['operations'] = [
      'title' => $this->t('Operations'),
      'help' => $this->t('Provides operation links for the thread.'),
      'field' => [
        'id' => 'entity_operations',
      ],
    ];

    // Bulk operations
    $data['ai_conversation_thread']['ai_conversation_thread_bulk_form'] = [
      'title' => $this->t('Bulk operations'),
      'help' => $this->t('Provides a checkbox for bulk operations.'),
      'field' => [
        'id' => 'bulk_form',
      ],
    ];

    // Thread hierarchy indicator
    $data['ai_conversation_thread']['thread_hierarchy'] = [
      'title' => $this->t('Thread hierarchy'),
      'help' => $this->t('Visual representation of thread relationships.'),
      'field' => [
        'id' => 'standard',
        'click sortable' => FALSE,
      ],
    ];

    return $data;
  }

}
