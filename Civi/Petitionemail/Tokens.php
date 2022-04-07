<?php

namespace Civi\Petitionemail;

use CRM_Petitionemail_ExtensionUtil as E;

class Tokens {

  /** @var string */
  private static $entity = 'petitionemail';

  public static function register(\Civi\Token\Event\TokenRegisterEvent $event) {
    $event->entity(self::$entity)
      ->register('senderIdentificationBlock', E::ts('Sender Identification Block'))
      ->register('message', E::ts('Message'))
      ->register('subject', E::ts('Subject'));
  }

  public static function evaluate(\Civi\Token\Event\TokenValueEvent $event) {
    foreach ($event->getRows() as $row) {
      if (empty(\Civi::$statics['petitionemail']['tokens'])) {
        continue;
      }

      /** @var \Civi\Token\TokenRow $row */
      $row->tokens(self::$entity, 'senderIdentificationBlock', \Civi::$statics['petitionemail']['tokens']['senderIdentificationBlock'] ?? '');
      $row->tokens(self::$entity, 'message', \Civi::$statics['petitionemail']['tokens']['message'] ?? '');
      $row->tokens(self::$entity, 'subject', \Civi::$statics['petitionemail']['tokens']['subject'] ?? '');
    }
  }

}
