<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Dao\ProcessDao;
use App\Dao\ProductDao;
use App\Client\ProductClient;
use App\Service\ImportService;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;


$logger = new Logger(__FILE__);

$logger->pushHandler(new StreamHandler(__FILE__.'.log', Logger::DEBUG));


$serviceImport = new ImportService(new ProductClient(), new ProductDao(), new ProcessDao());

$result = $serviceImport->process();

print_r($result);
