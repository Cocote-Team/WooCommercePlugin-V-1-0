<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Cocotefeed
{
    public $name;
    public $version;
    public $submenu;
    public $action;
    public $url_plugin;
    private $url_xml_file;
    public $status_stock;

    public function __construct()
    {
        include_once plugin_dir_path( __DIR__ ).'includes'.DIRECTORY_SEPARATOR.'generate-xml.php';

        $this->name = 'Cocote feed';
        $this->version = '1.0.4';
        $this->submenu = 'cocote-submenu-page';
        $this->action = 'save-cocotefeed';
        $this->url_plugin         = dirname( plugin_dir_url( __FILE__ ) ) . '/';
        $this->status_stock = false;

        add_action('admin_menu', array($this,'config_cocotefeed_submenu_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_loaded', array($this, 'save_cocotefeed'));
        add_action('woo_cocote', array($this,'woo_cocote_generate_xml' ));
    }

    public function save_cocotefeed()
    {
        if (isset($_POST['save-only-btn']) && !empty($_POST['save-only-btn'])) {
            if (wp_verify_nonce($_POST['save-cocotefeed-verif'], $this->action)) {

                global $wpdb;

                $shop_id = $_POST['shop_id'];
                $private_key = $_POST['private_key'];
                $status_stock = false;
                if (isset($_POST['cocote_checkbox']) && !empty($_POST['cocote_checkbox']))
                    $status_stock = $_POST['cocote_checkbox'];

                $this->url_xml_file = $this->generate_cocote_Xml($status_stock);

                $row = $this->get_cocote_export();
                if (is_null($row)) {
                    // insert
                    $wpdb->insert("{$wpdb->prefix}cocote_export", array('shop_id' => $shop_id, 'private_key' => $private_key, 'export_xml' => $this->url_xml_file, 'export_status' => $status_stock));
                } else {
                    // update
                    $wpdb->update("{$wpdb->prefix}cocote_export",
                        array('shop_id' => $shop_id, 'private_key' => $private_key, 'export_xml' => $this->url_xml_file, 'export_status' => $status_stock),
                        array('id_export' => $row->id_export)
                    );
                }
            }
        }
    }

    public function config_cocotefeed_submenu_page() {
        add_submenu_page( 'woocommerce', $this->name, $this->name, 'manage_options', $this->submenu, array($this,'menu_html') );
    }

    public function menu_html()
    {
        echo "<h1 class='page-title'>Configurer</h1> ";

        echo '<form method="post" action="">';
        wp_nonce_field($this->action, 'save-cocotefeed-verif');
        echo '<fieldset class="fieldset-cocotefeed">';
        echo '<legend>'; echo get_admin_page_title(); echo '</legend>';
        settings_fields('cocote_feed_settings');

        do_settings_sections('cocote_feed_settings');
        echo '</fieldset>
            <p class="submit-cocotefeed">
                <!-- <button type="submit" id="preview-btn" class="button-secondary preview-btn" value="Export XML" title="Exporter le catalogue en fichier XML;!"><a href="<?php if(isset($this->url_xml_file) && !empty($this->url_xml_file)){echo $this->url_xml_file;} ?>" target="_blank">Export XML</a></button> -->
                <input type="submit" id="save-only-btn" name="save-only-btn" class="button-primary" value="Enregistrer" />
            </p>
        </form>';

    }

    public function register_settings()
    {
        register_setting('cocote_feed_settings', 'status');
        register_setting('cocote_feed_settings', 'nb_product');
        register_setting('cocote_feed_settings', 'shop_id');
        register_setting('cocote_feed_settings', 'private_key');
        register_setting('cocote_feed_settings', 'url_xml');
        register_setting("cocote_feed_settings", "cocote_checkbox");

        add_settings_section('cocote_feed_section', null, array($this, 'section_html'), 'cocote_feed_settings');
        add_settings_field('status', 'Status', array($this, 'status_html'), 'cocote_feed_settings', 'cocote_feed_section');
        add_settings_field('nb_product', 'Nombre de produit(s) à exporter', array($this, 'nb_product_html'), 'cocote_feed_settings', 'cocote_feed_section');
        add_settings_field('shop_id', 'Shop ID', array($this, 'shop_id_html'), 'cocote_feed_settings', 'cocote_feed_section');
        add_settings_field('private_key', 'Private Key', array($this, 'private_key_html'), 'cocote_feed_settings', 'cocote_feed_section');
        add_settings_field('cocote_checkbox', 'Exportez uniquemment les produits en stock', array($this,'checkbox_display'), 'cocote_feed_settings', 'cocote_feed_section');
        add_settings_field('url_xml', 'Lien vers le flux XML', array($this, 'url_xml'), 'cocote_feed_settings', 'cocote_feed_section');


        $resultat = check_cocote_export();
        if(isset($resultat->shop_id)) {
            update_option('shop_id', $resultat->shop_id);// = $resultat->shop_id;
            update_option('private_key', $resultat->private_key);// = $resultat->private_key;
            update_option('cocote_checkbox', $resultat->export_status);// =$resultat->export_status;
        }
    }


    public function status_html()
    {
        echo '<input type="text" name="status_html" value="'.
            $this->check_Configuration_Status().
            '" readonly="readonly" />';
    }

    public function nb_product_html()
    {
        if (isset($_POST['cocote_checkbox']) && !empty($_POST['cocote_checkbox']))
            $this->status_stock = $_POST['cocote_checkbox'];

        echo '<input type="text" name="nb_product_html" value="'.
            $this->check_nb_product($this->status_stock).
            '" readonly="readonly"/>';
    }

    public function url_xml()
    {
        $row = $this->get_cocote_export();
        if (!is_null($row)) {
            echo '<a href="'.
                $row->export_xml.
                '" target="_blank">'.
                $row->export_xml.
                '</a>';
        }
        echo '<p style="font-size: 11px;"><span>Votre flux sera réactualisé automatiquement chaque jour vers 3 heures (matin)</span><p>';
    }

    public function section_html()
    {
    }

    public function shop_id_html()
    {
        echo '<input type="text" name="shop_id" value="';

        if (isset($_POST['shop_id']) && !empty($_POST['shop_id'])) {
            echo $_POST['shop_id'];
        } else {
            echo get_option('shop_id');
        }

        echo '" required="required"/>';
        echo '<p style="font-size: 11px;"><span>Retrouvez votre identifiant depuis votre compte marchand Cocote.</span><p>';

    }

    public function private_key_html()
    {
        echo '<input type="text" name="private_key" value="';

        if (isset($_POST['private_key']) && !empty($_POST['private_key'])) {
            echo $_POST['private_key'];
        } else {
            echo get_option('private_key');
        }

        echo '" required="required"/>';
        echo '<p style="font-size: 11px;"><span>Retrouvez votre clé privée depuis votre compte marchand Cocote.</span><p>';

    }

    public function getVersion()
    {
        return $this->version;
    }

    public static function check_Configuration_Status()
    {
        // Get publish products.
        $status = 'INACTIVE';
        $args = array(
            'status' => 'publish',
        );
        $products = wc_get_products( $args );
        if (isset($products) && !empty($products)) {
            $status = 'ACTIVE';
        }

        return $status;
    }

    public static function check_nb_product($status_stock){
        // Get product ids.
        $nb_product = 0;
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
        if (isset($products) && !empty($products)) {
            $nb_product = count($products);
        }

        return $nb_product;
    }

    public function generate_cocote_Xml($status_stock)
    {
        /* Generate XML */
        $generateXml = new GenerateXml;
        $xmlName = $generateXml->initContent($status_stock);

        $url = site_url().DIRECTORY_SEPARATOR.'feed'.DIRECTORY_SEPARATOR.$xmlName;

        return $url;
    }

    public function checkbox_display()
    {

        if (isset($_POST['cocote_checkbox']) && !empty($_POST['cocote_checkbox'])) {
            $checked = $_POST['cocote_checkbox'];
        }
        else {
            $checked = get_option('cocote_checkbox');
        }

        echo '<input type="checkbox" name="cocote_checkbox" value="1"';

        if (isset($_POST['cocote_checkbox']) && !empty($_POST['cocote_checkbox']))
            checked(1, $checked, true);
        else
            checked(1, $checked, true);

        echo '/>';

    }

    public static function cron_cocote_generate_xml()
    {
        // check if scheduled hook exists
        if (! wp_next_scheduled ( 'woo_cocote' )) {
            date_default_timezone_set('Europe/Paris');
            wp_schedule_event(mktime(3, 0, 0, 1, 15, 2019), 'daily', 'woo_cocote');
        }
    }

    public function woo_cocote_generate_xml() {
        // generate xml by cron
        $row = $this->get_cocote_export();
        if (is_null($row)) {
            $cron_status_stock = $this->status_stock;
        } else {
            $cron_status_stock = $row->export_status;
        }
        $this->generate_cocote_Xml($cron_status_stock);
    }

    public function get_cocote_export(){
        global $wpdb;

        $row = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}cocote_export WHERE 1");

        return $row;
    }
}