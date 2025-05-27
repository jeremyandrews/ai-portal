<?php

declare(strict_types=1);

namespace Drupal\Tests\ai_conversation\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\Database\Database;

/**
 * Tests the AI Conversation module schema installation.
 *
 * @group ai_conversation
 */
class AiConversationSchemaTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'ai_assistant_api',
    'ai_conversation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    
    // Install database schema.
    $this->installEntitySchema('user');
    $this->installEntitySchema('ai_conversation');
    $this->installEntitySchema('ai_conversation_thread');
  }

  /**
   * Tests that the database tables are created correctly.
   */
  public function testDatabaseTablesExist(): void {
    $connection = Database::getConnection();
    $schema = $connection->schema();

    // Check ai_conversation table exists
    $this->assertTrue($schema->tableExists('ai_conversation'), 'The ai_conversation table exists.');
    
    // Check ai_conversation_thread table exists
    $this->assertTrue($schema->tableExists('ai_conversation_thread'), 'The ai_conversation_thread table exists.');
  }

  /**
   * Tests that the ai_conversation table has correct columns.
   */
  public function testAiConversationTableSchema(): void {
    $connection = Database::getConnection();
    $schema = $connection->schema();

    $expected_fields = [
      'id',
      'uuid',
      'langcode',
      'user_id',
      'title',
      'model',
      'provider',
      'temperature',
      'max_tokens',
      'metadata',
      'created',
      'changed',
      'default_thread_id'
    ];

    foreach ($expected_fields as $field) {
      $this->assertTrue(
        $schema->fieldExists('ai_conversation', $field),
        "The ai_conversation table has the '$field' field."
      );
    }

    // Check indexes
    $this->assertTrue(
      $this->indexExists('ai_conversation', 'ai_conversation__user_created'),
      'The ai_conversation table has user_created index.'
    );
  }

  /**
   * Tests that the ai_conversation_thread table has correct columns.
   */
  public function testAiConversationThreadTableSchema(): void {
    $connection = Database::getConnection();
    $schema = $connection->schema();

    $expected_fields = [
      'id',
      'uuid',
      'langcode',
      'conversation_id',
      'parent_thread_id',
      'title',
      'branch_point',
      'metadata',
      'created',
      'changed'
    ];

    foreach ($expected_fields as $field) {
      $this->assertTrue(
        $schema->fieldExists('ai_conversation_thread', $field),
        "The ai_conversation_thread table has the '$field' field."
      );
    }

    // Check indexes
    $this->assertTrue(
      $this->indexExists('ai_conversation_thread', 'ai_conversation_thread__conversation'),
      'The ai_conversation_thread table has conversation index.'
    );
    
    $this->assertTrue(
      $this->indexExists('ai_conversation_thread', 'ai_conversation_thread__parent'),
      'The ai_conversation_thread table has parent index.'
    );
  }

  /**
   * Helper method to check if an index exists.
   */
  protected function indexExists(string $table, string $index): bool {
    $connection = Database::getConnection();
    $schema = $connection->schema();
    
    // Get table info
    $query = $connection->query("SHOW INDEX FROM {" . $table . "} WHERE Key_name = :index", [
      ':index' => $index,
    ]);
    
    return (bool) $query->fetchField();
  }

}
