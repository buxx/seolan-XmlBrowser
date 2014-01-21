<?php

/**
 * @author Bastien Sevajol
 * @version 20140106001
 * 
 * Travaille sur un browser pour le convertir en un tableau de données de cette 
 * forme: 
 * 
 * array(
 *   0 => array('name' => ObjetCharField, 'age' => ObjetCharField),
 *   1 => ...
 * )
 */
class Browser
{
  
  protected $default_language;
  
  public function __construct($default_language = Null)
  {
    $this->default_language = $default_language;
    if (!$this->default_language)
      $this->default_language = XShell::getLangData();
  }
  
  public function getDefaultLanguage()
  {
    return $this->default_language;
  }
  
  /**
   * @param int $module_id
   * @param array $parameters
   * @param mixed $lang_data, recoit un tableau contenant les langues voulues ou une chaine all
   * @return array
   * 
   * Retourne le browse
   */
  protected function getBrowse($module_id, $parameters, $lang_data='all')
  {
    if(!isSet($parameters['pagesize'])){
      $parameters['pagesize']=9999999999;
    }
    
    $module = XModule::objectFactory($module_id);
   
    if(!is_array($lang_data)) $lang_array = $GLOBALS['TZR_LANGUAGES'];
    else{
      $lang_array = array();
      foreach($GLOBALS['TZR_LANGUAGES'] as $klang=>$lang){
        if(in_array($lang,$lang_data)) $lang_array[$klang]=$lang; 
      }
    }
      foreach($lang_array as $klang=>$lang){
        
        $_REQUEST['LANG_DATA']=$klang;
        $_REQUEST['LANG_USER']=$klang;
        $lang=XShell::getLangData($klang,true);
        $lang=XShell::getLangData(NULL, true);
        
        $res[$lang]=$module->browse(array_merge($parameters, array(
          'tplentry' => TZR_RETURN_DATA,
          '_local' => True,
        )));
      }
      
      return $res;
  }
  
  /**
   * 
   * @param array $browse Le browse issue du module source de donnée
   * @param boolean $get_oid Ajoute ou non le oid dans le tableau de retour
   * @param string $field_attribute Si précisé: Place comme valeur du champs 
   * l'attribut de l'objet au lieu de l'objet représentant le champ
   * @param mixed $lang_data, recoit un tableau contenant les langues voulues ou une chaine all
   * @return array
   */
  protected function getFormatedData($browse, $get_oid = True, $field_attribute = Null, $lang_data='all')
  {

    if(!is_array($lang_data)) 
      $lang_data = $GLOBALS['TZR_LANGUAGES'];
    else{
      $lang_data = array();
      foreach($GLOBALS['TZR_LANGUAGES'] as $klang=>$lang){
        if(in_array($lang,$lang_data)) 
          $lang_data[$klang]=$lang;
      }
    }
    
    $data = array();
    if ($get_oid)
      $data = $this->updateBrowsedDataWithOid ($data, $browse, $lang_data);
    
    foreach ($lang_data as $klang=>$lang){

      foreach ($browse[$klang]['header_fields'] as $header_field)
        {
          
          foreach ($browse[$klang]['lines_o'.$header_field->field] as $key => $field)
          {
            
            if($header_field->translatable != 0)
            {
              $data_lang = $klang;
            }
            else 
            {
              $data_lang = 0;
            }
            
            $data = $this->updateBrowsedDataWithField($data, $header_field->field, $key, $field, $field_attribute, $data_lang);
            
          }
        }
    }
      
    return $data;
  }

  protected function updateBrowsedDataWithOid($data, $browse)
  {
    foreach ($browse['lines_oid'] as $key => $field)
    {
      $data = $this->updateBrowsedDataWithField($data, 'oid', $key, $field, Null);
    }

    return $data;
  }
  
  protected function updateBrowsedDataWithField($data, $field_id, $key, $field, $field_attribute, $lang = Null)
  {
    if (!$lang)
      $lang = $this->default_language;
    
    if (!array_key_exists($key, $data))
      $data[$key] = array();

    if (!$field_attribute)
      $data[$key][$field_id][$lang] = $field;
    else
      $data[$key][$field_id][$lang] = $field->$field_attribute;
    
    return $data;
  }
  
  /**
   * 
   * @param int $module_id
   * @param array $parameters
   * @param string $field_attribute Si précisé: Place comme valeur du champs 
   * l'attribut de l'objet au lieu de l'objet représentant le champ
   * @param boolean $get_oid Ajoute ou non le oid dans le tableau de retour
   * @return array
   * 
   * Retourne le browse dans un tableau de données avec pour valeur des champs 
   * l'attribut de l'objet champs spécifié
   */
  public function getArrangedDataWithSpecifiedFieldValue($module_id, $parameters, $field_attribute, $get_oid = True)
  {
    return $this->getFormatedData(
      $this->getBrowse($module_id, $parameters),
      $get_oid,
      $field_attribute
    );
  }

  /**
   * 
   * @param int $module_id
   * @param array $parameters
   * @param boolean $get_oid Ajoute ou non le oid dans le tableau de retour
   * @return array
   * 
   * Retourne le browse dans un tableau de données avec pour valeur des champs 
   * l'attribut 'raw' de l'objet champs
   */
  public function getArrangedDataWithRawFieldValue($module_id, $parameters, $get_oid = True)
  {
    return $this->getArrangedDataWithSpecifiedFieldValue($module_id, $parameters,  'raw', $get_oid);
  }
  
  /**
   * 
   * @param int $module_id
   * @param array $parameters
   * @param boolean $get_oid Ajoute ou non le oid dans le tableau de retour
   * @return array
   * 
   * Retourne le browse dans un tableau de données 
   */
  public function getArrangedData($module_id, $parameters, $get_oid = True, $lang_data='all')
  {
    return $this->getFormatedData($this->getBrowse($module_id, $parameters, $lang_data), $get_oid,NULL,$lang_data);
  }
  
  /**
   * 
   * @param int $module_id
   * @param array $parameters
   * @return array
   * 
   * Retourne le browse non transformé en un tableau de données 'arrangés'
   */
  public function getData($module_id, $parameters)
  {
    return $this->getBrowse($module_id, $parameters);
  }
  
  
}