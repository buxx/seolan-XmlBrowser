seolan-XmlBrowser
=================

# Introduction

Génération de sortie xml appuyé sur seolan. L'idée est de pouvoir configuré le rendu
d'un fichier xml uniquement coté php exploitant le browse d'un module de gestionnaire
de fiche.

# Exemple

``` php
$browser = new Browser();
$xml_browser = new XmlBrowser("1.0", "UTF-8");

// On récupère un tableau des données (module 44, un gestionnaire de fiche) dans un format exploitable
// par XmlBrowser. Le deuxième paramètre de getArrangedData() est un tableau de paramètre habituellement 
// transmit au browse.
$prepared_data = $browser->getArrangedData(44, array('selectedfields' => 'all'));

// Pour plus de doc, se reporter aux commentaire de la méthode
$xml_browser->prepare(
  $prepared_data,
  // La structure de notre premier niveau
  array('catalogue', 'product'),
  // On peux spécifier des attributs sur les branches (TODO: faire fonctionner sur tous les niveaux)
  array('product' => array('id' => 'oid')),
  // Ce tableau paramètre l'affichage de chaques élements affiché dans le XML
  array(
    'UPD' => array('CDATA' => True, 'field' => 'raw', 'display' => 'update'),
    'oid' => array('hide' => True),
    'name' => array('hide' => False, 'field_value_call_back' => function($field){
      return 'Mister '.$field->raw;
    })
  ),
  // On peux ajouter des element indépendants a l'arbre xml, c'est a dire en plus des champs de l'objet géré dans le gestionnaire de fiche
  array(
    'additional' => array('CDATA' => True, 'field_value_call_back' => function($element){
      return 'El\' '.$element['FNAME'];
    }),
    // Afin d'ajouter un niveau dans notre XML on précise que le champs est un 'Tree'
    'medias' => array('Tree' => True, 'configuration_callback' => function($element){
      // Dans ce callback il faut en fait simplement retourner la même chose que dans $xml_browser->prepare()
      $browser = new Browser();
      return array(
        'prepared_data'          => $browser->getArrangedData(45, array(
          'selectedfields' => array('title'),
          'cond' => array('stage' => array('=', $element['oid']) )
        )), 
        'root_structure'         => array('medias', 'media'), 
        'elements_attributes'    => array(), 
        'elements_configuration' => array(
          'title' => array('CDATA' => True, 'field' => 'raw'),
        )
      );
    })
  )
);
```