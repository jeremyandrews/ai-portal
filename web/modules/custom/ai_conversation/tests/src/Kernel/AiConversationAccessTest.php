<?php

declare(strict_types=1);

namespace Drupal\Tests\ai_conversation\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\ai_conversation\Entity\AiConversation;
use Drupal\ai_conversation\Entity\AiConversationThread;
use Drupal\user\Entity\User;
use Drupal\user\Entity\Role;

/**
 * Tests AI Conversation access control and permissions.
 *
 * @group ai_conversation
 */
class AiConversationAccessTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ai_assistant_api',
    'ai_conversation',
  ];

  /**
   * Test users.
   *
   * @var \Drupal\user\UserInterface[]
   */
  protected $users = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    
    // Install entity schemas.
    $this->installEntitySchema('ai_conversation');
    $this->installEntitySchema('ai_conversation_thread');
    
    // Create test users.
    $this->users['owner'] = User::create([
      'name' => 'owner',
      'mail' => 'owner@example.com',
    ]);
    $this->users['owner']->save();
    
    $this->users['other'] = User::create([
      'name' => 'other',
      'mail' => 'other@example.com',
    ]);
    $this->users['other']->save();
    
    $this->users['admin'] = User::create([
      'name' => 'admin',
      'mail' => 'admin@example.com',
    ]);
    $this->users['admin']->save();
    
    // Grant permissions to authenticated role.
    $auth_role = Role::load('authenticated');
    $auth_role->grantPermission('view own ai conversations');
    $auth_role->grantPermission('create ai conversations');
    $auth_role->grantPermission('edit own ai conversations');
    $auth_role->grantPermission('delete own ai conversations');
    $auth_role->grantPermission('view ai conversation threads');
    $auth_role->grantPermission('create ai conversation threads');
    $auth_role->save();
    
    // Create admin role with all permissions.
    $admin_role = Role::create([
      'id' => 'ai_admin',
      'label' => 'AI Admin',
    ]);
    $admin_role->grantPermission('administer ai conversations');
    $admin_role->grantPermission('view any ai conversations');
    $admin_role->grantPermission('edit any ai conversations');
    $admin_role->grantPermission('delete any ai conversations');
    $admin_role->save();
    
    $this->users['admin']->addRole('ai_admin');
    $this->users['admin']->save();
  }

  /**
   * Tests viewing own conversations.
   */
  public function testViewOwnConversation(): void {
    // Create a conversation owned by 'owner'.
    $conversation = AiConversation::create([
      'title' => 'Owner Conversation',
      'user_id' => $this->users['owner']->id(),
      'model' => 'gpt-4',
      'provider' => 'openai',
    ]);
    $conversation->save();
    
    // Owner should be able to view.
    $this->assertTrue(
      $conversation->access('view', $this->users['owner']),
      'Owner can view their own conversation.'
    );
    
    // Other user should not be able to view.
    $this->assertFalse(
      $conversation->access('view', $this->users['other']),
      'Other user cannot view conversation they do not own.'
    );
    
    // Admin should be able to view.
    $this->assertTrue(
      $conversation->access('view', $this->users['admin']),
      'Admin can view any conversation.'
    );
  }

  /**
   * Tests editing own conversations.
   */
  public function testEditOwnConversation(): void {
    $conversation = AiConversation::create([
      'title' => 'Owner Conversation',
      'user_id' => $this->users['owner']->id(),
      'model' => 'gpt-4',
      'provider' => 'openai',
    ]);
    $conversation->save();
    
    // Owner should be able to edit.
    $this->assertTrue(
      $conversation->access('update', $this->users['owner']),
      'Owner can edit their own conversation.'
    );
    
    // Other user should not be able to edit.
    $this->assertFalse(
      $conversation->access('update', $this->users['other']),
      'Other user cannot edit conversation they do not own.'
    );
    
    // Admin should be able to edit.
    $this->assertTrue(
      $conversation->access('update', $this->users['admin']),
      'Admin can edit any conversation.'
    );
  }

  /**
   * Tests deleting own conversations.
   */
  public function testDeleteOwnConversation(): void {
    $conversation = AiConversation::create([
      'title' => 'Owner Conversation',
      'user_id' => $this->users['owner']->id(),
      'model' => 'gpt-4',
      'provider' => 'openai',
    ]);
    $conversation->save();
    
    // Owner should be able to delete.
    $this->assertTrue(
      $conversation->access('delete', $this->users['owner']),
      'Owner can delete their own conversation.'
    );
    
    // Other user should not be able to delete.
    $this->assertFalse(
      $conversation->access('delete', $this->users['other']),
      'Other user cannot delete conversation they do not own.'
    );
    
    // Admin should be able to delete.
    $this->assertTrue(
      $conversation->access('delete', $this->users['admin']),
      'Admin can delete any conversation.'
    );
  }

  /**
   * Tests creating conversations.
   */
  public function testCreateConversation(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('ai_conversation');
    
    // Authenticated users should be able to create.
    $this->assertTrue(
      $storage->createAccess(NULL, $this->users['owner']),
      'Authenticated user can create conversations.'
    );
    
    // Anonymous users should not be able to create.
    $anonymous = User::getAnonymousUser();
    $this->assertFalse(
      $storage->createAccess(NULL, $anonymous),
      'Anonymous user cannot create conversations.'
    );
  }

  /**
   * Tests thread access control.
   */
  public function testThreadAccess(): void {
    // Create conversation and thread.
    $conversation = AiConversation::create([
      'title' => 'Owner Conversation',
      'user_id' => $this->users['owner']->id(),
      'model' => 'gpt-4',
      'provider' => 'openai',
    ]);
    $conversation->save();
    
    $thread = AiConversationThread::create([
      'conversation_id' => $conversation->id(),
      'title' => 'Test Thread',
    ]);
    $thread->save();
    
    // Owner of conversation should be able to view thread.
    $this->assertTrue(
      $thread->access('view', $this->users['owner']),
      'Conversation owner can view thread.'
    );
    
    // Other user should not be able to view thread.
    $this->assertFalse(
      $thread->access('view', $this->users['other']),
      'Other user cannot view thread of conversation they do not own.'
    );
    
    // Admin should be able to view thread.
    $this->assertTrue(
      $thread->access('view', $this->users['admin']),
      'Admin can view any thread.'
    );
  }

  /**
   * Tests creating threads.
   */
  public function testCreateThread(): void {
    $conversation = AiConversation::create([
      'title' => 'Owner Conversation',
      'user_id' => $this->users['owner']->id(),
      'model' => 'gpt-4',
      'provider' => 'openai',
    ]);
    $conversation->save();
    
    $storage = \Drupal::entityTypeManager()->getStorage('ai_conversation_thread');
    
    // Owner should be able to create threads in their conversation.
    $this->assertTrue(
      $storage->createAccess(NULL, $this->users['owner'], ['conversation_id' => $conversation->id()]),
      'Conversation owner can create threads.'
    );
    
    // Other user should not be able to create threads in someone else's conversation.
    $this->assertFalse(
      $storage->createAccess(NULL, $this->users['other'], ['conversation_id' => $conversation->id()]),
      'Other user cannot create threads in conversation they do not own.'
    );
  }

  /**
   * Tests permission-based access without ownership.
   */
  public function testPermissionBasedAccess(): void {
    $conversation = AiConversation::create([
      'title' => 'Test Conversation',
      'user_id' => $this->users['owner']->id(),
      'model' => 'gpt-4',
      'provider' => 'openai',
    ]);
    $conversation->save();
    
    // Create a user with no permissions.
    $no_perm_user = User::create([
      'name' => 'noperm',
      'mail' => 'noperm@example.com',
    ]);
    $no_perm_user->save();
    
    // Remove all AI conversation permissions from authenticated role temporarily.
    $auth_role = Role::load('authenticated');
    $auth_role->revokePermission('view own ai conversations');
    $auth_role->save();
    
    // User should not be able to view even their own conversation without permission.
    $own_conversation = AiConversation::create([
      'title' => 'No Perm User Conversation',
      'user_id' => $no_perm_user->id(),
      'model' => 'gpt-4',
      'provider' => 'openai',
    ]);
    $own_conversation->save();
    
    $this->assertFalse(
      $own_conversation->access('view', $no_perm_user),
      'User cannot view their own conversation without permission.'
    );
    
    // Restore permission.
    $auth_role->grantPermission('view own ai conversations');
    $auth_role->save();
  }

  /**
   * Tests querying conversations respects access control.
   */
  public function testQueryAccess(): void {
    // Create conversations for different users.
    $conv1 = AiConversation::create([
      'title' => 'Owner Conv 1',
      'user_id' => $this->users['owner']->id(),
      'model' => 'gpt-4',
      'provider' => 'openai',
    ]);
    $conv1->save();
    
    $conv2 = AiConversation::create([
      'title' => 'Owner Conv 2',
      'user_id' => $this->users['owner']->id(),
      'model' => 'gpt-4',
      'provider' => 'openai',
    ]);
    $conv2->save();
    
    $conv3 = AiConversation::create([
      'title' => 'Other Conv',
      'user_id' => $this->users['other']->id(),
      'model' => 'gpt-4',
      'provider' => 'openai',
    ]);
    $conv3->save();
    
    // Query as owner - should see only their conversations.
    \Drupal::currentUser()->setAccount($this->users['owner']);
    $storage = \Drupal::entityTypeManager()->getStorage('ai_conversation');
    $query = $storage->getQuery()
      ->accessCheck(TRUE);
    $ids = $query->execute();
    
    $this->assertCount(2, $ids, 'Owner sees only their 2 conversations.');
    $this->assertContains($conv1->id(), $ids);
    $this->assertContains($conv2->id(), $ids);
    $this->assertNotContains($conv3->id(), $ids);
    
    // Query as admin - should see all conversations.
    \Drupal::currentUser()->setAccount($this->users['admin']);
    $query = $storage->getQuery()
      ->accessCheck(TRUE);
    $ids = $query->execute();
    
    $this->assertCount(3, $ids, 'Admin sees all 3 conversations.');
  }

}
