<?php
declare(strict_types=1);

namespace App\Service;


use App\Cache\RemoteProductsCache;
use App\Client\ProductClient;
use App\Dao\ProcessDao;
use App\Dao\ProductDao;
use App\DataFormatter\ProductFormatter;
use Automattic\WooCommerce\HttpClient\HttpClientException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * Class ImportService
 * @package App\Service
 */
class ImportService
{
    /**
     * @var ProductClient
     */
    private $productClient;

    /**
     * @var ProductDao
     */
    private $productDao;

    /**
     * @var ProcessDao
     */
    private $processDao;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * ImportService constructor.
     * @param ProductClient $productClient
     * @param ProductDao $productDao
     * @param ProcessDao $processDao
     * @throws \Exception
     */
    public function __construct(ProductClient $productClient, ProductDao $productDao, ProcessDao $processDao)
    {
        $this->productClient = $productClient;
        $this->productDao = $productDao;
        $this->processDao = $processDao;

        $this->logger = new Logger(__FILE__);

        $this->logger->pushHandler(new StreamHandler(__FILE__ . time() . '.log', Logger::DEBUG));
    }

    public function process()
    {
        /**
         * 1. Recuperer la derniere date d'execution
         * 2. Recuperer tous les produits qui ont été modifier apres cette date
         * 3. Si aucun produit modifier ==> fin du prcess sinon etape 4.
         * 4. Pour chaque produit recuperer
         *      1. Verifier si il est present actuellement sur le site
         *          Si Oui c'est une mise a jour
         *          Si Non c'est une nouveau produit a creer
         *          Bloucler sur la liste des produits et construire un tabelau avec la liste des produits à créer
         *          et à modifier.
         *
         *          productToUpd[]
         *          productToCreate[]
         * 5. Poster des produits a creer et a mettre a jour
         * 6. Produit depublier de la base
         *      Recupperer tous les produits actuellement sur le site
         *      Recuprer tous les produits publier de la base
         *      Les produits a supprimer sont ceux qui ne sont pas dans la liste des produits a publier de la base
         *      productToDelete[] = array_diff(remoteIds, localIds)
         * 7. Poster les produits a supprimer
         *
         */

        $lastExecutionDate = $this->processDao->getLastExecutionDate();

        $modifiedProducts = $this->productDao->getLastUpdProduct($lastExecutionDate);

        if (empty($modifiedProducts)) {

            return " Aucun produit mis a jour depuis la derniere syncronisation ...";
        }

        $newProduct = [];
        $productToUpd = [];

        foreach ($modifiedProducts as $product) {

            $params = [
                'sku' => $product->id
            ];

            $remoteProduct = $this->productClient->get($params);

            if (empty($remoteProduct)) {
                $product->id = null;
                $newProduct[] = $product;
            } else {
                $product->id = $remoteProduct[0]->id;
                $productToUpd[] = $product;
            }
        }

        list($resultUpdate, $errorsUpdate) = $this->executeUpdate($productToUpd);
        $this->logger->error("Errors : ", ["Execute Update " => $errorsUpdate]);

        list($resultInsert, $errorsInsert) = $this->executeInsert($newProduct);
        $this->logger->error("Errors : ", ["Execute create " => $errorsInsert]);

        list($resultDelete, $errorsDeleted, $listProductToDeleted) = $this->executeDelete();
        $this->logger->error("Errors  : ", ["Execute Delete "=> $errorsDeleted]);




        $datas = [
            'create' => $newProduct,
            'update' => $productToUpd,
            'delete' => $listProductToDeleted
        ];

        $callback = function ($res) {
            return $res->sku;

        };

        $report = [
            'created' => array_map($callback, $resultInsert),
            'updated' => array_map($callback, $resultUpdate),
            'deleted' => array_map($callback, $resultDelete),
            'errorsInsert' => $errorsInsert,
            'errorsUpdate' => $errorsUpdate,
            'errorsDelete' => $errorsDeleted
        ];

        print_r($datas);
        print_r($report);


        $this->createDatasFiles('products', 'json', $datas);
        $this->createDatasFiles('report', 'json', $report);

        $this->processDao->save();

    }

    /**
     *
     * @param $listProductToUpdate
     * @param array $result
     * @param array $errors
     * @return array
     */
    private function executeUpdate($listProductToUpdate): array
    {
        $result = [];
        $errors = [];

        foreach ($listProductToUpdate as $product) {
            try {
                $product = ProductFormatter::transform($product);
                $result[] = $this->productClient->putProduct($product['id'], $product);
            } catch (HttpClientException $e) {
                $errors[] = $e->getMessage() . ' Product sku ' . $product['sku'];
            }
        }

        return array($result, $errors);
    }

    /**
     * @param $listProductToUpdate
     * @param array $result
     * @param array $errors
     * @return array
     */
    private function executeInsert($listProductToCreate): array
    {
        $result = [];
        $errors = [];

        foreach ($listProductToCreate as $product) {
            try {
                $product = ProductFormatter::transform($product);
                $result[] = $this->productClient->post($product);
            } catch (HttpClientException $e) {
                $errors[] = $e->getMessage() . " Product sku " . $product['sku'];
            }
        }

        return array($result, $errors);
    }

    /**
     * @param $name
     * @param $type
     * @param $datas
     */
    private function createDatasFiles($name, $type, $datas): void
    {
        $fp = fopen($name . '_' . time() . '.' . $type, 'w');
        switch ($type) {
            case 'json' :
                $options = JSON_PRETTY_PRINT | JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE;
                if (!json_encode($datas, $options)) {
                    print_r($datas);
                    break;
                }

                fwrite($fp, stripslashes(json_encode($datas, $options)));
                break;

            case 'txt' :
                fwrite($fp, serialize($datas));

            default :
                fclose($fp);
        }
    }

    /**
     * @param $listProductToDelete
     * @return array
     */
    private function executeDelete()
    {
        $result = [];
        $errors = [];
        $remoteProducts = RemoteProductsCache::getRemoteProduct();
        $localPublishProducts = $this->productDao->getLocalProductsIds();

        $diff = array_diff(array_values($remoteProducts), $localPublishProducts);

        $listProductToDelete = array_map(function($val) use ($remoteProducts) {
            return array_search($val, $remoteProducts);
        }, $diff);

        foreach ($listProductToDelete as $product) {
            try {

                $result[] = $this->productClient->delete($product);
            } catch (HttpClientException $e) {
                $errors[] = $e->getMessage() . " Product Not deleted  : " . $product;
            }
        }

        return array($result, $errors, $listProductToDelete);
    }
}
