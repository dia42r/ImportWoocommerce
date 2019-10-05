<?php
declare(strict_types=1);

namespace App\Service;


use App\Client\ProductClient;
use App\Dao\ProcessDao;
use App\Dao\ProductDao;
use App\DataFormatter\ProductFormatter;
use Automattic\WooCommerce\HttpClient\HttpClientException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

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

        $this->logger->pushHandler(new StreamHandler(__FILE__ . time(). '.log', Logger::DEBUG));
    }

    public function process()
    {

        $localProductIds = $this->productDao->getLocalProductsIds();

        $lastExecutionDate = $this->processDao->getLastExecutionDate();

        $this->logger->debug("Récuperation des produit de la base local ....: ",
            ['count:' => count($localProductIds)]);

        $remoteProductIds = $this->getRemoteProductIds();

        list($listProductToCreate, $listProductToUpdate, $listProductToDelete) = $this->buildProductList($remoteProductIds, $lastExecutionDate);


        list($resultUpdate, $errorsUpdate) = $this->executeUpdate($listProductToUpdate);
        $this->logger->error("Errors : ", ["Execute Update " => $errorsUpdate]);

        list($resultInsert, $errorsInsert) = $this->executeInsert($listProductToCreate);
        $this->logger->error("Errors : ", ["Execute create " => $errorsInsert]);

        list($resultDelete, $errorsDelete) = $this->executeDelete($listProductToCreate);
        $this->logger->error("Errors : ", ["Execute Delete " => $errorsDelete]);


        $datas = [
            'create' => $listProductToCreate,
            'update' => $listProductToUpdate,
            'delete' => array_keys($listProductToDelete)
            ];



        $callback = function ($res) {
            return $res->sku;
        };

        $report = [
                'created' => array_map($callback, $resultInsert),
                'updated' => array_map($callback, $resultUpdate),
                'delete' => array_map($callback, $resultDelete),
                'errorsInsert' => $errorsInsert,
                'errorsUpdate' => $errorsUpdate,
                'errorsDelete' => $errorsDelete,
        ];

        print_r($report);

        $this->createDatasFiles('products', 'json', $datas);
        $this->createDatasFiles('report', 'json', $report);

        $this->processDao->save();

    }



    /**
     * @return array
     */
    public function getRemoteProductIds(): array
    {

        $remoteProducts = [];
        try {
            $products = $this->productClient->getAllProducts();
            $this->logger->debug('Recupération des produits de la base distante ...: ', $products);

            foreach ($products as $product) {
                // key = idwoocommerce value = code_eds
                $remoteProducts[$product->id] = $product->sku;
            }

            $this->logger->debug('Recupération des produits de la base distante ...: ',
                ['count:' => count($remoteProducts), 'liste:' => $remoteProducts]);
        } catch (HttpClientException $e) {
            $this->logger->error("", [$e->getMessage()]);
            print_r($e->getMessage());
        }

        return $remoteProducts;
    }


    /**
     * @param $name
     * @param $type
     * @param $datas
     */
    public function createDatasFiles($name, $type, $datas): void
    {

        $fp = fopen($name.'_' . time() . '.'. $type, 'w');
        switch ($type) {
            case 'json' :
                $options = JSON_PRETTY_PRINT | JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE;
                fwrite($fp, stripslashes(json_encode($datas, $options)));
                break;

            case 'txt' :
                fwrite($fp, serialize($datas));

            default :
                fclose($fp);
        }
    }


    /**
     * Build product list to create update or delete
     * @param array $remoteProductIds
     * @param string $lastExecutionDate
     * @return array
     */
    private function buildProductList(array $remoteProductIds, $lastExecutionDate)
    {

        $listProductToCreate = [];
        $listProductToUpdate = [];
        $listProductToDelete = array_keys(array_filter($remoteProductIds, function($value) {
            return empty($value);
        }));

        $idsProducts = array_filter($remoteProductIds, function($value) {
            return !empty($value);
        });

        $listProduct =  $this->productDao->getProductByIds($idsProducts, $lastExecutionDate);

        foreach ($listProduct as $product) {

            $idProduct = array_search($product->id, $remoteProductIds);
            if(!($idProduct)) {
                $listProductToCreate[] = ProductFormatter::transform($product);
            } else {
                $listProductToUpdate[] = ProductFormatter::transform($product, $idProduct);
            }
        }

        return array($listProductToCreate, $listProductToUpdate, $listProductToDelete);
    }

    /**
     *
     * @param $listProductToUpdate
     * @param array $result
     * @param array $errors
     * @return array
     */
    public function executeUpdate($listProductToUpdate): array
    {
        $result = [];
        $errors = [];

        foreach ($listProductToUpdate as $product) {
            try {
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
    public function executeInsert($listProductToCreate): array
    {
        $result = [];
        $errors = [];

        foreach ($listProductToCreate as $product) {
            try {

                $result[] = $this->productClient->post($product);
            } catch (HttpClientException $e) {
                $errors[] = $e->getMessage() . " Product sku ". $product['sku'];
            }
        }

        return array($result, $errors);
    }

    /**
     * @param $listProductToDelete
     * @return array
     */
    private function executeDelete($listProductToDelete)
    {
        $result = [];
        $errors = [];

        foreach ($listProductToDelete as $product) {
            try {

                $result[] = $this->productClient->post($product);
            } catch (HttpClientException $e) {
                $errors[] = $e->getMessage() . " Product sku : " .$product['sku'];
            }
        }

        return array($result, $errors);
    }
}
