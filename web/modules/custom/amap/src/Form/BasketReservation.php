<?php

namespace Drupal\amap\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\Url;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\user\Entity\User;

/**
 * Provides a amap form.
 */
class BasketReservation extends FormBase {

  protected $step = 0;

  /**
   *
   */
  public function getFormId() {
    return 'amap_basket_reservation';
  }

  /**
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $anonymous = $this->currentUser()->isAnonymous();

    if ($this->step == 0) {

      \Drupal::moduleHandler()
        ->loadInclude('amap', 'inc', 'amap.allowed.values');
      $aProducts = amap_distribution_products();

      $header = [
        'id' => '#',
        'distributiondate' => $this->t('Distribution'),
        'product' => $this->t('Product'),
        'description' => $this->t('Description'),
        'price' => [
          'data' => [
            '#type' => 'item',
            '#markup' => $this->t('Price'),
            '#prefix' => '<span class="text-right">',
            '#suffix' => '</span>',
          ],
        ],
        'comment' => $this->t('Comment'),
        'seller' => $anonymous ? "" : $this->t('Seller'),
        'seller2' => [
          'data' => [
            '#prefix' => '<span hidden="">',
            '#suffix' => '</span>',
          ],
        ],
        'seller3' => [
          'data' => [
            '#prefix' => '<span hidden="">',
            '#suffix' => '</span>',
          ],
        ],
      ];

      $options = [];
      $query = \Drupal::database()->select('basket', 'ba');
      $query->leftJoin('distribution_date', 'dd', 'dd.id = ba.distributiondate');
      $query->leftJoin('person', 'pe', 'pe.id = ba.seller');
      $query->leftJoin('member', 'me', 'me.id = pe.member_id');
      $query->fields('ba', [
        'id',
        'product',
        'description',
        'price',
        'comment',
        'buyer',
      ]);
      $query->fields('dd', ['distributiondate']);
      $query->fields('pe', ['email', 'lastname', 'firstname', 'member_id']);
      $query->fields('me', ['designation']);
      $query->orderBy('distributiondate', 'ASC')
        ->orderBy('product', 'ASC')
        ->orderBy('seller', 'ASC');
      $results = $query->execute();
      $form_state->set('noBasketToReserve', TRUE);
      foreach ($results as $key => $result) {
        if ($result->buyer != "" ) {
          $disabledRow = TRUE;
        }
        else {
          $disabledRow = FALSE;
          $form_state->set('noBasketToReserve', FALSE);
        }
        $options[$result->id] = [
          '#disabled' => $disabledRow,
          'id' => $result->id,
          'distributiondate' => $result->distributiondate,
          'product' => $aProducts[$result->product],
          'description' => $result->description,
          'price' => [
            'data' => [
              '#type' => 'item',
              '#markup' => str_replace('.', ',', $result->price . '&nbsp;€'),
              '#prefix' => '<span class="text-right">',
              '#suffix' => '</span>',
            ],
          ],
          'comment' => $result->comment == "" ? " " : $result->comment,
          'seller' => $anonymous ? "" : $result->designation,
          'seller2' => [
            'data' => [
              '#markup' => $result->email,
              '#prefix' => '<span hidden="">',
              '#suffix' => '</span>',
            ],
          ],
          'seller3' => [
            'data' => [
              '#markup' => $result->member_id,
              '#prefix' => '<span hidden="">',
              '#suffix' => '</span>',
            ],
          ],
        ];
      }
      $previouslyselected = isset($form_state->getStorage()['baskets']) ? $form_state->getStorage()['baskets'] : [];
      $form['baskets'] = [
        '#type' => 'tableselect',
        '#options' => $options,
        '#default_value' => $previouslyselected,
        '#js_select' => FALSE,
      ];

      $form['baskets'] = array_merge($form['baskets'], [
        '#header' => $header,
        '#empty' => $this->t('There are currently no baskets to reserve.'),
        '#sticky' => TRUE,
        '#responsive' => TRUE,
        '#id' => 'baskets',
      ]);
    }

    else {

      if ($anonymous) {
        $email = isset($form_state->getStorage()['email']) ? $form_state->getStorage()['email'] : '';
        $lastname = isset($form_state->getStorage()['lastname']) ? $form_state->getStorage()['lastname'] : '';
        $firstname = isset($form_state->getStorage()['firstname']) ? $form_state->getStorage()['firstname'] : '';
        $cellphone = isset($form_state->getStorage()['cellphone']) ? $form_state->getStorage()['cellphone'] : '';
        $registeredAnonymousId = isset($form_state->getStorage()['registeredAnonymousId']) ? $form_state->getStorage()['registeredAnonymousId'] : 0;
      }
      else {
        if (!array_key_exists('email', $form_state->getStorage())) {
          $person = \Drupal::entityTypeManager()
            ->getStorage('person')
            ->load($this->currentUser()->id());
          $email = $person->email->value;
          $lastname = $person->lastname->value;
          $firstname = $person->firstname->value;
          $cellphone = $person->cellphone->value;
        }
        else {
          $email = $form_state->getStorage()['email'];
          $lastname = $form_state->getStorage()['lastname'];
          $firstname = $form_state->getStorage()['firstname'];
          $cellphone = $form_state->getStorage()['cellphone'];
        }
      }

      if ($this->step >= 1) {
        $form['email'] = [
          '#type' => 'email',
          '#title' => $this->t('Email'),
          '#size' => 64,
          '#default_value' => $email,
          '#weight' => 10,
          '#disabled' => ($this->step == 1) ? FALSE : TRUE,
        ];
      }
      if ($this->step == 2) {
        if ($registeredAnonymousId == 0) {
          $disabled = FALSE;
        }
        else {
          $disabled = TRUE;
        }
        $form['lastname'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Last Name'),
          '#size' => 32,
          '#default_value' => $lastname,
          '#disabled' => $disabled,
          '#weight' => 20,
        ];
        $form['firstname'] = [
          '#type' => 'textfield',
          '#title' => $this->t('First Name'),
          '#size' => 32,
          '#default_value' => $firstname,
          '#disabled' => $disabled,
          '#weight' => 30,
        ];
        $form['cellphone'] = [
          '#type' => 'tel',
          '#title' => $this->t('Cellphone'),
          '#size' => 10,
          '#pattern' => '\d{10}',
          '#default_value' => $cellphone,
          '#disabled' => $disabled,
          '#weight' => 40,
        ];
        $form['authorisation'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('I consent to the collection of my personal information as part of the basket exchange'),
          '#weight' => 50,
        ];
      }
    }

    if ($this->step > 0) {
      $form['previous'] = [
        '#type' => 'submit',
        '#value' => $this->t('Previous'),
        '#name' => 'previous',
        '#submit' => ['::goto_previous_step'],
        '#limit_validation_errors' => [],
        '#weight' => 97,
      ];
    }

    switch ($this->step) {
      case 0:
        if ($anonymous) {
          $label = $this->t('Next');
        }
        else {
          $label = $this->t('Submit');
        }
        $name = 'next';
        break;
      case 1:
        $label = $this->t('Next');
        $name = 'next';
        break;
      case 2:
        $label = $this->t('Submit');
        $name = 'reserve';
        break;
      default:
    }
    $noBasketToReserve = $form_state->getStorage()['noBasketToReserve'];
    if (!$noBasketToReserve) {
      $form[$name] = [
        '#type' => 'submit',
        '#name' => $name,
        '#value' => $label,
        '#weight' => 98,
      ];
    }

    $form['leave'] = [
      '#type' => 'submit',
      '#name' => 'leave',
      '#value' => $this->t('Leave'),
      '#submit' => ['::leave'],
      '#limit_validation_errors' => [],
      '#weight' => 99,
    ];

    return $form;
  }

  /**
   *
   */
  public function goto_previous_step($form, $form_state) {

    $aUserInput = $form_state->getUserInput();
    if (isset($aUserInput['email'])) {
      $form_state->set('email', $aUserInput['email']);
    }
    if (isset($aUserInput['lastname'])) {
      $form_state->set('lastname', $aUserInput['lastname']);
    }
    if (isset($aUserInput['firstname'])) {
      $form_state->set('firstname', $aUserInput['firstname']);
    }
    if (isset($aUserInput['cellphone'])) {
      $form_state->set('cellphone', $aUserInput['cellphone']);
    }
    $form_state->setRebuild();
    if ($this->currentUser()->isAnonymous()) {
      $this->step = $this->step - 1;
    }
    else {
      $this->step = 0;
    }

  }

  /**
   *
   */
  public function leave($form, $form_state) {

    $form_state->setRedirectUrl(Url::fromRoute('<front>'));

  }

  /**
   *
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $aValues = $form_state->cleanValues()->getValues();
    switch ($this->step) {

      case 0:
        if ($form_state->getTriggeringElement()['#name'] == 'next') {
          $noselection = TRUE;
          $selected = $aValues['baskets'];
          foreach ($selected as $item) {
            if ($item !== 0) {
              $noselection = FALSE;
            }
          }
          if ($noselection) {
            $form_state->setErrorByName('email', $this->t('You must choose at least one basket.'));
          }
        }
        break;

      case 1:
        if ($form_state->getTriggeringElement()['#name'] == 'next') {
          $email = $aValues['email'];
          if ($email == '') {
            $form_state->setErrorByName('email', $this->t('You must enter your email address.'));
          }
          else {
            $temp = _existsEmail($email, $_SERVER["REQUEST_URI"]);
            if ($temp) {
              if ($temp[1] == 4) {
                $form_state->setErrorByName('email', $temp[0]);
              }
            }
          }
        }
        break;

      case 2:
        if ($form_state->getTriggeringElement()['#name'] == 'reserve') {
          if ($aValues['lastname'] == '') {
            $form_state->setErrorByName('lastname', $this->t('You must enter your lastname.'));
          }
          if ($aValues['firstname'] == '') {
            $form_state->setErrorByName('firstname', $this->t('You must enter your firstname.'));
          }
          if ($aValues['cellphone'] == '') {
            $form_state->setErrorByName('cellphone', $this->t('You must enter your cellphone.'));
          }
          if ($aValues['authorisation'] == 0) {
            $form_state->setErrorByName('authorisation', $this->t('You must consent.'));
          }
        }
        break;

      default:
        break;
    }

  }

  /**
   * $form_state->set('key', 'value'). The value ends up in
   * $form_state->getStorage()['value'].
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $anonymous = $this->currentUser()->isAnonymous();
    $aCleanValues = $form_state->cleanValues()->getValues();
    $form_state->setStorage(array_merge($form_state->getStorage(), $aCleanValues));
    switch ($this->step) {

      case 0:
        $baskets = $form_state->getStorage()['baskets'];
        foreach ($baskets as $item) {
          if ($item !== 0) {
            $form_state->getStorage()["baskets"][$item] = $form["baskets"]["#options"][$item];
          }
        }
        if ($anonymous) {
          $this->step = 1;
          $form_state->setRebuild();
        }
        else {
          $this->getPersonData($form_state, $this->currentUser()->getEmail());
          $this->saveData($form_state, $this->currentUser()->id());
          $form_state->setStorage([]);
          $this->step = 0;
        }
        break;

      case 1:
        $email = $aCleanValues['email'];
        $this->getPersonData($form_state, $email);
        $registeredAnonymousId = $form_state->getStorage()['registeredAnonymousId'];
        $this->step = 2;
        $form_state->setRebuild();
        break;

      case 2:
        if ($anonymous) {
          $registeredAnonymousId = $form_state->getStorage()['registeredAnonymousId'];
          if ($registeredAnonymousId == 0) {
            $user = User::create();
            $user->enforceIsNew();
            $user->setEmail($form_state->getStorage()['email']);
            $user->setUsername('4294967295');
            $user->block();
            $user->save();
            $user->setUsername($user->id());
            $user->save();
            $person = \Drupal::entityTypeManager()
              ->getStorage('person')
              ->create();
            $person->id = $user->id();
            $person->lastname = $form_state->getStorage()['lastname'];
            $person->firstname = $form_state->getStorage()['firstname'];
            $person->cellphone = $form_state->getStorage()['cellphone'];
            $person->email = $form_state->getStorage()['email'];
            $person->iscontact = 0;
            $person->isactive = 0;
            $person->member_id = NULL;
            $person->user_id = $user->id();
            $person->comment = NULL;
            $person->owner_id = 1;
            $person->created = $person->changed = \Drupal::time()
              ->getRequestTime();
            $person->save();
            $id = $person->id();
          }
          else {
            $id = $registeredAnonymousId;
          }
        }
        else {
          $id = $this->currentUser()->id();
        }
        $this->saveData($form_state, $id);
        $form_state->setStorage([]);
        $this->step = 0;
        break;

      default:
    }

  }

  public function getPersonData(FormStateInterface $form_state, $email) {

    $form_state->set('email', $email);
    $query = \Drupal::database()->select('person', 'pe');
    $query->fields('pe', [
      'id',
      'email',
      'lastname',
      'firstname',
      'cellphone',
      'member_id',
    ])
      ->condition('pe.email', $email, '=');
    $result = $query->execute()->fetchAssoc();
    if ($result) {
      $form_state->set('lastname', $result['lastname']);
      $form_state->set('firstname', $result['firstname']);
      $form_state->set('cellphone', $result['cellphone']);
      $form_state->set('member_id', $result['member_id']);
      $form_state->set('registeredAnonymousId', $result['id']);
    }
    else {
      $form_state->set('lastname', '');
      $form_state->set('firstname', '');
      $form_state->set('cellphone', '');
      $form_state->set('member_id', 0);
      $form_state->set('registeredAnonymousId', 0);
    }

  }

  public function saveData(FormStateInterface $form_state, $id) {

    $currentUserMemberId = $form_state->getStorage()['member_id'];
    $baskets = $form_state->getStorage()['baskets'];
    foreach ($baskets as $key => $value) {
      if ($value !== 0) {
        $basket = \Drupal::entityTypeManager()
          ->getStorage('basket')
          ->load($key);
        $buyers = $basket->buyer->getString();
        if ( $value['seller3']['data']['#markup'] == $currentUserMemberId) {
          $sMessage = $this->t('Are you sure you want to reserve your own basket?');
          $sType = 'warning';
        }
        elseif (in_array($id, explode('+', $buyers))) {
          $sMessage = $this->t('You have already reserved the basket #@tran.', ['@tran' => $key]);
          $sType = 'warning';
        }
        else {
          $aParams = [
            $value['seller2']['data']['#markup'] . ', ' . $form_state->getStorage()['email'] . ', amap@lejardindepoissy.org',
            $form_state->getStorage(),
            $key,
          ];
          $message = [
            'module' => 'amap',
            'key' => 'emailforbasket',
            'to' => 'batch',
            'params' => $aParams,
            'reply' => 'L\'AMAP du Jardin de Poissy',
          ];
          $aResults = shared_send_email($message);
          \Drupal::logger('amap')->info('Reserve basket: Email sent.');

          $buyers .= (($buyers != '') ? '+' : '') . $id;
          $basket->buyer = $buyers;
          $basket->save();
          $sMessage = $this->t('Your reservation request for the basket #@tran has been recorded.<BR>An email has been sent to you and the seller.', ['@tran' => $key]);
          $sType = 'status';
        }
        \Drupal::messenger()->addMessage($sMessage, $sType);
      }
    }

  }

}
