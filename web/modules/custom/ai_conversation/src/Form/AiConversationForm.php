<?php

declare(strict_types=1);

namespace Drupal\ai_conversation\Form;

use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\Service\AiProviderFormHelper;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for AI conversation edit forms.
 *
 * @ingroup ai_conversation
 */
class AiConversationForm extends ContentEntityForm {

  /**
   * The AI provider plugin manager.
   *
   * @var \Drupal\ai\AiProviderPluginManager
   */
  protected $aiProviderManager;

  /**
   * The AI provider form helper.
   *
   * @var \Drupal\ai\Service\AiProviderFormHelper
   */
  protected $aiProviderFormHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->aiProviderManager = $container->get('ai.provider');
    $instance->aiProviderFormHelper = $container->get('ai.form_helper');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\ai_conversation\AiConversationInterface $entity */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    // Add a wrapper for AJAX.
    $form['ai_settings'] = [
      '#type' => 'container',
      '#weight' => -10,
      '#attributes' => ['id' => 'ai-settings-wrapper'],
    ];

    // Get current values or defaults.
    $provider = $form_state->getValue('ai_provider') ?: $entity->getProvider();
    $model = $form_state->getValue('ai_model') ?: $entity->getModel();

    // Add AI provider selection.
    $providers = $this->aiProviderFormHelper->getAiProvidersOptions('chat');
    
    $form['ai_settings']['ai_provider'] = [
      '#type' => 'select',
      '#title' => $this->t('AI Provider'),
      '#options' => $providers,
      '#default_value' => $provider,
      '#required' => TRUE,
      '#empty_option' => $this->t('- Select a provider -'),
      '#ajax' => [
        'callback' => [$this, 'updateModels'],
        'wrapper' => 'ai-models-wrapper',
        'event' => 'change',
      ],
    ];

    // Container for models dropdown.
    $form['ai_settings']['models_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'ai-models-wrapper'],
    ];

    // Add models dropdown if provider is selected.
    if ($provider && !empty($providers[$provider])) {
      $provider_instance = $this->aiProviderManager->createInstance($provider);
      $models = $provider_instance->getModelOptions('chat');
      
      $form['ai_settings']['models_container']['ai_model'] = [
        '#type' => 'select',
        '#title' => $this->t('Model'),
        '#options' => $models,
        '#default_value' => $model,
        '#required' => TRUE,
        '#empty_option' => $this->t('- Select a model -'),
      ];
    }
    else {
      $form['ai_settings']['models_container']['ai_model'] = [
        '#type' => 'select',
        '#title' => $this->t('Model'),
        '#options' => [],
        '#empty_option' => $this->t('- Select a provider first -'),
        '#disabled' => TRUE,
      ];
    }

    // Hide the default provider and model fields.
    $form['provider']['#access'] = FALSE;
    $form['model']['#access'] = FALSE;

    // Add advanced settings fieldset.
    $form['advanced_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced Settings'),
      '#open' => FALSE,
      '#weight' => 10,
    ];

    // Move temperature and max_tokens to advanced settings.
    if (isset($form['temperature'])) {
      $form['temperature']['#group'] = 'advanced_settings';
    }
    if (isset($form['max_tokens'])) {
      $form['max_tokens']['#group'] = 'advanced_settings';
    }

    return $form;
  }

  /**
   * AJAX callback to update models dropdown.
   */
  public function updateModels(array &$form, FormStateInterface $form_state) {
    return $form['ai_settings']['models_container'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validate that both provider and model are selected.
    $provider = $form_state->getValue('ai_provider');
    $model = $form_state->getValue('ai_model');

    if (!$provider) {
      $form_state->setErrorByName('ai_provider', $this->t('Please select an AI provider.'));
    }
    if (!$model && $provider) {
      $form_state->setErrorByName('ai_model', $this->t('Please select a model.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\ai_conversation\AiConversationInterface $entity */
    $entity = $this->entity;

    // Set the provider and model from our custom fields.
    $entity->setProvider($form_state->getValue('ai_provider'));
    $entity->setModel($form_state->getValue('ai_model'));

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
