<?php
declare(strict_types=1);

namespace  App\Entity;

/**
 * Class Product
 * @package App\Entity
 */
class Product
{

    public $id;
    public $title;
    public $name;
    public $type;
    public $regularPrice;
    public $description;
    public $descriptionShort;
    public $sku;
    public $weight;
    public $design;
    public $categorie;
    public $tags;
    public $images;
    public $attributes;
    public $hauteur;
    public $hauteurAssise;
    public $hauteurAccoudoire;
    public $longueur;
    public $largeur;
    public $epaisseur;
    public $profondeur;
    public $longueurMin;
    public $longueurMax;
    public $largeurMin;
    public $largeurMax;
    public $carreMin;
    public $carreMax;
    public $diametre;
    public $diametreMin;
    public $diametreMax;
    public $longueurPlateau;
}
