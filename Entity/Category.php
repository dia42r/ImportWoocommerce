<?php
declare(strict_types=1);

namespace App\Entity;

/**
 * Class Category
 * @package App\Entity
 */
class Category
{
    /**
     *
     * @var int id
     */
    private $id;

    /**
     *
     * @var string
     */
    private $name;

    /**
     *
     * @var string
     */
    private $description;


    /**
     *
     * @var  integer
     */
    private $parent;

    /*
     * @var type array
     */
    private $image;


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return int
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return mixed
     */
    public function getImage()
    {
        return $this->images;
    }

    /**
     * @param array $categories
     * @return false|string
     */
    public static function formatDelete(array $categories)
    {
        $datas = array();
        foreach ($categories as $categorie) {

            $data = [
                    'id' => $categorie->getId() . 9999,
                ];
            array_push($datas, $data);
        }

        return json_encode($datas);

    }

    /**
     * @param $categories
     * @return false|string
     */
    public static function formatCreate($categories)
    {
        $datas = array();
        foreach ($categories as $categorie) {

            $data = [
                    //'id' => $categorie->getId() . 9999,
                    'name' => "'" .(utf8_encode($categorie->getName())) ."'",
                    //'description' => "'" . (utf8_encode($categorie->getDescription())) ."'",
                ];
            array_push($datas, $data);
        }

        return json_encode($datas);

    }

    /**
     * @return false|string
     */
    public static function formatUpdate()
    {
        $datas = [];
        return json_encode($datas);

    }
}
