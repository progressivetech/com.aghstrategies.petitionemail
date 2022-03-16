<?php    
namespace Civi\Api4;
     
/**
 * PetitionEmailUtilities
 *
 * Provided by the PetitionEmail extension.
 *
 * @package Civi\Api4
 */
class PetitionEmailUtilities extends Generic\AbstractEntity {

  public static function getFields() {
    return new Generic\BasicGetFieldsAction(__CLASS__, __FUNCTION__, function() {
      return [ ];
    });
  }
}
