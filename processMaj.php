<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Client\ProductClient;
use App\Dao\ProcessDao;
use App\Dao\ProductDao;
use App\Service\ImportService;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Dotenv\Dotenv;

$dotEnv = new Dotenv();
$dotEnv->load(__DIR__ . '/Config/.env');

$logger = new Logger(__FILE__);

$logger->pushHandler(new StreamHandler(__FILE__ . time() . '.log', Logger::DEBUG));

$startTime = microtime(true);
$logger->debug(" Debut du batch de MAJ", array(date('Y-m-d-H:i:s')));

$serviceImport = new ImportService(new ProductClient(), new ProductDao(), new ProcessDao());

// $result = $serviceImport->process();

$report = [
    'created' => 3,
    'updated' => 3,
    'deleted' => 4,
    'majUpsell' => 4,
    'errorsInsert' => ['err : err1 ', 'err : err1 ', 'err : err1 ', 'err : err1 '],
    'errorsUpdate' => ['err : err1 ', 'err : err1 ', 'err : err1 ', 'err : err1 '],
    'errorsDelete' => ['err : err1 ', 'err : err1 ', 'err : err1 ', 'err : err1 '],
    'errorsMajUpsell' => ['err : err1 ', 'err : err1 ', 'err : err1 ', 'err : err1 ']
];

if (is_array($report)) {
    ?>
    <table class="table">
        <thead class="thead-green">

        <tr>
            <th scope="col"></th>
            <th scope="col"></th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Nombre de produit crée</td>
            <td><?= $report['created'] ?></td>
        </tr>
        <tr>
            <td>Nombre de produit mis à jour</td>
            <td><?= $report['updated'] ?></td>
        </tr>
        <tr>
            <td>Nombre de produit supprimé</td>
            <td><?= $report['deleted'] ?></td>
        </tr>
        </tbody>
    </table>

    <table class="table">
        <thead class="thead-light">
        <tr>
            <th scope="col"> </th>
            <th scope="col"> </th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Erreur de creation </td>
            <td><?= var_dump($report['errorsInsert']) ?></td>
        </tr>
        <tr>
            <td>Erreur de Mise à jour  </td>
            <td><?= var_dump($report['errorsUpdate']) ?></td>
        </tr>
        <tr>
            <td>Erreur de mise à jour  </td>
            <td><?= var_dump($report['errorsMajUpsell']) ?></td>
        </tr>
        <tr>
            <td>Erreur de suppression   </td>
            <td><?= var_dump($report['errorsDelete']) ?></td>
        </tr>
        </tbody>
    </table>

    <?php
}
$endTime = microtime(true);

$duree = $endTime - $startTime;
$logger->debug(" Fin du bach ", array('heure de fin ' => date('Y-m-d-H:i:s'), 'Duree d\'execution' => $duree));
