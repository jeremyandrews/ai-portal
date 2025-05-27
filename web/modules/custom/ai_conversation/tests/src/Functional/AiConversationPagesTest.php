<?php

declare(strict_types=1);

namespace Drupal\Tests\ai_conversation\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\ai_conversation\Entity\AiConversation;
use Drupal\ai_conversation\Entity\AiConversationThread;

/**
 * Tests AI Conversation module pages and routes.
 *
 * @group ai_conversation
 */
class AiConversationPagesTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ai_assistant_api',
    'ai_conversation',
    'views',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A test user with conversation permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authenticatedUser;

  /**
   * An admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create authenticated user with AI conversation permissions.
    $this->authenticatedUser = $this->createUser([
      'view own ai conversations',
      'create ai conversations',
      'edit own ai conversations',
      'delete own ai conversations',
      'view ai conversation threads',
      'create ai conversation threads',
    ]);

    // Create admin user.
    $this->adminUser = $this->createUser([
      'administer ai conversations',
      'view any ai conversations',
      'edit any ai conversations',
      'delete any ai conversations',
      'access administration pages',
    ]);

    // Place blocks for messages.
    $this->drupalPlaceBlock('system_messages_block');
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests the user conversations page.
   */
  public function testUserConversationsPage(): void {
    // Test anonymous access is denied.
    $this->drupalGet('/user/conversations');
    $this->assertSession()->statusCodeEquals(403);

    // Login as authenticated user.
    $this->drupalLogin($this->authenticatedUser);

    // Test page loads with no conversations.
    $this->drupalGet('/user/conversations');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('My AI Conversations');
    $this->assertSession()->pageTextContains('You have no AI conversations yet');
    $this->assertSession()->linkExists('Start a new conversation');

    // Create a conversation.
    $conversation = AiConversation::create([
      'title' => 'Test Conversation',
      'user_id' => $this->authenticatedUser->id(),
      'model' => 'gpt-4',
      'provider' => 'openai',
    ]);
    $conversation->save();

    // Test page shows the conversation.
    $this->drupalGet('/user/conversations');
    $this->assertSession()->pageTextContains('Test Conversation');
    $this->assertSession()->pageTextNotContains('You have no AI conversations yet');

    // Test operations links exist.
    $this->assertSession()->linkExists('View');
    $this->assertSession()->linkExists('Edit');
    $this->assertSession()->linkExists('Delete');
  }

  /**
   * Tests creating a conversation.
   */
  public function testCreateConversation(): void {
    $this->drupalLogin($this->authenticatedUser);

    // Visit add page.
    $this->drupalGet('/ai-conversation/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Add AI Conversation');

    // Fill out form.
    $edit = [
      'title[0][value]' => 'My New Conversation',
      'model[0][value]' => 'gpt-4',
      'provider[0][value]' => 'openai',
      'temperature[0][value]' => '0.7',
      'max_tokens[0][value]' => '1000',
    ];
    $this->submitForm($edit, 'Save');

    // Verify created.
    $this->assertSession()->pageTextContains('Created the My New Conversation AI Conversation.');
    
    // Verify it shows up in the list.
    $this->drupalGet('/user/conversations');
    $this->assertSession()->pageTextContains('My New Conversation');
  }

  /**
   * Tests editing a conversation.
   */
  public function testEditConversation(): void {
    $this->drupalLogin($this->authenticatedUser);

    // Create a conversation.
    $conversation = AiConversation::create([
      'title' => 'Original Title',
      'user_id' => $this->authenticatedUser->id(),
      'model' => 'gpt-3.5-turbo',
      'provider' => 'openai',
    ]);
    $conversation->save();

    // Visit edit page.
    $this->drupalGet('/ai-conversation/' . $conversation->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldValueEquals('title[0][value]', 'Original Title');

    // Update the title.
    $edit = [
      'title[0][value]' => 'Updated Title',
    ];
    $this->submitForm($edit, 'Save');

    // Verify updated.
    $this->assertSession()->pageTextContains('Saved the Updated Title AI Conversation.');
    
    // Verify update shows in list.
    $this->drupalGet('/user/conversations');
    $this->assertSession()->pageTextContains('Updated Title');
    $this->assertSession()->pageTextNotContains('Original Title');
  }

  /**
   * Tests deleting a conversation.
   */
  public function testDeleteConversation(): void {
    $this->drupalLogin($this->authenticatedUser);

    // Create a conversation.
    $conversation = AiConversation::create([
      'title' => 'To Delete',
      'user_id' => $this->authenticatedUser->id(),
      'model' => 'gpt-4',
      'provider' => 'openai',
    ]);
    $conversation->save();

    // Visit delete page.
    $this->drupalGet('/ai-conversation/' . $conversation->id() . '/delete');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Are you sure you want to delete the AI Conversation To Delete?');

    // Confirm deletion.
    $this->submitForm([], 'Delete');

    // Verify deleted.
    $this->assertSession()->pageTextContains('The AI Conversation To Delete has been deleted.');
    
    // Verify it's gone from the list.
    $this->drupalGet('/user/conversations');
    $this->assertSession()->pageTextNotContains('To Delete');
  }

  /**
   * Tests access control for other users' conversations.
   */
  public function testAccessControl(): void {
    // Create another user.
    $otherUser = $this->createUser([
      'view own ai conversations',
      'edit own ai conversations',
    ]);

    // Create a conversation owned by the other user.
    $conversation = AiConversation::create([
      'title' => 'Other User Conversation',
      'user_id' => $otherUser->id(),
      'model' => 'gpt-4',
      'provider' => 'openai',
    ]);
    $conversation->save();

    // Login as authenticated user.
    $this->drupalLogin($this->authenticatedUser);

    // Try to view - should be denied.
    $this->drupalGet('/ai-conversation/' . $conversation->id());
    $this->assertSession()->statusCodeEquals(403);

    // Try to edit - should be denied.
    $this->drupalGet('/ai-conversation/' . $conversation->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);

    // Try to delete - should be denied.
    $this->drupalGet('/ai-conversation/' . $conversation->id() . '/delete');
    $this->assertSession()->statusCodeEquals(403);

    // The conversation should not appear in the user's list.
    $this->drupalGet('/user/conversations');
    $this->assertSession()->pageTextNotContains('Other User Conversation');
  }

  /**
   * Tests admin conversations page.
   */
  public function testAdminConversationsPage(): void {
    // Create conversations for different users.
    $conversation1 = AiConversation::create([
      'title' => 'User 1 Conversation',
      'user_id' => $this->authenticatedUser->id(),
      'model' => 'gpt-4',
      'provider' => 'openai',
    ]);
    $conversation1->save();

    $otherUser = $this->createUser();
    $conversation2 = AiConversation::create([
      'title' => 'User 2 Conversation',
      'user_id' => $otherUser->id(),
      'model' => 'gpt-3.5-turbo',
      'provider' => 'openai',
    ]);
    $conversation2->save();

    // Regular user should not access admin page.
    $this->drupalLogin($this->authenticatedUser);
    $this->drupalGet('/admin/content/ai-conversations');
    $this->assertSession()->statusCodeEquals(403);

    // Admin should see all conversations.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/content/ai-conversations');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('AI Conversations');
    $this->assertSession()->pageTextContains('User 1 Conversation');
    $this->assertSession()->pageTextContains('User 2 Conversation');
  }

  /**
   * Tests conversation with threads.
   */
  public function testConversationThreads(): void {
    $this->drupalLogin($this->authenticatedUser);

    // Create a conversation.
    $conversation = AiConversation::create([
      'title' => 'Conversation with Threads',
      'user_id' => $this->authenticatedUser->id(),
      'model' => 'gpt-4',
      'provider' => 'openai',
    ]);
    $conversation->save();

    // Create threads.
    $thread1 = AiConversationThread::create([
      'conversation_id' => $conversation->id(),
      'title' => 'Main Thread',
    ]);
    $thread1->save();

    $thread2 = AiConversationThread::create([
      'conversation_id' => $conversation->id(),
      'parent_thread_id' => $thread1->id(),
      'title' => 'Branch Thread',
      'branch_point' => 5,
    ]);
    $thread2->save();

    // Set default thread.
    $conversation->setDefaultThreadId($thread1->id());
    $conversation->save();

    // View conversation page.
    $this->drupalGet('/ai-conversation/' . $conversation->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Conversation with Threads');
  }

  /**
   * Tests entity routes are generated correctly.
   */
  public function testEntityRoutes(): void {
    $this->drupalLogin($this->authenticatedUser);

    // Create a conversation.
    $conversation = AiConversation::create([
      'title' => 'Route Test',
      'user_id' => $this->authenticatedUser->id(),
      'model' => 'gpt-4',
      'provider' => 'openai',
    ]);
    $conversation->save();

    // Test canonical route.
    $this->drupalGet($conversation->toUrl());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Route Test');

    // Test edit route.
    $this->drupalGet($conversation->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldValueEquals('title[0][value]', 'Route Test');

    // Test delete route.
    $this->drupalGet($conversation->toUrl('delete-form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Are you sure you want to delete');
  }

  /**
   * Tests Views integration.
   */
  public function testViewsIntegration(): void {
    $this->drupalLogin($this->authenticatedUser);

    // Create multiple conversations.
    for ($i = 1; $i <= 3; $i++) {
      $conversation = AiConversation::create([
        'title' => "Conversation $i",
        'user_id' => $this->authenticatedUser->id(),
        'model' => 'gpt-4',
        'provider' => 'openai',
        'created' => time() - ($i * 3600), // Stagger creation times
      ]);
      $conversation->save();
    }

    // Visit user conversations page.
    $this->drupalGet('/user/conversations');
    
    // Verify conversations are sorted by changed date (newest first).
    $rows = $this->xpath('//table//tbody//tr');
    $this->assertCount(3, $rows);
    
    // Check table headers exist.
    $this->assertSession()->pageTextContains('Title');
    $this->assertSession()->pageTextContains('Created');
    $this->assertSession()->pageTextContains('Last updated');
    $this->assertSession()->pageTextContains('Operations');
  }

}
