<?php

declare(strict_types=1);

namespace Drupal\amap\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Formulaire de confirmation pour purger un panier de ses réservations
 */
final class BasketPurgeForm extends ConfirmFormBase {

  protected ?object $basket = NULL;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Charge l'entité depuis le paramètre {id} de la route.
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?string $id = NULL): array {
    $this->basket = $id
      ? $this->entityTypeManager->getStorage('basket')->load($id)
      : NULL;

    if (!$this->basket) {
      $this->messenger()->addError($this->t('Basket can\'t be found.'));
      return [];
    }

    return parent::buildForm($form, $form_state);
  }

  public function getQuestion(): TranslatableMarkup {
    return $this->t('Are you sure you want to purge basket « @id »?', ['@id' => $this->basket->id()]);
  }

  public function getDescription(): TranslatableMarkup {
    return $this->t('This action clears the person who reserved the basket. It can\'t be undone.');
  }

  public function getConfirmText(): TranslatableMarkup {
    return $this->t('Purge');
  }

  public function getCancelText(): TranslatableMarkup {
    return $this->t('Cancel');
  }

  public function getFormId(): string {
    return 'amap_basket_purge_form';
  }

  public function getCancelUrl(): Url {
    return Url::fromRoute('view.amap_baskets.page_1');
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\amap\Entity\Basket $basket */
    $basket = $this->basket;

    $basket->buyer = "";
    $basket->save();

    $this->messenger()
      ->addMessage($this->t('The basket « @id » has been purged.', ['@id' => $this->basket->id()]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
