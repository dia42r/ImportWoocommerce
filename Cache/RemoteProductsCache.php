<?php
/**
 * Created by PhpStorm.
 * User: XQJM798
 * Date: 21/10/2019
 * Time: 17:28
 */

namespace App\Cache;


use App\Client\ProductClient;

class RemoteProductsCache
{
    public static $remoteProduct = null;

    public static function getRemoteProduct()
    {

        if (is_null(self::$remoteProduct)) {
            $productClient = new ProductClient();
            self::$remoteProduct = $productClient->getAllRemoteProducts();

        }
        return self::$remoteProduct;
    }
}
