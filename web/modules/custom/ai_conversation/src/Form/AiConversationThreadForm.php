<?php

declare(strict_types=1);

namespace Drupal\ai_conversation\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for AI conversation thread edit forms.
 *
 * @ingroup ai_conversation
 */
class AiConversationThreadForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\ai_conversation\AiConversationThreadInterface $entity */
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\ai_conversation\AiConversationThreadInterface $entity */
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the AI conversation thread.'));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the AI conversation thread.'));
    }

    $form_state->setRedirect('entity.ai_conversation_thread.canonical', [
      'ai_conversation' => $entity->getConversationId(),
      'ai_conversation_thread' => $entity->id(),
    ]);

    return $status;
  }

}
