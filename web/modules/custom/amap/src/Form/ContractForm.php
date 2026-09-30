<?php

namespace Drupal\amap\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Contract edit forms.
 *
 * @ingroup amap
 */
class ContractForm extends ContentEntityForm
{

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    /* @var $entity \Drupal\amap\Entity\Contract */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    $form['type']['widget']['#sort_options']  = TRUE;
    if (empty($form["type"]["widget"]["#default_value"])) {
      $form["type"]["widget"]["#default_value"]=58;
      }

    unset($form['actions']['delete']);

    $url = \Drupal\Core\Url::fromRoute('view.amap_contracts.page_1');
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => $url,
      '#attributes' => [
        'class' => 'button',
      ],
      '#weight' => '20',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state)
  {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        \Drupal::messenger()->addMessage($this->t('Contract « @label » has been added.', [
          '@label' => $entity->label()
        ]));
        break;
      case SAVED_UPDATED:
        \Drupal::messenger()->addMessage($this->t('Contract « @label » has been updated.', [
          '@label' => $entity->label()
        ]));
      default:
        break;
    }

    $form_state->setRedirect('view.amap_contracts.page_1');

  }

}
