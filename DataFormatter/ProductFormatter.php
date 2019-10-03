<?php
declare(strict_types=1);

namespace App\DataFormatter;


use App\Entity\Product;
use App\Config\AppConfig;
use App\Exception\FunctionnalExecption;

/**
 * Class ProductFormatter
 * @package App\DataFormatter
 */
class ProductFormatter
{
    /**
     * @param array $listProduct
     * @return array
     */
    public static function transform(Product $product, $id = null)
    {

                $datas = [
                "id" => $id,
                "title" => utf8_encode($product->title),
                "name" => utf8_encode($product->name),
                "type" => $product->type,
                "regular_price" => $product->regularPrice,
                "description" => utf8_encode(str_replace('"', "", $product->description)),
                "short_description" => utf8_encode(str_replace('"',"",$product->descriptionShort)),
                "sku" => $product->sku,
                "weight" => $product->weight,
                "dimensions" => self::getDimensions($product)

                ,
                "categories" => self::getCategorie($product->categorie)

                ,
                "tags" => [
                    [],
                    []
                ],
                "images" =>
                    self::getImages($product->images, $product->name)

                ,
                "attributes" => [
                    [
                        "id" => 2,
                        "options" => [

                            self::getDesign($product->design)
                        ]
                    ],
                    [
                        "id" => 3,
                        "options" => [
                            "Collection X"
                        ]
                    ],
                    [
                        "id" => 4,
                        "options" => [
                            "Extérieur",
                            "Intérieur"
                        ]
                    ]
                ]
            ];

        return self::utf8ize($datas);
    }

    private static function getDimensions(Product $product)
    {
        $dimensions = [];
        if (self::valueExist($product->hauteur)) {
            $dimensions[] = ["hauteur" => $product->hauteur];
        }

        if (self::valueExist($product->hauteurAssise)) {
            $dimensions[] = ["hauteur assise" => $product->hauteurAssise];
        }

        if (self::valueExist($product->hauteurAccoudoire)) {
            $dimensions[] = ["hauteur accoudoire" => $product->hauteurAccoudoire];
        }

        if (self::valueExist($product->longueur)) {
            $dimensions[] = ["longueur" => $product->longueur];
        }

        if (self::valueExist($product->largeur)) {
            $dimensions[] = ["largeur" => $product->largeur];
        }

        if (self::valueExist($product->epaisseur)) {
            $dimensions[] = ["epaisseur" => $product->epaisseur];
        }

        if (self::valueExist($product->profondeur)) {
            $dimensions[] = ["profondeur" => $product->profondeur];
        }

        if (self::valueExist($product->longueurMin)) {
            $dimensions[] = ["longueur min" => $product->longueurMin];
        }

        if (self::valueExist($product->longueurMax)) {
            $dimensions[] = ["logueur max" => $product->longueurMax];
        }

        if (self::valueExist($product->largeurMin)) {
            $dimensions[] = ["largeur min" => $product->largeurMin];
        }

        if (self::valueExist($product->largeurMax)) {
            $dimensions[] = ["largeur max" => $product->largeurMax];
        }

        if (self::valueExist($product->largeurMax)) {
            $dimensions[] = ["largeur max" => $product->largeurMax];
        }

        if (self::valueExist($product->carreMin)) {
            $dimensions[] = ["carre min" => $product->carreMin];
        }

        if (self::valueExist($product->carreMax)) {
            $dimensions[] = ["carre max" => $product->carreMax];
        }

        if (self::valueExist($product->diametre)) {
            $dimensions[] = ["diametre " => $product->diametre];
        }

        if (self::valueExist($product->diametreMin)) {
            $dimensions[] = ["diametre max" => $product->diametreMin];
        }

        if (self::valueExist($product->diametreMax)) {
            $dimensions[] = ["diametre max" => $product->diametreMax];
        }

        if (self::valueExist($product->longueurPlateau)) {
            $dimensions[] = ["longueur plateau" => $product->longueurPlateau];
        }

        return $dimensions;

    }

    private static function valueExist($value)
    {
        return (!empty($value) || $value <> 0);
    }

    private static function getImages($product_images, $product_name)
    {

        $images = explode(",", $product_images);

        $results = array_filter($images, function ($image) {

            return $image != 0;
        });

        return array_map(function ($image) use ($product_name) {
            return [
                "src" => str_replace(" ","",AppConfig::IMAGES_LOCATION.$image),
                "name" => $product_name,
                "alt" => $product_name
            ];
        }, $results);
    }


    private static function getCategorie($id)
    {

        try {
            if ($idCatDist = array_search($id, array_flip(AppConfig::CATEGORIES_MAP))) {

                return [["id" => AppConfig::DEFAULT_CATEGORY], ["id" => $idCatDist]];
            }

            throw new FunctionnalExecption(sprintf("Category %s not found in CATEGORY_MAP", $id));

        } catch (FunctionnalExecption $e) {

            sprintf("%s",  $e->getMessage());
        }

        return [];
    }

    private static function getDesign($design)
    {
        if (array_key_exists($design, AppConfig::ATTRIBUTES_DESIGN_MAPS)) {

            return AppConfig::ATTRIBUTES_DESIGN_MAPS[$design];
        }
        return "";
    }

    /* Use it for json_encode some corrupt UTF-8 chars
     * useful for = malformed utf-8 characters possibly incorrectly encoded by json_encode
     */
    private static function utf8ize( $mixed ) {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = self::utf8ize($value);
            }
        } elseif (is_string($mixed)) {
            return mb_convert_encoding($mixed, "UTF-8", "UTF-8");
        }
        return $mixed;
    }
}
