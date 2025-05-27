<?php

/**
 * @file
 * Manual verification script for AI Conversation module.
 */

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;
use Drupal\ai_conversation\Entity\AiConversation;
use Drupal\ai_conversation\Entity\AiConversationThread;
use Drupal\user\Entity\User;
use Drupal\Core\Database\Database;

// Bootstrap Drupal.
$autoloader = require_once 'web/autoload.php';

$kernel = new DrupalKernel('prod', $autoloader);
$request = Request::create('/');
$response = $kernel->handle($request);
$kernel->terminate($request, $response);

// Set up container.
$container = $kernel->getContainer();

// Colors for output.
$green = "\033[32m";
$red = "\033[31m";
$yellow = "\033[33m";
$reset = "\033[0m";

$tests_passed = 0;
$tests_failed = 0;

function test_assert($condition, $message) {
  global $green, $red, $reset, $tests_passed, $tests_failed;
  if ($condition) {
    echo "{$green}✓{$reset} $message\n";
    $tests_passed++;
  } else {
    echo "{$red}✗{$reset} $message\n";
    $tests_failed++;
  }
}

echo "\n{$yellow}AI Conversation Module Verification{$reset}\n";
echo str_repeat("=", 50) . "\n\n";

// Test 1: Module is enabled
echo "{$yellow}1. Module Installation{$reset}\n";
$module_handler = \Drupal::moduleHandler();
test_assert(
  $module_handler->moduleExists('ai_conversation'),
  "AI Conversation module is enabled"
);
test_assert(
  $module_handler->moduleExists('ai_assistant_api'),
  "AI Assistant API module is enabled"
);

// Test 2: Database tables exist
echo "\n{$yellow}2. Database Schema{$reset}\n";
$connection = Database::getConnection();
$schema = $connection->schema();

test_assert(
  $schema->tableExists('ai_conversation'),
  "Table 'ai_conversation' exists"
);
test_assert(
  $schema->tableExists('ai_conversation_thread'),
  "Table 'ai_conversation_thread' exists"
);

// Check specific fields
$ai_conversation_fields = [
  'id', 'uuid', 'user_id', 'title', 'model', 'provider',
  'temperature', 'max_tokens', 'metadata', 'created', 'changed'
];
foreach ($ai_conversation_fields as $field) {
  test_assert(
    $schema->fieldExists('ai_conversation', $field),
    "Field 'ai_conversation.$field' exists"
  );
}

// Test 3: Entity types are defined
echo "\n{$yellow}3. Entity Types{$reset}\n";
$entity_type_manager = \Drupal::entityTypeManager();

test_assert(
  $entity_type_manager->hasDefinition('ai_conversation'),
  "Entity type 'ai_conversation' is defined"
);
test_assert(
  $entity_type_manager->hasDefinition('ai_conversation_thread'),
  "Entity type 'ai_conversation_thread' is defined"
);

// Test 4: Permissions are defined
echo "\n{$yellow}4. Permissions{$reset}\n";
$permissions = \Drupal::service('user.permissions')->getPermissions();

$expected_permissions = [
  'administer ai conversations',
  'view own ai conversations',
  'view any ai conversations',
  'create ai conversations',
  'edit own ai conversations',
  'edit any ai conversations',
  'delete own ai conversations',
  'delete any ai conversations',
  'view ai conversation threads',
  'create ai conversation threads',
];

foreach ($expected_permissions as $permission) {
  test_assert(
    isset($permissions[$permission]),
    "Permission '$permission' is defined"
  );
}

// Test 5: Routes are defined
echo "\n{$yellow}5. Routes{$reset}\n";
$route_provider = \Drupal::service('router.route_provider');

$routes_to_test = [
  'entity.ai_conversation.canonical',
  'entity.ai_conversation.add_form',
  'entity.ai_conversation.edit_form',
  'entity.ai_conversation.delete_form',
  'entity.ai_conversation.collection',
  'ai_conversation.resume',
  'ai_conversation.settings',
  'view.user_ai_conversations.page_1',
];

foreach ($routes_to_test as $route_name) {
  try {
    $route = $route_provider->getRouteByName($route_name);
    test_assert(true, "Route '$route_name' exists");
  } catch (\Exception $e) {
    test_assert(false, "Route '$route_name' exists");
  }
}

// Test 6: Entity CRUD operations
echo "\n{$yellow}6. Entity CRUD Operations{$reset}\n";

// Create a test user
$test_user = User::create([
  'name' => 'test_verify_' . time(),
  'mail' => 'test_verify_' . time() . '@example.com',
]);
$test_user->save();

// Check if authenticated users have the necessary permissions
$auth_role = \Drupal::entityTypeManager()->getStorage('user_role')->load('authenticated');
$has_permissions = FALSE;
if ($auth_role && $auth_role->hasPermission('view own ai conversations')) {
  $has_permissions = TRUE;
}

// Create a conversation
$conversation = AiConversation::create([
  'title' => 'Test Conversation',
  'user_id' => $test_user->id(),
  'model' => 'gpt-4',
  'provider' => 'openai',
  'temperature' => 0.7,
  'max_tokens' => 1000,
  'metadata' => ['test' => 'value'],
]);
$conversation->save();

test_assert(
  !empty($conversation->id()),
  "Conversation entity created with ID: " . $conversation->id()
);

// Load the conversation
$loaded = AiConversation::load($conversation->id());
test_assert(
  $loaded instanceof AiConversation && $loaded->getTitle() === 'Test Conversation',
  "Conversation entity loaded successfully"
);

// Update the conversation
$loaded->setTitle('Updated Test Conversation');
$loaded->save();
$reloaded = AiConversation::load($conversation->id());
test_assert(
  $reloaded->getTitle() === 'Updated Test Conversation',
  "Conversation entity updated successfully"
);

// Create a thread
$thread = AiConversationThread::create([
  'conversation_id' => $conversation->id(),
  'title' => 'Test Thread',
  'metadata' => ['thread_test' => 'value'],
]);
$thread->save();

test_assert(
  !empty($thread->id()),
  "Thread entity created with ID: " . $thread->id()
);

// Set default thread
$conversation->setDefaultThreadId($thread->id());
$conversation->save();
test_assert(
  $conversation->getDefaultThreadId() == $thread->id(),
  "Default thread set successfully"
);

// Test 7: Access control
echo "\n{$yellow}7. Access Control{$reset}\n";

// Create another user
$other_user = User::create([
  'name' => 'other_verify_' . time(),
  'mail' => 'other_verify_' . time() . '@example.com',
]);
$other_user->save();

// The authenticated role is assigned automatically, no need to add it

// Test access
// Note: Access depends on permissions being granted to authenticated users
if ($has_permissions) {
  test_assert(
    $conversation->access('view', $test_user),
    "Owner can view their own conversation (permissions granted)"
  );
} else {
  test_assert(
    !$conversation->access('view', $test_user),
    "Owner cannot view without permissions (expected - grant 'view own ai conversations' to authenticated users)"
  );
  echo "  {$yellow}Note: Grant permissions to authenticated users in admin UI{$reset}\n";
}

test_assert(
  !$conversation->access('view', $other_user),
  "Other user cannot view conversation they don't own"
);

// Test 8: Views exist
echo "\n{$yellow}8. Views Configuration{$reset}\n";
$view_storage = \Drupal::entityTypeManager()->getStorage('view');

$views_to_test = [
  'user_ai_conversations',
  'admin_ai_conversations',
  'ai_conversation_threads',
];

foreach ($views_to_test as $view_id) {
  $view = $view_storage->load($view_id);
  test_assert(
    $view !== NULL,
    "View '$view_id' exists"
  );
}

// Test 9: Services are available
echo "\n{$yellow}9. Services{$reset}\n";
$services_to_test = [
  'ai_conversation.manager',
];

foreach ($services_to_test as $service_id) {
  test_assert(
    \Drupal::hasService($service_id),
    "Service '$service_id' is available"
  );
}

// Clean up test entities
echo "\n{$yellow}10. Cleanup{$reset}\n";
$thread->delete();
$conversation->delete();
$test_user->delete();
$other_user->delete();
test_assert(true, "Test entities cleaned up");

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "{$yellow}Summary:{$reset}\n";
echo "{$green}Passed:{$reset} $tests_passed\n";
echo "{$red}Failed:{$reset} $tests_failed\n";

if ($tests_failed === 0) {
  echo "\n{$green}✓ All tests passed! The AI Conversation module is fully functional.{$reset}\n";
  exit(0);
} else {
  echo "\n{$red}✗ Some tests failed. Please check the module configuration.{$reset}\n";
  exit(1);
}
