<?php
/**
 * Created by PhpStorm.
 * User: XQJM798
 * Date: 08/12/2019
 * Time: 00:02
 */

namespace App\Service;


use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class FTPService
{

    /**
     * @var Logger
     */
    private $logger;

    public function __construct()
    {
        $this->logger = new Logger(__FILE__);
        $this->logger->pushHandler(new StreamHandler(__FILE__ . time() . '.log', Logger::DEBUG));
    }

    public function getConnection()
    {
        $conn_id = ftp_connect($_ENV['FTP_HOST']);

        $login_result = ftp_login($conn_id, $_ENV['FTP_USER'], $_ENV['FTP_PWD']);

        if (!$login_result) {
            throw new \Exception('Impossible d\'etablir la connexion');
        }
        ftp_pasv($conn_id, true) or die("Unable switch to passive mode");;

        return $conn_id;
    }


    public function closeConnection($conn_id)
    {
        $result = ftp_close($conn_id);

        if (!$result) {
            throw new \Exception('Impossible de fermer la connexion !');
        }
    }

    public function fileExist(string $file_name, $conn_id)
    {

        return ftp_size($conn_id, $file_name) != -1;
    }

    /**
     * @param $conn_id
     * @param $remote_file
     * @param $local_file
     */
    function copyFile($conn_id, $remote_file, $local_file)
    {

        return ftp_put($conn_id, $remote_file, $local_file, FTP_BINARY);
    }


    /**
     * Envoie des images sur le serveur FTP
     * @param array $imagesToCpy
     */
    public function sendFiles(array $imagesToCpy, $source_dir, $dest_dir)
    {
        try {
            $ftp_cnx = $this->getConnection();
        } catch (\Exception $e) {
            $this->logger->error(" Erreur ",[" Connection FTP  " => $e->getMessage()]);
            exit("Impossible d'etablir la connection FTP");
        }

        foreach ($imagesToCpy as $image) {

            $image = str_replace(" ", "", $image);
            $remoteFile = $dest_dir.$image;
            $localFile = $source_dir.$image;

            if (!$this->fileExist($remoteFile, $ftp_cnx)) {

                if (!$this->copyFile($ftp_cnx, $remoteFile, $localFile)) {
                    $this->logger->error(" copie images",[" copie images" => "Erreur lors de la copie de l'image :  {$image} "]);
                };
            }
        }
    }
}
