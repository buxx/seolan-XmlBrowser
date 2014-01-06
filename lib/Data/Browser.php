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
  
  /**
   * @param int $module_id
   * @param array $parameters
   * @return array
   * 
   * Retourne le browse
   */
  protected function getBrowse($module_id, $parameters)
  {
    $module = XModule::objectFactory($module_id);
    return $module->browse(array_merge($parameters, array(
      'tplentry' => TZR_RETURN_DATA,
      'pagesize' => 9999999999,
      '_local' => True,
    )));
  }
  
  /**
   * 
   * @param array $browse Le browse issue du module source de donnée
   * @param boolean $get_oid Ajoute ou non le oid dans le tableau de retour
   * @param string $field_attribute Si précisé: Place comme valeur du champs 
   * l'attribut de l'objet au lieu de l'objet représentant le champ
   * @return array
   */
  protected function getFormatedData($browse, $get_oid = True, $field_attribute = Null)
  {
    $data = array();
    
    if ($get_oid)
      $data = $this->updateBrowsedDataWithOid ($data, $browse);
    
    foreach ($browse['header_fields'] as $header_field)
    {
      foreach ($browse['lines_o'.$header_field->field] as $key => $field)
      {
        $data = $this->updateBrowsedDataWithField($data, $header_field->field, $key, $field, $field_attribute);
      }
    }
    
    return $data;
  }
  
  protected function updateBrowsedDataWithOid($data, $browse)
  {
    foreach ($browse['lines_oid'] as $key => $field)
    {
      $data = $this->updateBrowsedDataWithField($data, 'oid', $key, $field);
    }
    
    return $data;
  }
  
  protected function updateBrowsedDataWithField($data, $field_id, $key, $field, $field_attribute)
  {    
    if (!array_key_exists($key, $data))
      $data[$key] = array();

    if (!$field_attribute)
      $data[$key] = array_merge($data[$key], array($field_id => $field));
    else
      $data[$key] = array_merge($data[$key], array($field_id => $field->$field_attribute));
    
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
  public function getArrangedData($module_id, $parameters, $get_oid = True)
  {
    return $this->getFormatedData($this->getBrowse($module_id, $parameters), $get_oid);
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