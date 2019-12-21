<?php
/**
 * Created by PhpStorm.
 * User: XQJM798
 * Date: 08/12/2019
 * Time: 00:02
 */

namespace App\Service;


class FTPService
{
    public function getConnection()
    {
        $conn_id = ftp_connect($_ENV['FTP_HOST']);

        $login_result = ftp_login($conn_id, $_ENV['FTP_USER'], $_ENV['FTP_PWD']);

        if (!$login_result) {
            throw new \Exception('Impossible d\'etablir la connexion');
        }
        ftp_pasv($conn_id, true);

        return $conn_id;
    }


    public function closeConnection($conn_id)
    {
        $result = ftp_close($conn_id);

        if (!$result) {
            throw new \Exception('Ompossible de fermer la connexion !');
        }
    }

    public function fileExist(string $file_name, $conn_id)
    {

        return ftp_size($conn_id, $file_name) != -1;
    }

    function copyFile($conn_id, $remote_file, $local_file)
    {

        if (ftp_put($conn_id, $remote_file, $local_file, FTP_BINARY)) {
            echo "Le fichier $local_file a été chargé avec succès\n";
        } else {
            echo "Il y a eu un problème lors du chargement du fichier $local_file \n";
        }
    }
}
