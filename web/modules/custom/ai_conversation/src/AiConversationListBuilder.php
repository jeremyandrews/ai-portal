<?php

declare(strict_types=1);

namespace Drupal\ai_conversation;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of AI conversation entities.
 *
 * @ingroup ai_conversation
 */
class AiConversationListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = $this->t('Title');
    $header['owner'] = $this->t('Owner');
    $header['threads'] = $this->t('Threads');
    $header['created'] = $this->t('Created');
    $header['changed'] = $this->t('Updated');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\ai_conversation\AiConversationInterface $entity */
    
    $row['title'] = Link::createFromRoute(
      $entity->getTitle(),
      'entity.ai_conversation.canonical',
      ['ai_conversation' => $entity->id()]
    );
    
    $owner = $entity->getOwner();
    $row['owner'] = $owner ? $owner->getDisplayName() : $this->t('Anonymous');
    
    // Count threads for this conversation.
    $thread_count = \Drupal::entityQuery('ai_conversation_thread')
      ->accessCheck(TRUE)
      ->condition('conversation_id', $entity->id())
      ->count()
      ->execute();
    
    $row['threads'] = $thread_count;
    $row['created'] = \Drupal::service('date.formatter')->format($entity->getCreatedTime());
    $row['changed'] = \Drupal::service('date.formatter')->format($entity->getChangedTime());
    
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    
    // Add a link to view threads.
    if ($entity->access('view') && $entity->hasLinkTemplate('canonical')) {
      $operations['view_threads'] = [
        'title' => $this->t('View threads'),
        'weight' => 10,
        'url' => Url::fromRoute('entity.ai_conversation.canonical', [
          'ai_conversation' => $entity->id(),
        ]),
      ];
    }
    
    // Add resume conversation link.
    if ($entity->access('update')) {
      $operations['resume'] = [
        'title' => $this->t('Resume'),
        'weight' => 5,
        'url' => Url::fromRoute('ai_conversation.resume', [
          'ai_conversation' => $entity->id(),
        ]),
      ];
    }
    
    return $operations;
  }

}
