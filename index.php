<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use App\Dao\ProcessDao;
use App\Dao\ProductDao;
use App\Client\ProductClient;
use App\Service\ImportService;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;


$dotEnv = new Dotenv();
$dotEnv->load(__DIR__.'/Config/.env');

$logger = new Logger(__FILE__);

$logger->pushHandler(new StreamHandler(__FILE__.time(). '.log', Logger::DEBUG));

$startTime = microtime(true);
$logger->debug(" Debut du batch de MAJ", array(date('Y-m-d-H:i:s')));

$serviceImport = new ImportService(new ProductClient(), new ProductDao(), new ProcessDao());

$result = $serviceImport->process();

print_r($result);

$endTime = microtime(true);

$duree = $endTime - $startTime;
$logger->debug(" Fin du bach ", array('heure de fin ' =>date('Y-m-d-H:i:s'), 'Duree d\'execution' => $duree ));
