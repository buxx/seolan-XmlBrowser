<?php

/**
 * @author Bastien Sevajol
 * @version 20140106001
 * 
 * Convertir un source de données (browse) en xml
 * Doit s'associé avec un template correspondant (voir generic.xml ou xml.xml)
 * 
 * TODO: Depuis que les valeur sont exprimés array('type' => 'element', 
 * 'value' => 'la valeur'), si on veut afficher
 * oid ca ne colle pas. A adapter donc.
 * TODO: Les $elements_attributes doivent être gérés avec un callback et rangé 
 * dans un array qui leurs est propre
 * TODO: Quand on travaille au calcul des valeurs de champs on met a jour de 
 * tableau en cours. C'est pas tres logique car au fur et a mesure de l'avancement
 * on ne travaille pas avec les même valeurs dans les callbacks ... Il faudrait 
 * avoir un tableau de reference et construire le tableau qui sera rendu a Smarty
 */
class XmlBrowser
{
  
  protected $version;
  protected $encoding;
  protected $exhaustive;
  
  /**
   * 
   * @param string $version Version of XML document
   * @param string $encoding Encoding of XML document
   * dans le paramètre $elements_configuration de la méthode publique prepare()
   */
  public function __construct($version = "1.0", $encoding = "UTF-8")
  {
    $this->version = $version;
    $this->encoding = $encoding;
  }
  
  /**
   * 
   * @param array $prepared_data Tableau de données (structure: chaques ligne 
   * contient un tableau dont les indices sont l'id du champ et la valeur la 
   * valeur du champ)
   * @param array $root_structure Tableau contenant array('nom_de_la_branche_mere', 
   * 'nom_des_branches_elements')
   * @param array $elements_attributes Tableau contenant la liste des attributs 
   * des elements. array('nom_de_la_branche' => array('id_attribut' => 'attribut_objet_valeur')).
   * Pour le moment on ne peut donner qu'un attribut, plus tard un callback (TODO)
   * @param array $elements_configuration Configuration des elements. Exemple:
   * array(
        'UPD' => array('CDATA' => True, 'field' => 'raw', 'display' => 'update'),
        'FPOSITIONS' => array('display' => 'positions', 'field_value_call_back' => function($field){
          return json_encode($field->alltable);
        }),
      )
   * CDATA: Le contenue est dans un CDATA
   * field: la valeur doit sera cet attribut de l'objet du champ
   * display: nom d'affichage du champ
   * field_value_call_back: callback pour calculer la valeur que prendra le champs
   * @param array $additional_fields_configuration Champs additionnels. Réutilise le type de 
   * configuration de $elements_configuration. Il est cependant obligatoire de 
   * donner le callback. Dans ce callback sera donné le tableau de l'élement 
   * actuellement parcourru.
   * 
   * Configure et prépare les variables Smarty pour l'affichage des données 
   * $prepared_data en xml.
   */
  public function prepare($prepared_data, $root_structure, $elements_attributes = array(), $elements_configuration = array(), $additional_fields_configuration = array())
  {
    //die(var_dump($this->get($prepared_data, $root_structure, $elements_attributes, $elements_configuration, $additional_fields_configuration)));
    XShell::toScreen2('xml', 'generic', $this->get($prepared_data, $root_structure, $elements_attributes, $elements_configuration, $additional_fields_configuration));
  }
  
  protected function get($prepared_data, $root_structure, $elements_attributes = array(), $elements_configuration = array(), $additional_fields_configuration = array())
  {
    $prepared_data = $this->getPreparedFieldsValues($prepared_data, $elements_configuration);
    $prepared_data = $this->addAdditionnalFields($prepared_data, $additional_fields_configuration);
    return $this->assignTemplateVariables(
      $prepared_data, 
      $root_structure, 
      $elements_attributes, 
      $elements_configuration, 
      $additional_fields_configuration
    );
  }
  
  protected function assignTemplateVariables($prepared_data, $root_structure, $elements_attributes, $elements_configuration, $additional_fields_configuration, $return = False)
  {
    return array(
      'data' => $prepared_data,
      'parent_name' => $root_structure[0],
      'child_name' => $root_structure[1],
      'version' => $this->version,
      'encoding' => $this->encoding,
      'elements_attributes' => $elements_attributes,
      'elements_configuration' => array_merge($elements_configuration, $additional_fields_configuration)
    );
  }
  
  // TODO: Cette méthode aurait bien besoin d'être nettoyé (beaucoups meme)
  protected function getPreparedFieldsValues($prepared_data, $elements_configuration)
  {
    // TODO ranger cette partie du code dans une methode
    foreach ($prepared_data as $element_key => $element_data)
    {
      if (array_key_exists('oid', $element_data))
      {
        $prepared_data[$element_key]['oid'] = array(
          'type' => 'element', 
          'value' => $element_data[$element_key]['oid']
        );
      }
    }
      
    foreach ($elements_configuration as $element_field_id => $element_configuration)
    {
      $field_attribute = 'raw';
      if (array_key_exists('field', $element_configuration))
        $field_attribute = $element_configuration['field'];

      foreach ($prepared_data as $element_key => $element)
      {
        if (!empty($element[$element_field_id]))
        {
          if (array_key_exists('field_value_call_back_with_object', $element_configuration))
          {
            $field_callback = $element_configuration['field_value_call_back_with_object'];
            $field_value_data = array(
              'type'  => 'element',
              'value' => $field_callback($prepared_data[$element_key])
            );
          }
          elseif (array_key_exists('field_value_call_back', $element_configuration))
          {
            $field_callback = $element_configuration['field_value_call_back'];
            $field_value_data = array(
              'type'  => 'element',
              'value' => $field_callback($prepared_data[$element_key][$element_field_id])
            );
          }
          else
          {
            $field_value_data = array(
              'type'  => 'element',
              'value' => $prepared_data[$element_key][$element_field_id]->$field_attribute
            );
          }
        }

        $prepared_data[$element_key][$element_field_id] = $field_value_data;
      }
    }
    
    return $prepared_data;
  }
  
  protected function addAdditionnalFields($prepared_data, $elements_configuration)
  {
    foreach ($prepared_data as $field_key => $element_data)
    {
      foreach ($elements_configuration as $new_field_id => $new_field_configuration)
      {
        if ($this->isTreeElement($new_field_configuration))
        {
          $prepared_data[$field_key][$new_field_id] = $this->getFieldValueForTreeElement($element_data, $new_field_configuration);
          
        }
        else  
        {
          if (!array_key_exists('field_value_call_back', $new_field_configuration))
            throw new Exception('New field configuration must be have callback for value ("field_value_call_back" => function($element_data){return $element_data["myfieldid"];}).');
          $prepared_data[$field_key][$new_field_id] = array(
            'type'  => 'element',
            'value' => $new_field_configuration['field_value_call_back']($element_data)
          );
        }
      }
    }
    
    return $prepared_data;
  }
  
  protected function isTreeElement($new_field_configuration)
  {
    if (array_key_exists('Tree', $new_field_configuration))
      return $new_field_configuration['Tree'];
    return False;
  }
  
  protected function getFieldValueForTreeElement($element_data, $new_field_configuration)
  {
    $configuration = $new_field_configuration['configuration_callback']($element_data);
    
    return array(
      'type'  => 'Tree',
      'value' => $this->get(
        $configuration['prepared_data'], 
        $configuration['root_structure'], 
        $configuration['elements_attributes'], 
        $configuration['elements_configuration'], 
        $configuration['additional_fields_configuration']
      )
    );
  }
  
}
