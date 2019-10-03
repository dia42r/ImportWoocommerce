<?php
declare(strict_types=1);

namespace App\Dao;

/**
 * Class ProcessDao
 * @package App\Dao
 */
class ProcessDao
{
    public function save()
    {
        $queryString = sprintf(" INSERT INTO %s  (`name`, `date`) VALUES ('MAJ_SITE', :date);
", DBConfig::TB_PROCESS);

        $stmt = DbConnexion::prepare($queryString);
        $stmt->bindValue(':date', date('Y-m-d H:i:s'), \PDO::PARAM_STR);

        $stmt->execute();
    }


    /**
     * @return mixed
     */
    public function getLastExecutionDate()
    {
        $queryString = sprintf(" SELECT date FROM %s ORDER by date DESC LIMIT 1 ", DBConfig::TB_PROCESS);

        $stmt = DbConnexion::prepare($queryString);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

}
