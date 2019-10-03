<?php
declare(strict_types=1);

namespace App\Client;

use App\Config\AppConfig;
use Automattic\WooCommerce\Client;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * Class ProductClient
 * @package App\Client
 */
class ProductClient
{
    /**
     *
     * @var \Automattic\WooCommerce\Client
     */
    private $client;

    /**
     * @var Logger
     */
    private $logger;

    /**
     *
     * @var string $endpoints
     */
    private $endpoints = 'products';


    public function __construct()
    {
        $this->logger = new Logger(__CLASS__);
        try {
            $this->logger->pushHandler(new StreamHandler(__CLASS__ . '.log', Logger::DEBUG));
        } catch (\Exception $e) {

        }

        $this->client = new Client(
            AppConfig::END_POINT,
            AppConfig::CLIENT_KEY,
            AppConfig::CLIENT_SECRET,
            [
                'wp_api' => true,
                'version' => 'wc/v3',
                'query_string_auth' => true // Force Basic Authentication as query string true and using under HTTPS
            ]
        );

    }

    /**
     * @param int $id
     * @return array
     */
    public function getProductById($id)
    {
        return $this->client->get($this->endpoints . '/' . $id);
    }

    /**
     * @param array $datas
     * @return array
     */
    public function post(array $datas)
    {
        return $this->client->post($this->endpoints, $datas);

    }

    /**
     * @param $id
     * @param $data
     * @return array
     */
    public function putProduct($id, $data)
    {
        return $this->client->put($this->endpoints . '/'.$id, $data);
    }

    /**
     * @param $id
     * @return array
     */
    public function delete($id)
    {
        return $this->client->delete($this->endpoints . '/'.$id);
    }

    /**
     * @param $datas
     * @return array
     */
    public function batchProduit($datas)
    {
        return $this->client->post($this->endpoints ."/batch", $datas);
    }

    /**
     * @return array
     */
    public function getAllProducts()
    {
        $this->client->get($this->endpoints);
        $response = $this->client->http->getResponse();
        $nbrePage = $response->getHeaders()['X-WP-TotalPages'];

        $result = [];
        for ($i = 1; $i <= $nbrePage; ++$i) {
            $product = $this->client->get($this->endpoints, array('page' => $i));
            $result = array_merge($result, $product);
        }

        return $result;
    }
}