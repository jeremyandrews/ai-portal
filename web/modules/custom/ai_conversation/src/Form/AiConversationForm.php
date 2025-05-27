<?php

declare(strict_types=1);

namespace Drupal\ai_conversation\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for AI conversation edit forms.
 *
 * @ingroup ai_conversation
 */
class AiConversationForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\ai_conversation\AiConversationInterface $entity */
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\ai_conversation\AiConversationInterface $entity */
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label AI conversation.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label AI conversation.', [
          '%label' => $entity->label(),
        ]));
    }

    $form_state->setRedirect('entity.ai_conversation.canonical', ['ai_conversation' => $entity->id()]);

    return $status;
  }

}
