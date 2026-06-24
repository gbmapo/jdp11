<?php

declare(strict_types=1);

namespace Drupal\association\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal;
use Drupal\Core\Url;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\user\Entity\User;

/**
 * Provides a Association form.
 */
final class Membership extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'membership';
  }

  /**
   * {@inheritdoc}
   * Step 0
   * Did you read...,  Did you meet..., Are you sure..., Online/Membership form
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    if ($form_state->has('step')) {
      switch ($form_state->get('step')) {
        case 1:
          return $this->step1($form, $form_state);
          break;
        case 2:
          return $this->Step2($form, $form_state);
          break;
        case 3:
          return $this->Step3($form, $form_state);
          break;
        case 4:
          return $this->Step4($form, $form_state);
          break;
        default:
          break;
      }
    }
    $form_state->set('step', 0);

    if ($this->currentUser()->isAnonymous()) {

      $url = Url::fromUri('base:/user/login', ['query' => ['destination' => '/association/membership?mode=online']]);
      $link = \Drupal\Core\Link::fromTextAndUrl(t('here'), $url)->toString();
      $output =
        $this->t('If you are a member, please log in %link.', [
          '%link' => $link,
        ]);
      $form['warning'] = [
        '#markup' => $this->t("This form is only for those who are not member of the association.") . ' ' . $output . '<br><br>',
      ];
      $form['didyouread'] = [
        '#type' => 'radios',
        '#title' => $this->t('Did you read Frequently Asked Questions?'),
        '#options' => [
          0 => $this->t('No'),
          1 => $this->t('Yes'),
        ],
        '#required' => TRUE,
      ];
      $form['didyoumeet'] = [
        '#type' => 'radios',
        '#title' => $this->t('Did you meet someone of the organisation?'),
        '#options' => [
          0 => $this->t('No'),
          1 => $this->t('Yes'),
        ],
        '#required' => TRUE,
      ];
      $form['areyousure'] = [
        '#type' => 'radios',
        '#title' => $this->t('Are you sure about this?'),
        '#options' => [
          0 => $this->t('No'),
          1 => $this->t('Yes'),
        ],
        '#required' => TRUE,
      ];
      $mode = NULL;
      if (isset($_REQUEST["mode"])) {
        if ($_REQUEST["mode"] == 'online') {
          $mode = 0;
        }
      }
      $form['mode'] = [
        '#type' => 'radios',
        '#title' => $this->t('Mode of membership'),
        '#options' => [
          0 => $this->t('Online'),
          1 => $this->t('Membership form'),
        ],
        '#required' => TRUE,
        '#default_value' => $mode,
      ];
      $nextStepNotice = $this->t('enter');
      $markup0 = $this->t('After submitting this form, you will be redirected to the membership process where you can @str your personal information then choose your subscription payment mode.', [
          '@str' => $nextStepNotice,
        ]) . '<br><br>';
      $form['footer0'] = [
        '#type' => 'item',
        '#markup' => $markup0,
        '#states' => [
          'visible' => [
            ':input[name="mode"]' => ['value' => 0],
          ],
        ],
      ];
      $markup1 = $this->t('After submitting this form, you will be redirected to a page where you can download the membership form.') . '<br><br>';
      $form['footer1'] = [
        '#type' => 'item',
        '#markup' => $markup1,
        '#states' => [
          'visible' => [
            ':input[name="mode"]' => ['value' => 1],
          ],
        ],
      ];
      $bNext = TRUE;
    }
    else {
      $storage = Drupal::entityTypeManager()->getStorage('person');
      $person = $storage->load($this->currentUser()->id());
      $member_id = $person->get("member_id")->target_id;
      if ($member_id) {
        $query = Drupal::database()->select('member', 'am');
        $query->leftJoin('person', 'ap', 'ap.member_id = am.id');
        $query->leftJoin('person__field_sel_isseliste', 'ps', 'ap.id = ps.entity_id');
        $query->leftJoin('users_field_data', 'us', 'us.uid = ps.entity_id');
        $query->fields('am', [
          'id',
          'designation',
          'addresssupplement',
          'street',
          'postalcode',
          'city',
          'contact_id',
          'telephone',
          'status',
        ]);
        $query->fields('ap', [
          'id',
          'lastname',
          'firstname',
          'email',
          'cellphone',
          'iscontact',
        ]);
        $query->fields('ps', ['field_sel_isseliste_value',]);
        $query->fields('us', ['uid', 'name']);
        $query->condition('am.id', $member_id, '=');
        $query->orderBy('iscontact', 'DESC');
        $results = $query->execute()->fetchAll();
        $form_state->set('am_id', $results[0]->id);
        $form_state->set('designation', $results[0]->designation);
        $form_state->set('addresssupplement', $results[0]->addresssupplement);
        $form_state->set('street', $results[0]->street);
        $form_state->set('postalcode', $results[0]->postalcode);
        $form_state->set('city', $results[0]->city);
        $form_state->set('telephone', $results[0]->telephone);
        $form_state->set('status', $results[0]->status);
        $form_state->set('ap_id1', $results[0]->ap_id);
        $form_state->set('lastname1', $results[0]->lastname);
        $form_state->set('firstname1', $results[0]->firstname);
        $form_state->set('email1', $results[0]->email);
        $form_state->set('cellphone1', $results[0]->cellphone);
        $form_state->set('seliste1', $results[0]->field_sel_isseliste_value);
        $form_state->set('name1', $results[0]->name);
        if (count($results) > 1) {
          $form_state->set('ap_id2', $results[1]->ap_id);
          $form_state->set('lastname2', $results[1]->lastname);
          $form_state->set('firstname2', $results[1]->firstname);
          $form_state->set('email2', $results[1]->email);
          $form_state->set('cellphone2', $results[1]->cellphone);
          $form_state->set('seliste2', $results[1]->field_sel_isseliste_value);
          $form_state->set('name2', $results[1]->name);
        }
        $iTemp = ($this->currentUser()
            ->getDisplayName() == $results[0]->name) ? 0 : 1;
        $form_state->set('undersigned', $iTemp);
        $form_state->set('lastname', $results[$iTemp]->lastname);
        $form_state->set('firstname', $results[$iTemp]->firstname);
        $form_state->set('email', $results[$iTemp]->email);
      }
      else {
        $form_state->set('status', 4);
      }
      $nextStepNotice = $this->t('correct, if needed,');
      $config = Drupal::config('association.renewalperiod');
      $rpYear = $config->get('year');
      $rpStatus = $config->get('status');

      $bNext = FALSE;
      switch (TRUE) {
        case ($rpStatus == 'Closed'):
          $form['footer'] = [
            '#markup' => $this->t("There is currently no renewal period opened.") . '<br>',
          ];
          break;
        case (!$member_id):
        case (!$this->currentUser()->hasPermission("renew membership")):
          $form['footer'] = [
            '#markup' => $this->t("You're not allowed to renew membership. Only the persons associated to a member are allowed to do it.") . '<br>',
          ];
          break;
        default:
          switch ($form_state->getStorage()['status']) {
            case 1:
              $iWish = 0;
              break;
            case 3:
              $iWish = 1;
              break;
            case 2:
            default:
              $iWish = NULL;
              break;
          }
          $sMember = new FormattableMarkup('<span style="color: #0000ff;">' . $form_state->getStorage()['designation'] . '</span>', []);
          $sPerson = new FormattableMarkup('<span style="color: #0000ff;">' . $form_state->getStorage()['firstname'] . ' ' . $form_state->getStorage()['lastname'] . '</span>', []);
          if ($form_state->getStorage()['status'] == 4) {
            $sTemp = $this->t('The member «&nbsp;%member&nbsp;» has already renewed his membership to the association <I>Le Jardin de Poissy</I> for year « %year ».', [
              '%member' => $sMember,
              '%year' => $rpYear,
            ]);
            $form['header'] = [
              '#type' => 'inline_template',
              '#template' => $sTemp,
            ];
          }
          else {
            $sTemp = $this->t("Here’s your wish as recorded.<br>You can change it as many times as you like: only the last change will be taken into account.<br><br>");
            $sTemp = ($iWish == -1) ? "" : $sTemp;
            $sTemp2 = $this->t('I, the undersigned «&nbsp;%person&nbsp;», representing the member «&nbsp;%member&nbsp;», wishes to renew my membership to the association <I>Le Jardin de Poissy</I> for year « %year ».', [
              '%person' => $sPerson,
              '%member' => $sMember,
              '%year' => $rpYear,
            ]);
            $sTemp = $sTemp . $sTemp2;
            $form['header'] = [
              '#type' => 'inline_template',
              '#template' => $sTemp,
            ];
            $form['suscribe'] = [
              '#type' => 'radios',
              '#title' => '',
              '#options' => [
                0 => $this->t('No'),
                1 => $this->t('Yes'),
              ],
              '#default_value' => $iWish,
              '#validated' => TRUE,
            ];
            $markup = $this->t('After submitting this form, you will be able to @str your personal information then choose your subscription payment mode.', [
                '@str' => $nextStepNotice,
              ]) . '<br>';
            $form['suscribedyes'] = [
              '#type' => 'item',
              '#markup' => $markup,
              '#states' => [
                'visible' => [
                  ':input[name="suscribe"]' => ['value' => 1],
                ],
              ],
            ];
            $bNext = TRUE;
          }
      }
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];
    if ($bNext) {
      $form['actions']['next'] = [
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value' => $this->t('Next'),
        '#validate' => ['::step0Validate'],
        '#submit' => ['::step0Next'],
      ];
    }
    $form['actions']['leave'] = [
      '#type' => 'submit',
      '#name' => 'leave',
      '#value' => $this->t('Leave'),
      '#submit' => ['::leave'],
      '#limit_validation_errors' => [],
      '#weight' => 99,
    ];

    return $form;

  }

  public function leave($form, $form_state) {

    $form_state->setStorage([]);
    $form_state->setRedirectUrl(Url::fromRoute('<front>'));

  }

  public function step0Validate(array &$form, FormStateInterface $form_state) {

    if ($this->currentUser()->isAnonymous()) {
      if ($form_state->getValue('didyouread') == '0') {
        $form_state->setErrorByName('didyouread', $this->t('Some of your questions might be unanswered, maybe you should not apply now.'));
      }
      if ($form_state->getValue('didyoumeet') == '0') {
        $form_state->setErrorByName('didyoumeet', $this->t('Without meeting someone of our organisation, maybe you should not apply now.'));
      }
      if ($form_state->getValue('areyousure') == '0') {
        $form_state->setErrorByName('areyousure', $this->t('If you\'re not sure, maybe you should not apply now.'));
      }
    }
    else {
      if ($form_state->getValue('suscribe') == NULL) {
        $form_state->setErrorByName('suscribe', $this->t('Please choose one option.'));
      }
    }

  }

  public function step0Next(array &$form, FormStateInterface $form_state) {

    if ($this->currentUser()->isAnonymous()) {
      if ($form_state->getValue('mode') == "1") {
        $form_state->setRedirectUrl(Url::fromUri('base:/MembershipWithForm'));
        return;
      }
      else {
      }
    }
    else {
      switch ($form_state->getValue('suscribe')) {
        case 0:
          $form_state->set('status', 1);
          $this->saveData($form_state);
          $form_state->setRedirectUrl(Url::fromRoute('<front>'));
          return;
          break;
        case 1:
          $form_state->set('status', 3);
          break;
        default:
          $form_state->setRedirectUrl(Url::fromRoute('<front>'));
          return;
      }
    }

    $form_state
      ->set('step', $form_state->get('step') + 1)
      ->setRebuild(TRUE);

  }

  /**
   * Step 1
   * Person(s) information
   */
  public function step1(array &$form, FormStateInterface $form_state) {

    $weight = 0;
    $form['person1'] = [
      '#type' => 'fieldset',
      '#title' => t('Person') . ' 1 (' . t('Contact') . ') <a class="use-ajax" data-dialog-options="{&quot;width&quot;:440}" data-dialog-type="modal" href="/node/139" id="modal-dialog"><img src="/sites/default/files/images/info.svg"></a>',
    ];
    $form['person1']['#attached'] = ['library' => ['core/drupal.dialog.ajax']];
    $weight++;
    $temp = isset($form_state->getStorage()['lastname1']) ? $form_state->getStorage()['lastname1'] : '';
    $form['person1']['lastname1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last Name'),
      '#size' => 32,
      '#required' => TRUE,
      '#default_value' => $temp,
      '#weight' => $weight,
      '#attributes' => ['onchange' => 'hasChanged(this)',],
    ];
    $weight++;
    $temp = isset($form_state->getStorage()['firstname1']) ? $form_state->getStorage()['firstname1'] : '';
    $form['person1']['firstname1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First Name'),
      '#size' => 32,
      '#required' => TRUE,
      '#default_value' => $temp,
      '#weight' => $weight,
      '#attributes' => ['onchange' => 'hasChanged(this)',],
    ];
    $weight++;
    $temp = isset($form_state->getStorage()['email1']) ? $form_state->getStorage()['email1'] : '';
    $form['person1']['email1'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $temp,
      '#weight' => $weight,
      '#attributes' => ['onchange' => 'hasChanged(this)',],
    ];
    $weight++;
    $temp = isset($form_state->getStorage()['cellphone1']) ? $form_state->getStorage()['cellphone1'] : '';
    $form['person1']['cellphone1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cellphone'),
      '#size' => 16,
      '#required' => TRUE,
      '#default_value' => $temp,
      '#weight' => $weight,
      '#attributes' => ['onchange' => 'hasChanged(this)',],
    ];
    $weight++;
    $temp = isset($form_state->getStorage()['seliste1']) ? $form_state->getStorage()['seliste1'] : 0;
    $form['person1']['seliste1'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I wish to be SÉListe'),
      '#default_value' => $temp,
      '#weight' => $weight,
    ];
    $weight++;
    $form['person2'] = [
      '#type' => 'fieldset',
      '#title' => t('Person') . ' 2 ',
    ];
    $weight++;
    $temp = isset($form_state->getStorage()['lastname2']) ? $form_state->getStorage()['lastname2'] : '';
    $form['person2']['lastname2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last Name'),
      '#size' => 32,
      '#default_value' => $temp,
      '#weight' => $weight,
      '#attributes' => ['onchange' => 'hasChanged(this)',],
    ];
    $weight++;
    $temp = isset($form_state->getStorage()['firstname2']) ? $form_state->getStorage()['firstname2'] : '';
    $form['person2']['firstname2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First Name'),
      '#size' => 32,
      '#default_value' => $temp,
      '#weight' => $weight,
      '#attributes' => ['onchange' => 'hasChanged(this)',],
    ];
    $weight++;
    $temp = isset($form_state->getStorage()['email2']) ? $form_state->getStorage()['email2'] : '';
    $form['person2']['email2'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#size' => 64,
      '#default_value' => $temp,
      '#weight' => $weight,
      '#attributes' => ['onchange' => 'hasChanged(this)',],
    ];
    $weight++;
    $temp = isset($form_state->getStorage()['cellphone2']) ? $form_state->getStorage()['cellphone2'] : '';
    $form['person2']['cellphone2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cellphone'),
      '#size' => 26,
      '#default_value' => $temp,
      '#weight' => $weight,
      '#attributes' => ['onchange' => 'hasChanged(this)',],
    ];
    $weight++;
    $temp = isset($form_state->getStorage()['seliste2']) ? $form_state->getStorage()['seliste2'] : 0;
    $form['person2']['seliste2'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I wish to be SÉListe'),
      '#default_value' => $temp,
      '#weight' => $weight,
      '#states' => [
        'visible' => [
          ':input[name="lastname2"]' => ['filled' => TRUE],
        ],
      ],
    ];
    $weight++;
    $temp = isset($form_state->getStorage()['newcontact']) ? $form_state->getStorage()['newcontact'] : 0;
    $form['person2']['newcontact'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I wish Person 2 to be contact for member'),
      '#default_value' => $temp,
      '#weight' => $weight,
      '#states' => [
        'visible' => [
          ':input[name="lastname2"]' => ['filled' => TRUE],
        ],
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['previous'] = [
      '#type' => 'submit',
      '#value' => $this->t('Previous'),
      '#limit_validation_errors' => [],
      '#submit' => ['::step1Previous'],
    ];
    $form['actions']['next'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Next'),
      '#validate' => ['::step1Validate'],
      '#submit' => ['::step1Next'],
    ];
    $form['actions']['leave'] = [
      '#type' => 'submit',
      '#name' => 'leave',
      '#value' => $this->t('Leave'),
      '#submit' => ['::leave'],
      '#limit_validation_errors' => [],
      '#weight' => 99,
    ];

    $form['#attached']['library'][] = 'association/membership';

    return $form;
  }

  public function step1Validate(array &$form, FormStateInterface $form_state) {

    $values = $form_state->cleanValues()->getValues();
    $person2 = array_intersect_key($values, array_flip([
      'lastname2',
      'firstname2',
      'email2',
      'cellphone2',
    ]));
    if (implode("", $person2)) {
      $message = $this->t('If one of the fields Lastname, Firstname, Email, Cellphone is filled, the others must be filled too.');
      if (!$form_state->getValue('lastname2') || !$form_state->getValue('firstname2') || !$form_state->getValue('email2') || !$form_state->getValue('cellphone2')) {
        $form_state->setErrorByName('lastname2', $message);
      }
    }
    $email1 = $form_state->getValue('email1');
    if ($this->currentUser()->isAnonymous()) {
      $temp = _existsEmail($email1, $_SERVER["REQUEST_URI"]);
      if ($temp) {
        if ($temp[1] >= 4) {
          $form_state->setErrorByName('email1', $temp[0]);
        }
      }
    }
    $email2 = $form_state->getValue('email2');
    if ($email2) {
      if ($email2 == $email1) {
        $message = $this->t('The two persons can\'t have the same email address.');
        $form_state->setErrorByName('email2', $message);
      }
      else {
        if ($this->currentUser()
            ->isAnonymous() || (is_null($form_state->getStorage()['ap_id2']))) {
          $temp = _existsEmail($email2, $_SERVER["REQUEST_URI"]);
          if ($temp) {
            if ($temp[1] >= 4) {
              $form_state->setErrorByName('email2', $temp[0]);
            }
          }
        }
      }
    }
    $cellphone2 = $form_state->getValue('cellphone2');
    if ($cellphone2) {
      if ($cellphone2 == $form_state->getValue('cellphone1')) {
        $message = $this->t('The two persons can\'t have the same cellphone number.');
        $form_state->setErrorByName('cellphone2', $message);
      }
    }

  }

  public function step1Previous(array &$form, FormStateInterface $form_state) {

    $form_state
      ->set('step', $form_state->get('step') - 1)
      ->setRebuild(TRUE);

  }

  public function step1Next(array &$form, FormStateInterface $form_state) {

    $form_state
      ->setStorage(array_merge($form_state->getStorage(), $form_state->cleanValues()
        ->getValues()))
      ->set('step', $form_state->get('step') + 1)
      ->setRebuild(TRUE);

  }

  /**
   * Step 2
   * Member information
   */
  public function step2(array &$form, FormStateInterface $form_state) {

    $weight = 0;
    $weight++;
    $temp = $form_state->getStorage()['lastname1'] . ' ' . $form_state->getStorage()['firstname1'];
    if ($form_state->getStorage()['lastname2'] != "") {
      if ($form_state->getStorage()['lastname2'] == $form_state->getStorage()['lastname1']) {
        $temp .= t(' and ') . $form_state->getStorage()['firstname2'];
      }
      else {
        $temp .= t(' and ') . $form_state->getStorage()['lastname2'] . ' ' . $form_state->getStorage()['firstname2'];
      }
    }
    $form['designation'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Designation'),
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $temp,
      '#weight' => $weight,
    ];
    $form['address'] = [
      '#type' => 'fieldset',
      '#title' => t('Address'),
      '#weight' => $weight,
    ];
    $weight++;
    $temp = isset($form_state->getStorage()['addresssupplement']) ? $form_state->getStorage()['addresssupplement'] : '';
    $form['address']['addresssupplement'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Address supplement'),
      '#size' => 64,
      '#default_value' => $temp,
      '#placeholder' => 'Batiment B',
      '#weight' => $weight,
      '#attributes' => ['onchange' => 'hasChanged(this)',],
    ];
    $weight++;
    $temp = isset($form_state->getStorage()['street']) ? $form_state->getStorage()['street'] : '';
    $form['address']['street'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Street'),
      '#size' => 64,
      '#required' => TRUE,
      '#placeholder' => '28 bis boulevard Victor Hugo',
      '#default_value' => $temp,
      '#weight' => $weight,
      '#attributes' => ['onchange' => 'hasChanged(this)',],
    ];
    $weight++;
    $temp = isset($form_state->getStorage()['postalcode']) ? $form_state->getStorage()['postalcode'] : '';
    $form['address']['postalcode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Postal Code'),
      '#size' => 10,
      '#required' => TRUE,
      '#placeholder' => '78300',
      '#default_value' => $temp,
      '#weight' => $weight,
    ];
    $weight++;
    $temp = isset($form_state->getStorage()['city']) ? $form_state->getStorage()['city'] : '';
    $form['address']['city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City'),
      '#size' => 64,
      '#required' => TRUE,
      '#placeholder' => 'Poissy',
      '#default_value' => $temp,
      '#weight' => $weight,
      '#attributes' => ['onchange' => 'hasChanged(this)',],
    ];
    $weight++;
    $temp = isset($form_state->getStorage()['telephone']) ? $form_state->getStorage()['telephone'] : '';
    $form['telephone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Landline Phone'),
      '#size' => 16,
      '#default_value' => $temp,
      '#weight' => $weight,
      '#attributes' => ['onchange' => 'hasChanged(this)',],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['previous'] = [
      '#type' => 'submit',
      '#value' => $this->t('Previous'),
      '#limit_validation_errors' => [],
      '#submit' => ['::step2Previous'],
    ];
    $form['actions']['next'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Next'),
      '#validate' => ['::step2Validate'],
      '#submit' => ['::step2Next'],
    ];
    $form['actions']['leave'] = [
      '#type' => 'submit',
      '#name' => 'leave',
      '#value' => $this->t('Leave'),
      '#submit' => ['::leave'],
      '#limit_validation_errors' => [],
      '#weight' => 99,
    ];

    return $form;
  }

  public function step2Validate(array &$form, FormStateInterface $form_state) {
  }

  public function step2Previous(array &$form, FormStateInterface $form_state) {

    $form_state
      ->set('step', $form_state->get('step') - 1)
      ->setRebuild(TRUE);

  }

  public function step2Next(array &$form, FormStateInterface $form_state) {

    $form_state
      ->setStorage(array_merge($form_state->getStorage(), $form_state->cleanValues()
        ->getValues()))
      ->set('step', $form_state->get('step') + 1)
      ->setRebuild(TRUE);

  }

  /**
   * Step 3
   * Summary for confirmation
   */
  public function step3(array &$form, FormStateInterface $form_state) {

    $i1 = $form_state->get('undersigned') + 1;
    $markup = 'Je, soussigné(e), <span style="color: #0000ff;">' . implode("", [
        $form_state->getStorage()['lastname' . $i1],
        ' ',
        $form_state->getStorage()['firstname' . $i1],
        ' (',
        $form_state->getStorage()['cellphone' . $i1],
        ' - ',
        $form_state->getStorage()['email' . $i1],
        ')',
      ]) . '</span>, déclare vouloir adhérer à l’association « <b>Le Jardin de Poissy</b> » sous la désignation suivante : <span style="color: #0000ff;">' . $form_state->getStorage()['designation'] . '</span>.';
    if ($form_state->getStorage()['lastname2']) {
      $i2 = ($i1 % 2) + 1;
      $markup .= '<br>Je souhaite qu’une autre personne me soit associée : <span style="color: #0000ff;">' . implode("", [
          $form_state->getStorage()['lastname' . $i2],
          ' ',
          $form_state->getStorage()['firstname' . $i2],
          ' (',
          $form_state->getStorage()['cellphone' . $i2],
          ' - ',
          $form_state->getStorage()['email' . $i2],
          ')',
        ]) . '</span>.';
    }
    $markup .= '<br><br>Je communique mes coordonnées (adresse et téléphone) :';
    $markup .= '<br><span style="color: #0000ff;">';
    $markup .= implode("", [
      $form_state->getStorage()['addresssupplement'] ? $form_state->getStorage()['addresssupplement'] . '<br>' : '',
      $form_state->getStorage()['street'] . '<br>',
      $form_state->getStorage()['postalcode'] . ' ' . $form_state->getStorage()['city'] . '<br>',
      $form_state->getStorage()['telephone'] ? $form_state->getStorage()['telephone'] : '',
    ]);
    $markup .= '</span>';
    $markup = new FormattableMarkup($markup, []);
    $form['markup1'] = [
      '#type' => 'item',
      '#markup' => $markup,
    ];

    if ($form_state->getStorage()['lastname2']) {
      $options = [
        'sel1' => $form_state->getStorage()['lastname1'] . ' ' . $form_state->getStorage()['firstname1'],
        'sel2' => $form_state->getStorage()['lastname2'] . ' ' . $form_state->getStorage()['firstname2'],
      ];
    }
    else {
      $options = [
        'sel1' => $form_state->getStorage()['lastname1'] . ' ' . $form_state->getStorage()['firstname1'],
      ];
    }
    $form['sel'] = [
      '#type' => 'checkboxes',
      '#prefix' => '<br>',
      '#title' => $this->t('Souhaite(nt) faire partie du Grenier à SÉL :'),
      '#options' => $options,
      '#default_value' => [
        $form_state->getStorage()['seliste1'] ? 'sel1' : 0,
        $form_state->getStorage()['seliste2'] ? 'sel2' : 0,
      ],
      '#disabled' => TRUE,
    ];

    $form['commitment1'] = [
      '#type' => 'checkbox',
      '#prefix' => '<br>',
      '#title' => $this->t('Je m’engage à respecter les statuts disponibles sur le site de l’association.'),
    ];

    $form['authorisation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('J’autorise l’association à utiliser mes informations personnelles à des fins de communication interne.'),
    ];
    $form['commitment2'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Je m’engage à ne pas utiliser les informations personnelles des autres adhérents à des fins privées, notamment commerciales, ou dans un but qui serait contraire aux statuts de l’association.'),
    ];

    if ($this->currentUser()->isAnonymous()) {

      $form['amap'] = [
        '#type' => 'checkbox',
        '#prefix' => '<br>',
        '#title' => $this->t('Je souhaite faire partie de l’AMAP'),
      ];
      $form['amap-baskets'] = [
        '#type' => 'checkboxes',
        '#attributes' => [
          'style' => 'margin-left: 20px;',
        ],
        '#title' => $this->t('- j’accepte l’éventualité d’être sur liste d’attente. <br>- je suis intéressé.e par les paniers suivants (ceci est purement informatif et ne m’engage en rien) :'),
        '#options' => [
          'amap_legumes' => 'Légumes',
          'amap_fruits' => 'Fruits',
          'amap_pain' => 'Pain',
          'amap_viandedebœuf' => 'Viande de bœuf',
          'amap_œufs' => 'Œufs',
          'amap_volaille' => 'Volaille',
          'amap_champignons' => 'Champignons',
          'amap_lentilles' => 'Lentilles',
          'amap_viandedeporc' => 'Viande de porc',
          'amap_produitslaitiersvache' => 'Produits laitiers vache',
          'amap_pommesdeterre' => 'Pommes de terre',
          'amap_produitslaitiersbrebis' => 'Produits laitiers brebis',
          'amap_farine' => 'Farine',
          'amap_miel' => 'Miel',
          'amap_jusdepomme' => 'Jus de pomme',
          'amap_cidre' => 'Cidre',
          'amap_herbier' => 'Herbier',
          'amap_noix' => 'Noix',
        ],
        '#states' => [
          'visible' => [
            ':input[name="amap"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $form['commitment3'] = [
        '#type' => 'checkboxes',
        '#attributes' => [
          'style' => 'margin-left: 20px;',
        ],
        '#title' => $this->t('- Si je souscris à au moins un panier (quel que soit le contrat),'),
        '#options' => [
          'dist' => 'je m’engage à participer aux distributions au moins deux fois par an',
          'work' => 'je m’engage à assister aux ateliers pédagogiques sur les lieux de production en fonction des besoins',
        ],
        '#states' => [
          'visible' => [
            ':input[name="amap"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $form['how'] = [
        '#type' => 'checkboxes',
        '#prefix' => '<br>',
        '#title' => $this->t('J\'ai connu <b>Le Jardin de Poissy</b> par :'),
        '#options' => [
          'how_boucheaoreille' => 'bouche à oreille',
          'how_moteurderechercheinternet' => 'moteur de recherche internet',
          'how_pagefacebook' => 'page facebook',
          'how_pageinstagram' => 'page instagram',
          'how_autre' => 'autre...',
        ],
      ];
      $form['how_autre'] = [
        '#type' => 'textfield',
        '#size' => 64,
        '#states' => [
          'visible' => [
            ':input[name="how[how_autre]"]' => ['checked' => TRUE],
          ],
        ],
      ];

    }

    $markup = '<br>L’adhésion à l’association est formalisée par une cotisation ayant pour objet de couvrir les frais de
    fonctionnement. Son montant pour une année civile (du 1er janvier au 31 décembre) est de 24&nbsp;€. Il est
    dégressif, à raison de 2&nbsp;€ par mois écoulé depuis le 1er janvier :
    <table border="1" style="width: 600px; margin-left:auto; margin-right:auto;">
      <thead>
      <tr>
        <th colspan="6" scope="col" style="text-align: center;">Date d’inscription -&gt; Cotisation jusqu’au 31 décembre</th>
      </tr>
      </thead>
      <tbody>
      <tr style="text-align: center;">
        <td>Jan : 24 €</td>
        <td>Fév : 22 €</td>
        <td>Mar : 20 €</td>
        <td>Avr : 18 €</td>
        <td>Mai : 16 €</td>
        <td>Juin : 14 €</td>
      </tr>
      <tr style="text-align: center;">
        <td>Juil : 12 €</td>
        <td>Aoû : 10 €</td>
        <td>Sep : 8 €</td>
        <td>Oct : 6 €</td>
        <td>Nov : 4 €</td>
        <td>Déc : 2 €</td>
      </tr>
      </tbody>
    </table> Pour faire face aux difficultés qui impactent notre équilibre financier, nous proposons aux adhérents qui le souhaitent d\'ajouter à cette cotisation (dont le montant est inchangé depuis 2017) un don d\'une valeur laissée à leur appréciation.
    <br><br>';
    $markup = new FormattableMarkup($markup, []);
    $form['markup2'] = [
      '#type' => 'item',
      '#markup' => $markup,
    ];

    $form['payment'] = [
      '#type' => 'radios',
      '#title' => $this->t('Subscription Payment Method'),
      '#options' => [
        0 => $this->t('by check'),
        1 => $this->t('by bank transfer'),
        2 => $this->t('by card'),
      ],
    ];

    $markup = '<br>Votre chèque doit être envoyé à :<br>Le Jardin de Poissy<br>28 bis boulevard Victor Hugo<br>78300 Poissy<br>Ne pas oublier d’indiquer votre désignation d’adhérent (<span style="color: #0000ff;">' . $form_state->getStorage()['designation'] . '</span>) au verso.<br><br>';
    $markup = new FormattableMarkup($markup, []);
    $form['payment0'] = [
      '#type' => 'item',
      '#markup' => $markup,
      '#states' => [
        'visible' => [
          ':input[name="payment"]' => ['value' => 0],
        ],
      ],
    ];
    $markup = '<br>Compte à utiliser pour votre virement : <br><table style="width: auto;"><tr><td>Titulaire</td><td>Le Jardin de Poissy</td></tr><tr><td>RIB</td><td>20041 01012 6701481J033 71</td></tr><tr><td>IBAN</td><td>FR19 2004 1010 1267 0148 1J03 371</td></tr><tr><td>BIC</td><td>PSSTFRPPSCE</td></tr></table>Ne pas oublier d’indiquer votre désignation d’adhérent (<span style="color: #0000ff;">' . $form_state->getStorage()['designation'] . '</span>) dans le commentaire destiné au bénéficiaire.<br><br>';
    $markup = new FormattableMarkup($markup, []);
    $form['payment1'] = [
      '#type' => 'item',
      '#markup' => $markup,
      '#states' => [
        'visible' => [
          ':input[name="payment"]' => ['value' => 1],
        ],
      ],
    ];
    $markup = '<br>' . $this->t('After submitting this form, you will be redirected to the online payment process.') . '<br><br>';
    $markup = new FormattableMarkup($markup, []);
    $form['payment2'] = [
      '#type' => 'item',
      '#markup' => $markup,
      '#states' => [
        'visible' => [
          ':input[name="payment"]' => ['value' => 2],
        ],
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['previous'] = [
      '#type' => 'submit',
      '#value' => $this->t('Previous'),
      '#limit_validation_errors' => [],
      '#submit' => ['::step3Previous'],
    ];
    $form['actions']['next'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Submit'),
      '#validate' => ['::step3Validate'],
      '#submit' => ['::step3Next'],
    ];
    $form['actions']['leave'] = [
      '#type' => 'submit',
      '#name' => 'leave',
      '#value' => $this->t('Leave'),
      '#submit' => ['::leave'],
      '#limit_validation_errors' => [],
      '#weight' => 99,
    ];

    return $form;
  }

  public function step3Validate(array &$form, FormStateInterface $form_state) {

    if ($form_state->getValue('commitment1') == '0') {
      $form_state->setErrorByName('commitment1', $this->t('You must commit to observe the statuses of the association.'));
    }
    if ($form_state->getValue('authorisation') == '0') {
      $form_state->setErrorByName('authorisation', $this->t('You must authorise the association to use your personal information.'));
    }
    if ($form_state->getValue('commitment2') == '0') {
      $form_state->setErrorByName('commitment2', $this->t('You must commit not to use the information of other members for personal purposes.'));
    }
    if ($form_state->getValue('amap') == '1') {
      if ($form_state->getValue('commitment3')['dist'] == 0 || $form_state->getValue('commitment3')['work'] == 0) {
        $form_state->setErrorByName('commitment3', $this->t('You must commit to distributions and educational workshops.'));
      }
    }
    if ($this->currentUser()->isAnonymous()) {
      $zeros = TRUE;
      foreach ($form_state->getValue('how') as $value) {
        if ($value != '0') {
          $zeros = FALSE;
          break;
        }
      }
      if ($zeros) {
        $form_state->setErrorByName('how', $this->t('Please, tell us how you knew <b>Le Jardin de Poissy</b>.'));
      }
      else {
        if ($form_state->getValue('how')['how_autre'] == 'how_autre') {
          if ($form_state->getValue('how_autre') == '') {
            $form_state->setErrorByName('how_autre', $this->t('You must tell us by what other mean you knew <b>Le Jardin de Poissy</b>.'));
          }
        }
      }
    }
    if (is_null($form_state->getValue('payment'))) {
      $form_state->setErrorByName('payment', $this->t('You must choose a subscription payment method.'));
    }

  }

  public function step3Previous(array &$form, FormStateInterface $form_state) {

    $form_state
      ->set('step', $form_state->get('step') - 1)
      ->setRebuild(TRUE);

  }

  public function step3Next(array &$form, FormStateInterface $form_state) {

    $aCleanValues = $form_state->cleanValues()->getValues();

    if (array_key_exists('amap', $aCleanValues)) {
      if ($aCleanValues['amap'] == 1) {
        $contracts = '';
        foreach ($aCleanValues['amap-baskets'] as $key => $value) {
          if ($value != 0) {
            $contracts .= substr($key, 5, 999) . ', ';
          }
        }
        $contracts = substr($contracts, 0, strlen($contracts) - 2);
        $contracts = 'AMAP : ' . (($contracts) ? $contracts . '.' : '.');
        $aCleanValues['contracts'] = $contracts;
      }
    }

    if (array_key_exists('how', $aCleanValues)) {
      $how = '';
      foreach ($aCleanValues['how'] as $key => $value) {
        if ($value != 0) {
          $how .= substr($key, 4, 999) . ', ';
          if ($value == 'how_autre') {
            $how = substr($how, 0, strlen($how) - 2);
            $how .= '(' . $aCleanValues['how_autre'] . '), ';
          }
        }
      }
      $how = substr($how, 0, strlen($how) - 2);
      $how = ($how) ? 'Connu par : ' . $how . '.' : '';
      $aCleanValues['how'] = $how;
    }

    $payment = (string) $form['payment']['#title'] . ' : ' . (string) $form['payment']['#options'][$form_state->getValue('payment')] . '.';
    $aCleanValues['payment'] = $payment;
    $form_state->setStorage(array_merge($form_state->getStorage(), $aCleanValues));

    $this->saveData($form_state);

    if ($form_state->getValue('payment') == '2') {

      $form_state
        ->setStorage(array_merge($form_state->getStorage(), $form_state->cleanValues()
          ->getValues()))
        ->set('step', $form_state->get('step') + 1)
        ->setRebuild(TRUE);

    }

    else {

      $form_state->setStorage([]);
      $form_state->setRedirectUrl(Url::fromRoute('<front>'));

    }

  }

  /**
   * Step 4
   * Payment with HelloAsso
   */
  public function step4(array &$form, FormStateInterface $form_state) {

    $i1 = $form_state->get('undersigned') + 1;
    $markup = new FormattableMarkup('<B>À utiliser dans les étapes « Adhérents » et « Coordonnées » :</B>
    <br>&nbsp;&nbsp;Prénom : <span style="color: #0000ff;">' . $form_state->getStorage()['firstname' . $i1] . '</span>
    <br>&nbsp;&nbsp;Nom : <span style="color: #0000ff;">' . $form_state->getStorage()['lastname' . $i1] . '</span>
    <br>&nbsp;&nbsp;Désignation : <span style="color: #0000ff;">' . $form_state->getStorage()['designation'] . '</span>
    <br>&nbsp;&nbsp;Adresse email : <span style="color: #0000ff;">' . $form_state->getStorage()['email' . $i1] . '</span>
    <br><br>', []);

    $form['header'] = [
      '#type' => 'item',
      '#markup' => $markup,
    ];

    $config = \Drupal::service('config.factory')
      ->getEditable('association.renewalperiod');
    $rpYear = $config->get('year');
    $thisMonth = $this->currentUser()->isAnonymous() ? date('Y-m') : $rpYear;
    /*
        $form['hello'] = [
          '#type' => 'inline_template',
          '#template' => '<iframe id="haWidget" allowtransparency="true" src="https://www.helloasso.com/associations/le-jardin-de-poissy/adhesions/adhesion-' . $thisMonth . '/widget-bouton" style="width: 100%; height: 70px; border: none;" onload="window.addEventListener( \'message\', function(e) { const dataHeight = e.data.height; const haWidgetElement = document.getElementById(\'haWidget\'); haWidgetElement.height = dataHeight + \'px\'; } )" ></iframe>',
        ];
        $markup = new FormattableMarkup('<p style="width: 100%;text-align: center">
            Cliquer sur le bouton « Adhérer » pour passer au paiement avec HelloAsso dans une autre fenêtre
        </p>', []);
        $form['footer0'] = [
          '#type' => 'item',
          '#markup' => $markup,
        ];
     */

    $form['hello'] = [
      '#type' => 'inline_template',
      '#template' => '<iframe id="haWidget" allowtransparency="true" src="https://www.helloasso.com/associations/le-jardin-de-poissy/adhesions/adhesion-' . $thisMonth . '/widget-bouton" style="width: 100%; height: 70px; border: none;" onload="window.addEventListener( \'message\', function(e) { const dataHeight = e.data.height; const haWidgetElement = document.getElementById(\'haWidget\'); haWidgetElement.height = dataHeight + \'px\'; } )" ></iframe>
      <p style="width: 100%; text-align: center">
      Cliquer sur le bouton « Adhérer » pour passer au paiement avec HelloAsso dans une autre fenêtre
      </p><br>',
    ];

    $markup = new FormattableMarkup('Nota : Vous pouvez trouvez les autres modes de paiement de la cotisation dans la Foire aux Questions.', []);
    $form['footer'] = [
      '#type' => 'item',
      '#markup' => $markup,
    ];

    $form_state->setStorage([]);

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['leave'] = [
      '#type' => 'submit',
      '#name' => 'leave',
      '#value' => $this->t('Leave'),
      '#submit' => ['::leave'],
      '#limit_validation_errors' => [],
      '#weight' => 99,
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
  }

  public function saveData(FormStateInterface $form_state) {

    if ($form_state->get('step') == 0) {

      $now = date('Y-m-d');
      Drupal::database()->update('member')
        ->condition('id', $form_state->getStorage()['am_id'])
        ->fields([
          'status' => $form_state->getStorage()['status'],
          'enddate' => $now,
        ])
        ->execute();

    }
    else {

      $numberOfPersons = $form_state->getStorage()['lastname2'] ? 2 : 1;
      $now = Drupal::time()->getRequestTime();
      $anon = $this->currentUser()->isAnonymous();

      /**
       * User(s) --------------------------------------------------------------
       */
      $uid = [];
      for ($i = 1; $i <= $numberOfPersons; $i++) {
        if ($anon || ($i == 2 && is_null($form_state->getStorage()['ap_id2']))) {
          $user = User::create();
          $user->setPassword('passwordtobechanged');
          $user->enforceIsNew();
          $user->setEmail($form_state->getStorage()['email' . $i]);
          $user->setUsername($this->_generateName($form_state->getStorage()['firstname' . $i], $form_state->getStorage()['lastname' . $i]));
          $user->block();
          if ($i == 1) {
            $user->addRole('contact_for_member');
          }
          if ($form_state->getStorage()['seliste' . $i] == 1) {
            $user->addRole('seliste');
          }
          $user->save();
          $uid[$i] = $user->id();
          $mail = _user_mail_notify('register_pending_approval', $user);
        }
        else {
          $uid[$i] = $form_state->getStorage()['ap_id' . $i];
        }
      }

      /**
       * Member ---------------------------------------------------------------
       */
      $storage = Drupal::entityTypeManager()
        ->getStorage('member');

      if ($anon) {
        $member = $storage->create();
        $contracts = isset($form_state->getStorage()['contracts']) ? $form_state->getStorage()['contracts'] . ' ' : '';
        $comment = $contracts . ($form_state->getStorage()['how'] ? $form_state->getStorage()['how'] . ' ' : '') . $form_state->getStorage()['payment'];
        $member->comment = $comment;
        $member->contact_id = $uid['1'];
        $member->created = $now;
        $member->enddate = '2037-12-31';
        $member->owner_id = $uid['1'];
        $member->startdate = date('Y-m-d');
        $form_state->set('status', 5);
      }
      else {
        $idM = $form_state->getStorage()['am_id'];
        $member = $storage->load($idM);
        $member->comment = $form_state->getStorage()['payment'];
      }
      $member->addresssupplement = $form_state->getStorage()['addresssupplement'];
      $member->changed = $now;
      $member->city = $form_state->getStorage()['city'];
      $member->country = 'FR';
      $member->designation = $form_state->getStorage()['designation'];
      $member->postalcode = $form_state->getStorage()['postalcode'];
      $member->street = $form_state->getStorage()['street'];
      $member->telephone = $form_state->getStorage()['telephone'];
      $member->status = $form_state->getStorage()['status'];
      $member->enddate = '2037-12-31';
      $member->save();
      if ($anon) {
        $idM = $member->id();
      }

      /**
       * Person(s) ------------------------------------------------------------
       */
      $storage = Drupal::entityTypeManager()
        ->getStorage('person');

      $idP = [];
      for ($i = 1; $i <= $numberOfPersons; $i++) {
        if ($anon || ($i == 2 && is_null($form_state->getStorage()['ap_id2']))) {
          switch ($i) {
            case 1:
              $person1 = $storage->create();
              $person1->id = $uid[1];
              $person = $person1;
              break;
            case 2:
              $person2 = $storage->create();
              $person2->id = $uid[2];
              $person = $person2;
              break;
            default:
              break;
          }
          $person->comment = NULL;
          $person->created = $now;
          $person->isactive = 0;
          $person->iscontact = $i == 1 ? 1 : 0;
          $person->member_id = $idM;
          $person->owner_id = $uid['1'];
          $person->user_id = $uid[$i];
        }
        else {
          $idP[$i] = $form_state->getStorage()['ap_id' . $i];
          switch ($i) {
            case 1:
              $person1 = $storage->load($idP[1]);
              $person = $person1;
              break;
            case 2:
              $person2 = $storage->load($idP[2]);
              $person = $person2;
              break;
            default:
              break;
          }
        }

        $person->cellphone = $form_state->getStorage()['cellphone' . $i];
        $person->changed = $now;
        $person->email = $form_state->getStorage()['email' . $i];
        $person->firstname = $form_state->getStorage()['firstname' . $i];
        $person->lastname = $form_state->getStorage()['lastname' . $i];
        $person->field_sel_isseliste = $form_state->getStorage()['seliste' . $i];
        if ($form_state->getStorage()['seliste' . $i] == 1) {
          $person->field_sel_balance = 180;
        }
        $person->save();

        $user = User::load($uid[$i]);
        $user->setEmail($form_state->getStorage()['email' . $i]);
        if ($form_state->getStorage()['seliste' . $i] == 1) {
          $user->addRole('seliste');
        }
        else {
          $user->removeRole('seliste');
        }
        $user->save();
      }

      if (!$anon) {
        if ($form_state->getStorage()['newcontact']) {
          $entity = Drupal::entityTypeManager()
            ->getStorage('person')
            ->load($uid[2]);
          _updatePersonToContact($entity);
        }
      }
    }

    /**
     * Completion message -----------------------------------------------------
     */
    if ($this->currentUser()->isAnonymous()) {
      $str = $this->t('membership request');
      $sMessage = $this->t('Your @str has been registered.<br>It will be effective after reception of your payment.', ['@str' => $str]);
      Drupal::messenger()->addMessage($sMessage);
      $sMessage = $this->t('A confimation email has been sent.');
      Drupal::messenger()->addMessage($sMessage);
    }
    else {
      switch ($form_state->getStorage()['status']) {
        case 1:
          $sMessage = $this->t('Your wish has been recorded.');
          break;
        case 3:
          $str = $this->t('membership renewal request');
          $sMessage = $this->t('Your @str has been registered.<br>It will be effective after reception of your payment.', ['@str' => $str]);
          break;
        default:
          $sMessage = $this->t('Renew membership: Unexpected case.');
      }
      Drupal::messenger()->addMessage($sMessage);
    }

  }

  /**
   * Generate Username for new user preventing duplicates
   */
  public function _generateName($firstname, $lastname) {

    $query = Drupal::database()->select('users_field_data', 'us');
    $query->fields('us', ['name']);
    $query->condition('name', $firstname . '%', 'like');
    $results = $query->execute()->fetchCol();
    for ($i = 0; $i <= strlen($lastname); $i++) {
      $temp = $firstname . strtoupper(substr($lastname, 0, $i));
      $key = array_search($temp, $results);
      if ($key === FALSE) {
        break;
      }
    }
    return $temp;

  }

}
