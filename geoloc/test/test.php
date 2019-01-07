<?php 
$file = "rechercherListe_in.xml";
$xml = simplexml_load_file($file);

/* print_r($xml); */

/* print_r($xml->rechercherListe); */
/* print_r($xml->detailsGaam); */
/* print_r($xml->recupererListe); */


/* if (!empty($xml->rechercherListe)) { */
/*   echo "rechercherListe\n"; */
/* } */
/* elseif (!empty($xml->detailsGaam)) { */
/*   echo "detailsGaam\n"; */
/* } */
/* elseif (!empty($xml->recupererListe)) { */
/*   echo "recupererListe\n"; */
/* } */
/* $value = current((array)$xml); */
/* $key = key((array)$xml); */

/* echo "key = ".print_r($key,true)." - ".print_r($value,true)."\n"; */

$key   = key((array)$xml);
print_r($xml->$key);
$xml2 = $xml->$key;
$latu  = $xml->$key->latitudeUtilisateur;
$longu = $xml->$key->longitudeUtilisateur;
$latr  = $xml->$key->latitudeRecherche;
$longr = $xml->$key->longitudeRecherche;
$dist  = $xml->$key->rayon;
$limit = $xml->$key->nbrMax;

$bornesup = $xml->$key->borneSup;
$borneinf = $xml->$key->borneInf;
$borne    = $dist;

echo " $key - $latu - $longu - $latr - $longr - $dist - $limit\n";
