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
    public static function transform(Product $product)
    {

                $datas = [
                "id" => $product->id,
                "title" => utf8_encode($product->title),
                "name" => utf8_encode($product->name),
                "type" => $product->type,
                "regular_price" => $product->regularPrice,
                "description" => utf8_encode(str_replace('"', "", $product->description)),
                "short_description" => utf8_encode(str_replace('"',"",$product->descriptionShort)),
                "sku" => $product->sku,
                "weight" => $product->weight,
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


    public static function transformUpsells(Product $product)
    {

                $datas = [
                "id" => $product->id,
                "upsell_ids" => $product->collection != '' ? $product->getCollection($product->collection, $product->sku) : [],
            ];

        return self::utf8ize($datas);
    }


    private static function getImages($product_images, $product_name)
    {

        $images = explode(",", $product_images);

        $results = array_filter($images, function ($image) {

            return $image != 0;
        });

        return array_map(function ($image) use ($product_name) {
            return [
                "src" => str_replace(" ","",$_ENV['IMAGES_LOCATION'].$image),
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
