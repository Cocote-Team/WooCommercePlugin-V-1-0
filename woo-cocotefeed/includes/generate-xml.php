<?php

class GenerateXml
{
    private $domtree;
    private $protocol; // 'https' or 'http'
    private $langID;
    private $xmlFile;
    private $cms;

    public function __construct()
    {
        $this->domtree = new DOMDocument('1.0', 'UTF-8');
        $this->protocol = $this->checkHTTPS();
        $this->langID = 1;
        $this->xmlFile = hash('crc32',__FILE__).'.xml';
        $this->cms = 'woocommerce';
    }
    
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
            $elements = $this->getItemInnerXmlElements($product);
            if(!$elements){
                continue;
            }
            $currentprodTemponary = $domtree->createElement("item");
            $currentprod = $xmlRoot->appendChild($currentprodTemponary);
            
            foreach($elements as $element){
                $currentprod->appendChild($element);
            }
        }

        $domtree->save($this->xmlFile);
        $path = $this->directoryXml(getcwd(). DIRECTORY_SEPARATOR .$this->xmlFile);
        return $this->xmlFile;
    }
    
    private function getItemInnerXmlElements($product_id)
    {
        $product = new WC_Product($product_id);

        $response = array();

        $response[] = new DOMElement('identifier', $product_id); // CUSTOM
        $response[] = new DOMElement('title', $product->get_name()); // REQUIS
        $response[] = new DOMElement('keywords', strip_tags(wc_get_product_category_list($product_id,'|','',''))); // REQUIS

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

        $response[] = new DOMElement('brand', $attribute_name_all); // REQUIS
        $response[] = new DOMElement('description', strip_tags($product->get_description())); // REQUIS

        $response[] = new DOMElement('gtin', '');
        if($product->get_sku() != '')
            $response[] = new DOMElement('mpn', $product->get_sku()); // REQUIS
        else
            $response[] = new DOMElement('mpn', $product_id); // REQUIS


        $categoriesAll = strtolower(strip_tags(wc_get_product_category_list($product_id,' > ','','')));
        $categoriesAll = str_replace("->-", " > ",str_replace(" ", "-", $categoriesAll));
        $response[] = new DOMElement('category', $this->enleverCaracteresSpeciaux($categoriesAll));

        $response[] = new DOMElement('link', htmlentities(get_permalink( $product->get_id() )));
        $response[] = new DOMElement('image_link', htmlentities(get_the_post_thumbnail_url( $product->get_id(), 'full' ))); // REQUIS

        $response[] = new DOMElement('price', $product->get_price());

        return $response;
    }
    
    private function checkHTTPS()
    {
        if(isset($_SERVER['HTTPS'])){
            return 'https';
        } else {
            return 'http';
        }
    }

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

    public function enleverCaracteresSpeciaux($text) {
        return str_replace( array('à','á','â','ã','ä', 'ç', 'è','é','ê','ë', 'ì','í','î','ï', 'ñ', 'ò','ó','ô','õ','ö', 'ù','ú','û','ü', 'ý','ÿ', 'À','Á','Â','Ã','Ä', 'Ç', 'È','É','Ê','Ë', 'Ì','Í','Î','Ï', 'Ñ', 'Ò','Ó','Ô','Õ','Ö', 'Ù','Ú','Û','Ü', 'Ý'),
            array('a','a','a','a','a', 'c', 'e','e','e','e', 'i','i','i','i', 'n', 'o','o','o','o','o', 'u','u','u','u', 'y','y', 'A','A','A','A','A', 'C', 'E','E','E','E', 'I','I','I','I', 'N', 'O','O','O','O','O', 'U','U','U','U', 'Y'),
            $text);
    }
}