<?php

/**
 * Class GenerateXml
 */
class GenerateXml
{
    private $domtree;
    private $protocol; // 'https' or 'http'
    private $langID;
    private $xmlFile;
    private $cms;

    /**
     * GenerateXml constructor.
     */
    public function __construct()
    {
        $this->domtree = new DOMDocument('1.0', 'UTF-8');
        $this->protocol = $this->checkHTTPS();
        $this->langID = 1;
        $this->xmlFile = hash('crc32',__FILE__).'.xml';
        $this->cms = 'woocommerce';
    }

    /**
     * @param $status_stock
     * @return string
     */
    public function initContent($status_stock)
    {
        if(Cocotefeed::check_Configuration_Status() !== 'ACTIVE'){
            die();
        }

        // Get product ids.
        if($status_stock) {
            $args = array(
                'limit' => -1,
                'status' => 'publish',
                'return' => 'ids',
            );
        }else{
            $args = array(
                'limit' => -1,
                'return' => 'ids',
            );
        }
        
        $products = wc_get_products( $args );
        
        $domtree = new DOMDocument('1.0', 'UTF-8');

        $root= $domtree->createElement("shop");
        $xmlRoot = $domtree->appendChild($root);

        $generated = $domtree->createElement('generated');
        $attr = $domtree->createAttribute('cms');
        $attr->value = $this->cms;

        $attr2 = $domtree->createAttribute('plugin_version');
        $attr2->value = $this->wpbo_get_woo_version_number();
        $generated->appendChild($attr2);

        $generated->appendChild($attr);
        $domtree->appendChild($generated);
        $generated = $root->appendChild($generated);
        $text = $domtree->createTextNode(date('Y-m-d H:i:s'));
        $text = $generated->appendChild($text);

        $xmlRootTemponary = $domtree->createElement("offers");
        $xmlRoot = $xmlRoot->appendChild($xmlRootTemponary);

        foreach($products as $product){
            $this->getItemInnerXmlElements($product, $domtree);
        }

        $domtree->save($this->xmlFile);
        $path = $this->directoryXml(getcwd(). DIRECTORY_SEPARATOR .$this->xmlFile);
        return $this->xmlFile;
    }

    /**
     * @param int $product_id
     * @param DOMDocument $domtree
     */
    private function getItemInnerXmlElements($product_id, $domtree)
    {
        $product = new WC_Product($product_id);

        $attribute_name_all = '';
        foreach ($product->get_attributes() as $attribute) {
            $attribute_name = $attribute['name']; $i = 0;
            if (substr($attribute_name, 0, 3) == 'pa_') {
                $attribute_name = substr($attribute_name, 3, strlen($attribute_name));
                if($i == 0) {
                    $attribute_name_all .= $attribute_name;
                }else {
                    if ($attribute_name != '') {
                        $attribute_name_all .= '|'.$attribute_name;
                    }
                }
                $i++;
            }
        }

        $offers = $domtree->getElementsByTagName('offers')->item(0);

        $currentprod = $domtree->createElement('item');
        $offers->appendChild($currentprod);

        $currentprod->appendChild($domtree->createElement('identifier', $product_id));
        $currentprod->appendChild($domtree->createElement('link', htmlentities(get_permalink( $product->get_id() ))));
        $currentprod->appendChild($domtree->createElement('keywords', strip_tags(wc_get_product_category_list($product_id,'|','',''))));
        $currentprod->appendChild($domtree->createElement('brand', $attribute_name_all));

        $descTag = $domtree->createElement('description');
        $descTag->appendChild($domtree->createCDATASection(strip_tags($product->get_description())));
        $currentprod->appendChild($descTag);

        $currentprod->appendChild($domtree->createElement('image_link', htmlentities(get_the_post_thumbnail_url( $product->get_id(), 'full' ))));
        $currentprod->appendChild($domtree->createElement('price', $product->get_price()));
        $currentprod->appendChild($domtree->createElement('gtin', ""));

        if($product->get_sku() != '')
            $currentprod->appendChild($domtree->createElement('mpn', $product->get_sku()));
        else
            $currentprod->appendChild($domtree->createElement('mpn', $product_id));

        $categoriesAll = strtolower(strip_tags(wc_get_product_category_list($product_id,' > ','','')));
        $categoriesAll = str_replace("->-", " > ",str_replace(" ", "-", $categoriesAll));

        $currentprod->appendChild($domtree->createElement('category', $this->enleverCaracteresSpeciaux($categoriesAll)));

    }

    /**
     * @return string
     */
    private function checkHTTPS()
    {
        if(isset($_SERVER['HTTPS'])){
            return 'https';
        } else {
            return 'http';
        }
    }

    /**
     * @param $xmlFileLocal
     * @return string
     */
    private function directoryXml($xmlFileLocal){
        chdir( WP_CONTENT_DIR );
        chdir('..');
        if(!file_exists('feed')){
            mkdir ('feed');
        }
        $path = getcwd(). DIRECTORY_SEPARATOR .'feed' . DIRECTORY_SEPARATOR .$this->xmlFile;
        rename($xmlFileLocal, $path);

        return $path;
    }

    /**
     * @return string|null
     */
    private function wpbo_get_woo_version_number() {
        // If get_plugins() isn't available, require it
        if ( ! function_exists( 'get_plugins' ) )
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        // Create the plugins folder and file variables
        $plugin_folder = get_plugins( '/' . 'woocommerce' );
        $plugin_file = 'woocommerce.php';

        // If the plugin version number is set, return it
        if ( isset( $plugin_folder[$plugin_file]['Version'] ) ) {
            return $plugin_folder[$plugin_file]['Version'];

        } else {
            // Otherwise return null
            return NULL;
        }
    }

    /**
     * @param $text
     * @return mixed
     */
    public function enleverCaracteresSpeciaux($text) {
        return str_replace( array('à','á','â','ã','ä', 'ç', 'è','é','ê','ë', 'ì','í','î','ï', 'ñ', 'ò','ó','ô','õ','ö', 'ù','ú','û','ü', 'ý','ÿ', 'À','Á','Â','Ã','Ä', 'Ç', 'È','É','Ê','Ë', 'Ì','Í','Î','Ï', 'Ñ', 'Ò','Ó','Ô','Õ','Ö', 'Ù','Ú','Û','Ü', 'Ý'),
            array('a','a','a','a','a', 'c', 'e','e','e','e', 'i','i','i','i', 'n', 'o','o','o','o','o', 'u','u','u','u', 'y','y', 'A','A','A','A','A', 'C', 'E','E','E','E', 'I','I','I','I', 'N', 'O','O','O','O','O', 'U','U','U','U', 'Y'),
            $text);
    }
}