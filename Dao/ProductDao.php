<?php
declare(strict_types=1);

namespace App\Dao;

use App\AppConfig;
use App\Entity\Product;
use App\Entity\Category;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Description of ProductDao
 *
 * @author XQJM798
 */
class ProductDao
{

    private $logger;


    public function __construct()
    {

        $this->logger =  new Logger(__CLASS__);
        /**
         * @TODO : Injecter automatiquement le logger
         */
        $this->logger->pushHandler(new StreamHandler(__CLASS__.'.log', Logger::DEBUG));
    }

    public function getAllCategories()
    {

        $categories = [];
        $queryString = "SELECT 
                            vm_category_id id,
                            libelle name,
                            description,
                            0 parent,
                            '' image
                        FROM
                            acces_sit.categoriesweb LIMIT 3;";

        $qry = DbConnexion::prepare($queryString);
        $qry->execute();

        while ($row = $qry->fetchObject(Category::class)) {
            $categories[] = $row;
        }

        return $categories;
    }


    public function getProductByCatetory($idCat)
    {
        $queryString = "SELECT 
                            LIBELLE_PRODUIT title,
                            LIBELLE_PRODUIT name,
                            'simple' type,
                            0.0 regularPrice,
                            DESC_PRODUIT description,
                            DESC_PRODUIT descriptionShort,
                            CODEEDS sku,
                            POIDS weight,
                            CODESTYLE design,
                            CODE_CATEGORIE categorie,
                            'tags' tags,
                            CONCAT_WS(', ',
                                    PHOTO,
                                    PHOTO2,
                                    PHOTO3,
                                    PHOTO4,
                                    PHOTO5) images,
                            'attributes' attributes, 
                            HAUTEUR hauteur,
                            HAUTEUR_ASSISE hauteurAssise,
                            HAUTEUR_ACCOUDOIRE hauteurAccoudoire,
                            LONGUEUR longueur,
                            LARGEUR largeur,
                            EPAISSEUR epaisseur,
                            PROFONDEUR profondeur,
                            LONGUEUR_MIN longueurMin,
                            LONGUEUR_MAX longueurMax,
                            LARGEUR_MIN largeurMin,
                            LARGEUR_MAX larguerMax,
                            CARRE_MIN carreMin,
                            CARRE_MAX carreMax,
                            DIAMETRE diametre,
                            DIAMETRE_MIN diametreMin,
                            DIAMETRE_MAX diametreMax,
                            LONGUEUR_PLATEAU longueurPlateau
                        FROM
                            produit p
                                INNER JOIN
                            produits_site ps ON p.CODEEDS = ps.REF_PRODUIT
                        WHERE
                            active = 1 AND  code_categorie_site1 = :idCat  LIMIT 1
                                                ";
        $qry = DbConnexion::prepare($queryString);

        $qry->bindValue(':idCat', $idCat, \PDO::PARAM_INT);
        $qry->execute();

        while ($row = $qry->fetchObject(Product::class)) {

            $products[] = $this->build($row);
        }

        return ($products);
    }


    public function getLocalProductsIds()
    {
        $queryString = " SELECT DISTINCT
                            REF_PRODUIT
                        FROM
                            acces_sit.produits_site
                        WHERE
                            active = 1 AND REF_PRODUIT <> ''  LIMIT 10;";

        $stmt = DbConnexion::prepare($queryString);

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);

    }


    /**
     *
     * @param array $idsProducts
     * @return array
     */
    public function getProductByIds(array $idsProducts, $lastExecutionDate)
    {
        $products = [];

        $filterDate = !empty($lastExecutionDate) ? ' AND p.DATE_MAJ >= :lastExecutionDate' : '';

        $queryString = "SELECT 
                            p.CODEEDS id,
                            LIBELLE_PRODUIT title,
                            LIBELLE_PRODUIT name,
                            'simple' type,
                            0.0 regularPrice,
                            DESC_COMPLETE description,
                            DESC_PRODUIT descriptionShort,
                            p.CODEEDS sku,
                            p.POIDS weight,
                            p.CODESTYLE design,
                            ps.CODE_CATEGORIE categorie,
                            'tags' tags,
                            CONCAT_WS(', ',
                                    p.PHOTO,
                                    p.PHOTO2,
                                    p.PHOTO3,
                                    p.PHOTO4,
                                    p.PHOTO5) images,
                            'attributes' attributes,
                            p.HAUTEUR hauteur,
                            p.HAUTEUR_ASSISE hauteurAssise,
                            p.HAUTEUR_ACCOUDOIRE hauteurAccoudoire,
                            p.LONGUEUR longueur,
                            p.LARGEUR largeur,
                            p.EPAISSEUR epaisseur,
                            p.PROFONDEUR profondeur,
                            p.LONGUEUR_MIN longueurMin,
                            p.LONGUEUR_MAX longueurMax,
                            p.LARGEUR_MIN largeurMin,
                            p.LARGEUR_MAX larguerMax,
                            p.CARRE_MIN carreMin,
                            p.CARRE_MAX carreMax,
                            p.DIAMETRE diametre,
                            p.DIAMETRE_MIN diametreMin,
                            p.DIAMETRE_MAX diametreMax,
                            p.LONGUEUR_PLATEAU longueurPlateau
                        FROM
                            produit p
                                INNER JOIN
                            produits_site ps ON p.CODEEDS = ps.REF_PRODUIT
                                LEFT JOIN
                            (SELECT 
                                p2.CODEEDS id
                            FROM
                                produit p2
                            INNER JOIN produits_site ps2 ON p2.CODEEDS = ps2.REF_PRODUIT
                                AND ps2.REF_PRODUIT IN (" . implode(',', $idsProducts).")) to_update ON p.CODEEDS = to_update.id
                        WHERE
                            ps.active = 1 AND ps.CODE_SITE = 1 " .$filterDate ;

        $this->logger->debug("Get Product", [['query'=> $queryString]]);

        $qry = DbConnexion::prepare($queryString);

        if (!empty($lastExecutionDate)) {
            $qry->bindValue(':lastExecutionDate', $lastExecutionDate, \PDO::PARAM_STR);
        }

        $qry->execute();

        while ($row = $qry->fetchObject(Product::class)) {

            $products[] = $row;
        }

        return $products;
    }
}
