<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Client\ProductClient;
use App\Dao\ProcessDao;
use App\Dao\ProductDao;
use App\Service\FTPService;
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

$serviceImport = new ImportService(new ProductClient(), new ProductDao(), new ProcessDao(), new FTPService(), $logger);

$res = $serviceImport->process();


if (is_array($res)) {
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
            <td><b><?= count($res['created']) ?></b> produit(s) crées</td>
            <td>
                <ul><?php if (!empty($res['created'])) {
                        foreach ($res['created'] as $created) {
                            echo "<li>" . $created . "</li>";
                        }
                    } else echo "0" ?> </ul>
            </td>
        </tr>
        <tr>
            <td><b><?= count($res['updated']) ?></b> produit(s) mis à jour</td>
            <td>
                <ul> <?php if (!empty($res['updated'])) {
                        foreach ($res['updated'] as $updated) {
                            echo "<li>" . $updated . "</li>";
                        }
                    } else echo "0" ?> </ul>
            </td>
        </tr>
        <tr>
            <td><b><?= count($res['deleted']) ?></b> produit(s) supprimé</td>
            <td>
                <ul> <?php if (!empty($res['deleted'])) {
                        foreach ($res['deleted'] as $deleted ) {
                            echo "<li>" . $deleted . "</li>";
                        }
                    } else echo "0" ?> </ul>
            </td>
        </tr>
        </tbody>
    </table>

    <?php
} else { ?>

    <br>
    <div class="alert alert-success" role="alert">
        <?= $res ?></b>.
    </div>
    <?php

}
$endTime = microtime(true);


$logger->debug(" Fin du bach ",
    array('heure de fin ' => date('Y-m-d-H:i:s'), 'Duree d\'execution' => formatPeriod($endTime, $startTime)));


function formatPeriod($endtime, $starttime)
{

    $duration = $endtime - $starttime;

    $hours = (int)($duration / 60 / 60);

    $minutes = (int)($duration / 60) - $hours * 60;

    $seconds = (int)$duration - $hours * 60 * 60 - $minutes * 60;

    return ($hours == 0 ? "00" : $hours) . ":" . ($minutes == 0 ? "00" : ($minutes < 10 ? "0" . $minutes : $minutes)) . ":" . ($seconds == 0 ? "00" : ($seconds < 10 ? "0" . $seconds : $seconds));
}

