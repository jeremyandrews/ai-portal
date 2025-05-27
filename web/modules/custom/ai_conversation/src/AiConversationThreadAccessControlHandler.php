<?php

declare(strict_types=1);

namespace Drupal\ai_conversation;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the AI conversation thread entity.
 *
 * @see \Drupal\ai_conversation\Entity\AiConversationThread
 */
class AiConversationThreadAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ai_conversation\AiConversationThreadInterface $entity */
    
    // First, check if user can access the parent conversation.
    $conversation = \Drupal::entityTypeManager()
      ->getStorage('ai_conversation')
      ->load($entity->getConversationId());
    
    if (!$conversation) {
      return AccessResult::forbidden('Parent conversation not found.');
    }
    
    $conversation_access = $conversation->access($operation, $account, TRUE);
    if (!$conversation_access->isAllowed()) {
      return $conversation_access;
    }
    
    // If conversation access is granted, check thread-specific permissions.
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view ai conversation threads');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit own ai conversations')
          ->orIf(AccessResult::allowedIfHasPermission($account, 'edit any ai conversations'));

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete ai conversation threads');

      default:
        // No opinion.
        return AccessResult::neutral();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // Check if user has permission to create threads.
    $create_access = AccessResult::allowedIfHasPermission($account, 'create ai conversation threads');
    
    // Also check if they can create conversations (threads are part of conversations).
    $conversation_create_access = AccessResult::allowedIfHasPermission($account, 'create ai conversations');
    
    return $create_access->orIf($conversation_create_access);
  }

}
