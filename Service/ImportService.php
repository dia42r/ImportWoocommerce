<?php
declare(strict_types=1);

namespace App\Service;

ini_set('max_execution_time', '0');

use App\Client\ProductClient;
use App\Dao\ProcessDao;
use App\Dao\ProductDao;
use App\DataFormatter\ProductFormatter;
use Automattic\WooCommerce\HttpClient\HttpClientException;
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
     * @var FTPService
     */
    private $ftpService;

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
    public function __construct(
        ProductClient $productClient,
        ProductDao $productDao,
        ProcessDao $processDao,
        FTPService $ftpService,
        Logger $logger
    ) {
        $this->productClient = $productClient;
        $this->productDao = $productDao;
        $this->processDao = $processDao;
        $this->ftpService = $ftpService;
        $this->logger = $logger;
//
//        $this->logger = new Logger(__FILE__);
//
//        $this->logger->pushHandler(new StreamHandler(__FILE__ . time() . '.log', Logger::DEBUG));
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

        $imagesToCpy = [];
        foreach ($modifiedProducts as $product) {

            $images = $product->images;
            $images = $this->extractImagesNames($images);
            $imagesToCpy = array_merge($imagesToCpy, $images);

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

        if (!empty($imagesToCpy)) {
            $this->ftpService->sendFiles($imagesToCpy, $_ENV['LOCAL_IMAGES_DIR'], $_ENV['REMOTE_IMAGES_DIR']);
        }

        if (!empty($productToUpd)) {
            list($resultUpdate, $errorsUpdate) = $this->executeUpdate($productToUpd);
            $this->logger->error("Errors : ", ["Execute Update " => $errorsUpdate]);
        }

        if (!empty($newProduct)){
            list($resultInsert, $errorsInsert) = $this->executeInsert($newProduct);
            $this->logger->error("Errors : ", ["Execute create " => $errorsInsert]);
        }


        list($resultDelete, $errorsDeleted, $listProductToDeleted) = $this->executeDelete($lastExecutionDate);
        $this->logger->error("Errors  : ", ["Execute Delete " => $errorsDeleted]);

        // Execute MAj upsells
        $allProducts = array_merge($productToUpd, $newProduct);

        if (!empty($allProducts)) {
            list($resultMajUpsell, $errorsMajUpsell) = $this->executeUpdateUpsell($allProducts);
        }

        $datas = [
            'create' => $newProduct,
            'update' => $productToUpd,
            'delete' => $listProductToDeleted
        ];

        $callback = function ($res) {
            return $res->sku;
        };

        $report = [
            'created' => array_map($callback, isset($resultInsert) ? $resultInsert : []),
            'updated' => array_map($callback, isset($resultUpdate) ? $resultUpdate : []),
            'deleted' => array_map($callback, isset($resultDelete) ? $resultDelete : []),
            'majUpsell' => array_map($callback, isset($resultMajUpsell)? $resultMajUpsell : []),
            'errorsInsert' => isset($errorsInsert) ? $errorsInsert : [],
            'errorsUpdate' => isset($errorsUpdate) ? $errorsUpdate: [],
            'errorsDelete' => $errorsDeleted,
            'errorsMajUpsell' => $errorsMajUpsell
        ];

        $this->createDatasFiles('products', 'json', $datas);
        $this->createDatasFiles('report', 'json', $report);

        $this->processDao->save();

        return $report;
    }

    /**
     * @param $images
     * @return array
     */
    private function extractImagesNames($images): array
    {
        $images = explode(",", $images);

        return array_filter($images, function ($image) {
            return $image != 0;
        });
    }


    /**
     * @param $listProductToUpdate
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
     * @param $listProductToCreate
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
     * @param string $lastUpdateDate
     * @return array
     */
    private function executeDelete(string $lastUpdateDate)
    {
        $result = [];
        $errors = [];

        $listIdProductToDelete = $this->productDao->getLastDelProductIds($lastUpdateDate);

        foreach ($listIdProductToDelete as $idProduct) {
            try {

                $remoteProductId = $this->productClient->getRemoteIdByCodeEds($idProduct);
                $result[] = $this->productClient->delete($remoteProductId);
            } catch (HttpClientException $e) {
                $errors[] = $e->getMessage() . " Product Not deleted  : " . $idProduct;
            }
        }

        return array($result, $errors, $listIdProductToDelete);
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
}
