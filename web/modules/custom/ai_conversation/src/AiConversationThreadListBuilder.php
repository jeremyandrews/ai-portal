<?php

declare(strict_types=1);

namespace Drupal\ai_conversation;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of AI conversation thread entities.
 *
 * @ingroup ai_conversation
 */
class AiConversationThreadListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = $this->t('Title');
    $header['parent_thread'] = $this->t('Parent Thread');
    $header['messages'] = $this->t('Messages');
    $header['created'] = $this->t('Created');
    $header['changed'] = $this->t('Updated');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\ai_conversation\AiConversationThreadInterface $entity */
    
    $title = $entity->getTitle();
    if (empty($title)) {
      $messages = $entity->getMessages();
      $first_message = !empty($messages) ? $messages[0] : NULL;
      $title = $first_message ? substr($first_message['content'], 0, 50) . '...' : $this->t('Untitled Thread');
    }
    
    $row['title'] = Link::createFromRoute(
      $title,
      'entity.ai_conversation_thread.canonical',
      [
        'ai_conversation' => $entity->getConversationId(),
        'ai_conversation_thread' => $entity->id(),
      ]
    );
    
    // Show parent thread if this is a branch.
    $parent_thread_id = $entity->getParentThreadId();
    if ($parent_thread_id) {
      $parent_thread = \Drupal::entityTypeManager()
        ->getStorage('ai_conversation_thread')
        ->load($parent_thread_id);
      
      if ($parent_thread) {
        $parent_title = $parent_thread->getTitle() ?: $this->t('Thread @id', ['@id' => $parent_thread_id]);
        $row['parent_thread'] = Link::createFromRoute(
          $parent_title,
          'entity.ai_conversation_thread.canonical',
          [
            'ai_conversation' => $entity->getConversationId(),
            'ai_conversation_thread' => $parent_thread_id,
          ]
        );
      } else {
        $row['parent_thread'] = $this->t('Deleted thread @id', ['@id' => $parent_thread_id]);
      }
    } else {
      $row['parent_thread'] = $this->t('Main thread');
    }
    
    // Count messages in this thread.
    $messages = $entity->getMessages();
    $row['messages'] = count($messages);
    
    $row['created'] = \Drupal::service('date.formatter')->format($entity->getCreatedTime());
    $row['changed'] = \Drupal::service('date.formatter')->format($entity->getChangedTime());
    
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    
    // Add resume thread link.
    if ($entity->access('update')) {
      $operations['resume'] = [
        'title' => $this->t('Resume'),
        'weight' => 5,
        'url' => Url::fromRoute('ai_conversation.resume_thread', [
          'ai_conversation' => $entity->getConversationId(),
          'ai_conversation_thread' => $entity->id(),
        ]),
      ];
    }
    
    // Add branch thread link.
    if ($entity->access('view')) {
      $operations['branch'] = [
        'title' => $this->t('Branch'),
        'weight' => 15,
        'url' => Url::fromRoute('ai_conversation.branch_thread', [
          'ai_conversation' => $entity->getConversationId(),
          'ai_conversation_thread' => $entity->id(),
        ]),
      ];
    }
    
    return $operations;
  }

}
