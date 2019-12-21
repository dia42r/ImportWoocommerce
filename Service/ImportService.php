<?php
declare(strict_types=1);

namespace App\Service;

ini_set('max_execution_time', '0');

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


    /**
     * @return array|string
     */
    public function process()
    {

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

        list($resultDelete, $errorsDeleted, $listProductToDeleted) = $this->executeDelete($lastExecutionDate);
        $this->logger->error("Errors  : ", ["Execute Delete "=> $errorsDeleted]);

        // Execute MAj upsells

        $allProducts = array_merge($productToUpd, $newProduct);

        list($resultMajUpsell, $errorsMajUpsell) = $this->executeUpdateUpsell($allProducts);

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
            'majUpsell' => array_map($callback, $resultMajUpsell),
            'errorsInsert' => $errorsInsert,
            'errorsUpdate' => $errorsUpdate,
            'errorsDelete' => $errorsDeleted,
            'errorsMajUpsell' =>$errorsMajUpsell
        ];

        $this->createDatasFiles('products', 'json', $datas);
        $this->createDatasFiles('report', 'json', $report);

        $this->processDao->save();

        return $report;
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
     *
     * @param $listProductToUpdate
     * @param array $result
     * @param array $errors
     * @return array
     */
    private function executeUpdateUpsell($listProductToUpdate): array
    {
        $result = [];
        $errors = [];

        foreach ($listProductToUpdate as $product) {
            try {
                $product = ProductFormatter::transformUpsells($product);
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
     * @param string $lastUpdateDate
     * @return array
     */
    private function executeDelete(string $lastUpdateDate)
    {
        $result = [];
        $errors = [];

        $listProductToDelete = $this->productDao->getLastDelProduct($lastUpdateDate);

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
