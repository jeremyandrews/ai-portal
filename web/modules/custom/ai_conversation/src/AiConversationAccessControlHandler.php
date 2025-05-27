<?php

declare(strict_types=1);

namespace Drupal\ai_conversation;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the AI conversation entity.
 *
 * @see \Drupal\ai_conversation\Entity\AiConversation
 */
class AiConversationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ai_conversation\AiConversationInterface $entity */
    
    switch ($operation) {
      case 'view':
        // Users can view their own conversations or if they have permission to view any.
        if ($entity->getOwnerId() === $account->id()) {
          return AccessResult::allowedIfHasPermission($account, 'view own ai conversations');
        }
        return AccessResult::allowedIfHasPermission($account, 'view any ai conversations');

      case 'update':
        // Users can edit their own conversations or if they have permission to edit any.
        if ($entity->getOwnerId() === $account->id()) {
          return AccessResult::allowedIfHasPermission($account, 'edit own ai conversations');
        }
        return AccessResult::allowedIfHasPermission($account, 'edit any ai conversations');

      case 'delete':
        // Users can delete their own conversations or if they have permission to delete any.
        if ($entity->getOwnerId() === $account->id()) {
          return AccessResult::allowedIfHasPermission($account, 'delete own ai conversations');
        }
        return AccessResult::allowedIfHasPermission($account, 'delete any ai conversations');

      default:
        // No opinion.
        return AccessResult::neutral();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'create ai conversations');
  }

}
