<?php
declare(strict_types=1);

namespace  App\Entity;

use App\Cache\RemoteProductsCache;
use App\Dao\ProductDao;

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
    public $collection;

    public function getCollection($collection, $codeEds)
    {
        $productDao = new ProductDao();
        $collections = $productDao->getRelatedCollectionProductIds($collection, $codeEds);

        $remoteProducts = RemoteProductsCache::getRemoteProduct();

        return array_map(function ($val) use ($remoteProducts){
            return array_search($val, $remoteProducts);
        }, $collections);

    }
}
