<?php

declare(strict_types=1);

namespace Drupal\Tests\ai_conversation\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\ai_conversation\Entity\AiConversation;
use Drupal\ai_conversation\Entity\AiConversationThread;
use Drupal\user\Entity\User;

/**
 * Tests AI Conversation entity operations.
 *
 * @group ai_conversation
 */
class AiConversationEntityTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ai_assistant_api',
    'ai_conversation',
  ];

  /**
   * A test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    
    // Install entity schemas.
    $this->installEntitySchema('ai_conversation');
    $this->installEntitySchema('ai_conversation_thread');
    
    // Create a test user.
    $this->testUser = User::create([
      'name' => 'test_user',
      'mail' => 'test@example.com',
    ]);
    $this->testUser->save();
  }

  /**
   * Tests creating an AI conversation entity.
   */
  public function testCreateConversation(): void {
    $conversation = AiConversation::create([
      'title' => 'Test Conversation',
      'user_id' => $this->testUser->id(),
      'model' => 'gpt-4',
      'provider' => 'openai',
      'temperature' => 0.7,
      'max_tokens' => 1000,
      'metadata' => [
        'test_key' => 'test_value',
      ],
    ]);
    
    $this->assertInstanceOf(AiConversation::class, $conversation);
    $this->assertEquals('Test Conversation', $conversation->getTitle());
    $this->assertEquals($this->testUser->id(), $conversation->getOwnerId());
    $this->assertEquals('gpt-4', $conversation->getModel());
    $this->assertEquals('openai', $conversation->getProvider());
    $this->assertEquals(0.7, $conversation->getTemperature());
    $this->assertEquals(1000, $conversation->getMaxTokens());
    
    // Save and verify ID is assigned.
    $conversation->save();
    $this->assertNotEmpty($conversation->id());
    
    // Verify metadata.
    $metadata = $conversation->getMetadata();
    $this->assertEquals('test_value', $metadata['test_key']);
  }

  /**
   * Tests loading and updating a conversation.
   */
  public function testLoadUpdateConversation(): void {
    // Create and save a conversation.
    $conversation = AiConversation::create([
      'title' => 'Original Title',
      'user_id' => $this->testUser->id(),
      'model' => 'gpt-3.5-turbo',
      'provider' => 'openai',
    ]);
    $conversation->save();
    $id = $conversation->id();
    
    // Load the conversation.
    $loaded = AiConversation::load($id);
    $this->assertInstanceOf(AiConversation::class, $loaded);
    $this->assertEquals('Original Title', $loaded->getTitle());
    
    // Update the conversation.
    $loaded->setTitle('Updated Title');
    $loaded->setModel('gpt-4');
    $loaded->save();
    
    // Reload and verify updates.
    $reloaded = AiConversation::load($id);
    $this->assertEquals('Updated Title', $reloaded->getTitle());
    $this->assertEquals('gpt-4', $reloaded->getModel());
  }

  /**
   * Tests deleting a conversation.
   */
  public function testDeleteConversation(): void {
    $conversation = AiConversation::create([
      'title' => 'To Delete',
      'user_id' => $this->testUser->id(),
      'model' => 'gpt-4',
      'provider' => 'openai',
    ]);
    $conversation->save();
    $id = $conversation->id();
    
    // Verify it exists.
    $this->assertNotNull(AiConversation::load($id));
    
    // Delete it.
    $conversation->delete();
    
    // Verify it's gone.
    $this->assertNull(AiConversation::load($id));
  }

  /**
   * Tests creating conversation threads.
   */
  public function testCreateThread(): void {
    // Create a conversation first.
    $conversation = AiConversation::create([
      'title' => 'Test Conversation',
      'user_id' => $this->testUser->id(),
      'model' => 'gpt-4',
      'provider' => 'openai',
    ]);
    $conversation->save();
    
    // Create a thread.
    $thread = AiConversationThread::create([
      'conversation_id' => $conversation->id(),
      'title' => 'Main Thread',
      'metadata' => [
        'initial' => TRUE,
      ],
    ]);
    
    $this->assertInstanceOf(AiConversationThread::class, $thread);
    $this->assertEquals($conversation->id(), $thread->getConversationId());
    $this->assertEquals('Main Thread', $thread->getTitle());
    $this->assertNull($thread->getParentThreadId());
    
    $thread->save();
    $this->assertNotEmpty($thread->id());
    
    // Set as default thread.
    $conversation->setDefaultThreadId($thread->id());
    $conversation->save();
    $this->assertEquals($thread->id(), $conversation->getDefaultThreadId());
  }

  /**
   * Tests creating branched threads.
   */
  public function testBranchThread(): void {
    // Create conversation and initial thread.
    $conversation = AiConversation::create([
      'title' => 'Test Conversation',
      'user_id' => $this->testUser->id(),
      'model' => 'gpt-4',
      'provider' => 'openai',
    ]);
    $conversation->save();
    
    $parentThread = AiConversationThread::create([
      'conversation_id' => $conversation->id(),
      'title' => 'Parent Thread',
    ]);
    $parentThread->save();
    
    // Create a branched thread.
    $branchThread = AiConversationThread::create([
      'conversation_id' => $conversation->id(),
      'parent_thread_id' => $parentThread->id(),
      'title' => 'Branch Thread',
      'branch_point' => 5,
      'metadata' => [
        'branch_reason' => 'Alternative approach',
      ],
    ]);
    
    $this->assertEquals($parentThread->id(), $branchThread->getParentThreadId());
    $this->assertEquals(5, $branchThread->getBranchPoint());
    
    $branchThread->save();
    
    // Load parent thread and verify relationship.
    $loadedParent = AiConversationThread::load($parentThread->id());
    $this->assertNotNull($loadedParent);
  }

  /**
   * Tests conversation with multiple threads.
   */
  public function testConversationWithMultipleThreads(): void {
    $conversation = AiConversation::create([
      'title' => 'Multi-thread Conversation',
      'user_id' => $this->testUser->id(),
      'model' => 'gpt-4',
      'provider' => 'openai',
    ]);
    $conversation->save();
    
    // Create multiple threads.
    $thread1 = AiConversationThread::create([
      'conversation_id' => $conversation->id(),
      'title' => 'Thread 1',
    ]);
    $thread1->save();
    
    $thread2 = AiConversationThread::create([
      'conversation_id' => $conversation->id(),
      'title' => 'Thread 2',
    ]);
    $thread2->save();
    
    $thread3 = AiConversationThread::create([
      'conversation_id' => $conversation->id(),
      'parent_thread_id' => $thread1->id(),
      'title' => 'Thread 3 (branch of 1)',
      'branch_point' => 10,
    ]);
    $thread3->save();
    
    // Query threads for this conversation.
    $storage = \Drupal::entityTypeManager()->getStorage('ai_conversation_thread');
    $thread_ids = $storage->getQuery()
      ->condition('conversation_id', $conversation->id())
      ->accessCheck(FALSE)
      ->execute();
    
    $this->assertCount(3, $thread_ids);
    
    // Query only root threads (no parent).
    $root_thread_ids = $storage->getQuery()
      ->condition('conversation_id', $conversation->id())
      ->notExists('parent_thread_id')
      ->accessCheck(FALSE)
      ->execute();
    
    $this->assertCount(2, $root_thread_ids);
  }

  /**
   * Tests entity validation.
   */
  public function testEntityValidation(): void {
    // Test conversation without required fields.
    $conversation = AiConversation::create([
      'title' => 'Test',
      // Missing user_id, model, provider
    ]);
    
    $violations = $conversation->validate();
    $this->assertGreaterThan(0, $violations->count(), 'Validation should fail for missing required fields.');
    
    // Test with all required fields.
    $conversation->set('user_id', $this->testUser->id());
    $conversation->set('model', 'gpt-4');
    $conversation->set('provider', 'openai');
    
    $violations = $conversation->validate();
    $this->assertEquals(0, $violations->count(), 'Validation should pass with all required fields.');
  }

}
