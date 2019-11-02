<?php
declare(strict_types=1);

namespace App\Dao;

use App\AppConfig;
use App\Entity\Product;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Description of ProductDao
 * @author XQJM798
 */
class ProductDao
{
    private $logger;

    private $tablename = "acces_sit.produit";

    public function __construct()
    {
        $this->logger =  new Logger(__CLASS__);
        /**
         * @TODO : Injecter automatiquement le logger
         */
        $this->logger->pushHandler(new StreamHandler(__CLASS__.'.log', Logger::DEBUG));
    }

    /**
     * @return mixed
     */
    public function getLocalProductsIds()
    {
        $queryString = " SELECT 
                            REF_PRODUIT
                        FROM
                            acces_sit.produits_site
                        WHERE CODE_SITE = 1 and   active = 1 AND REF_PRODUIT <> '';";

        $stmt = DbConnexion::prepare($queryString);

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);

    }

    /**
     * @param string $lastUpdateDate
     * @return array
     */
    public function getLastUpdProduct(string $lastUpdateDate)
    {
        $products = [];

        $queryString = " SELECT 
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
                            p.LONGUEUR_PLATEAU longueurPlateau,
                            p.COLLECTION collection
                        FROM
                            produit p
                                INNER JOIN
                            produits_site ps ON p.CODEEDS = ps.REF_PRODUIT 
                         WHERE DATE_MAJ  >= :lastUpdDate";

        $stmt= DbConnexion::prepare($queryString);
        $stmt->bindValue(':lastUpdDate', $lastUpdateDate);


        $stmt->execute();

        while ($row = $stmt->fetchObject(Product::class)) {

            $products[] = $row;
        }

        return $products;
    }

    /**
     * @param $collection
     * @return mixed
     */
    public function getRelatedCollectionProductIds($collection, $codeEds)
    {
        $queryString = "SELECT 
                          p.CODEEDS
                        FROM
                          produit  p
                        INNER JOIN
                          produits_site ps ON p.CODEEDS = ps.REF_PRODUIT
                        WHERE
                          COLLECTION = :collection
                        AND CODEEDS <> :codeEds
                        AND CODEEDS <> ''
                        AND ps.CODE_SITE = 1;";


        $stmt = DbConnexion::prepare($queryString);

        $stmt->bindValue(':collection', $collection, \PDO::PARAM_STR);
        $stmt->bindValue(':codeEds', $codeEds, \PDO::PARAM_STR);
        $stmt->execute();


        return array_column($stmt->fetchAll(), 'CODEEDS');
    }
}
