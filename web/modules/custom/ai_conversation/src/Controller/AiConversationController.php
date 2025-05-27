<?php

declare(strict_types=1);

namespace Drupal\ai_conversation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\views\Views;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ai_conversation\Service\AiConversationManager;
use Drupal\ai_conversation\AiConversationInterface;
use Drupal\ai_conversation\AiConversationThreadInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Returns responses for AI conversation routes.
 */
class AiConversationController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The AI conversation manager.
   *
   * @var \Drupal\ai_conversation\Service\AiConversationManager
   */
  protected $conversationManager;

  /**
   * Constructs a new AiConversationController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\ai_conversation\Service\AiConversationManager $conversation_manager
   *   The conversation manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AiConversationManager $conversation_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->conversationManager = $conversation_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('ai_conversation.manager')
    );
  }

  /**
   * Displays a list of user's conversations.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function userConversations() {
    // Load and embed the user conversations view.
    $view = Views::getView('user_ai_conversations');
    
    if (!$view) {
      // Fallback if view doesn't exist.
      return [
        '#markup' => $this->t('The conversations view is not available. Please contact the administrator.'),
      ];
    }
    
    // Set the display.
    $view->setDisplay('default');
    
    // Execute the view.
    $view->execute();
    
    // Build the render array.
    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['ai-conversation-list']],
    ];
    
    // Add a link to start a new conversation.
    $build['new_conversation'] = [
      '#type' => 'link', 
      '#title' => $this->t('Start New Conversation'),
      '#url' => \Drupal\Core\Url::fromRoute('entity.ai_conversation.add_form'),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
      ],
      '#weight' => -10,
    ];
    
    // Add the view.
    $build['view'] = $view->render();
    
    return $build;
  }

  /**
   * Resumes a conversation by redirecting to the chat interface.
   *
   * @param \Drupal\ai_conversation\AiConversationInterface $ai_conversation
   *   The conversation to resume.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the chat interface.
   */
  public function resume(AiConversationInterface $ai_conversation) {
    // Check access.
    if (!$ai_conversation->access('view')) {
      throw new AccessDeniedHttpException();
    }

    // Get the default thread or the most recent one.
    $thread_id = $ai_conversation->getDefaultThreadId();
    if (!$thread_id) {
      $threads = $this->conversationManager->getConversationThreads($ai_conversation->id());
      if (!empty($threads)) {
        $thread = end($threads);
        $thread_id = $thread->id();
      }
    }

    if ($thread_id) {
      return $this->redirect('ai_conversation.resume_thread', [
        'ai_conversation' => $ai_conversation->id(),
        'ai_conversation_thread' => $thread_id,
      ]);
    }

    // If no threads exist, create a new one and redirect to the chat.
    $this->messenger()->addWarning($this->t('This conversation has no threads. Starting a new conversation.'));
    return $this->redirect('entity.ai_conversation.canonical', [
      'ai_conversation' => $ai_conversation->id(),
    ]);
  }

  /**
   * Resumes a specific conversation thread.
   *
   * @param \Drupal\ai_conversation\AiConversationInterface $ai_conversation
   *   The conversation.
   * @param \Drupal\ai_conversation\AiConversationThreadInterface $ai_conversation_thread
   *   The thread to resume.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the chat interface.
   */
  public function resumeThread(AiConversationInterface $ai_conversation, AiConversationThreadInterface $ai_conversation_thread) {
    // Check access.
    if (!$ai_conversation->access('view') || !$ai_conversation_thread->access('view')) {
      throw new AccessDeniedHttpException();
    }

    // Verify the thread belongs to the conversation.
    if ($ai_conversation_thread->getConversationId() !== $ai_conversation->id()) {
      throw new NotFoundHttpException();
    }

    // Store the thread context in the user's session for the AI chat to pick up.
    $session = $this->getRequest()->getSession();
    $session->set('ai_conversation_resume', [
      'conversation_id' => $ai_conversation->id(),
      'thread_id' => $ai_conversation_thread->id(),
      'messages' => $ai_conversation_thread->getMessages(),
    ]);

    $this->messenger()->addStatus($this->t('Conversation thread resumed. You can continue from where you left off.'));

    // Redirect to the main chat interface.
    return new RedirectResponse('/chat');
  }

}
