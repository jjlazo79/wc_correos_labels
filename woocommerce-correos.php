<?php 
/*
Plugin Name: WooCommerce Correos
Plugin URI:  https://gogood.com
Description: Integración del WebService de Correos España
Version:     0.1
Author:      Sngular Team
Author URI:  https://wordpress.org/joselazo
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: correos
Domain Path: /languages
License:     GPL2
 
Report Builder is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
Report Builder is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Report Builder. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/

if ( ! defined( 'WPINC' ) ) {
 
    die;
 
}
 
/*
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
 
    function correos_shipping_method() {
        if ( ! class_exists( 'Correos_Shipping_Method' ) ) {
            class Correos_Shipping_Method extends WC_Shipping_Method {
                public static $field_name           = 'title';
                public static $field_shipping_zones = 'zones';
                public static $field_description    = 'desc';
                public static $field_weight         = 'weight';

                /**
                 * Constructor for your shipping class
                 *
                 * @access public
                 * @return void
                 */
                public function __construct() {
                    $this->id                 = 'correos'; 
                    $this->method_title       = __( 'Correos Shipping', 'correos' );  
                    $this->method_description = __( 'Custom Shipping Method for Correos', 'correos' ); 
 
                    // Availability & Countries
                    $this->availability = 'including';
                    $this->countries    = array(
                        // 'US', // Unites States of America
                        // 'CA', // Canada
                        // 'DE', // Germany
                        // 'GB', // United Kingdom
                        // 'IT',   // Italy
                        'ES', // Spain
                        // 'HR'  // Croatia
                        );
 
                    $this->init();
 
                    $this->enabled  = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'yes';
                    $this->title    = isset( $this->settings['title'] ) ? $this->settings['title'] : __( 'Correos Shipping', 'correos' );
                    $this->desc     = isset( $this->settings['desc'] ) ? $this->settings['desc'] : __( 'Correos Shipping', 'correos' );
                    $this->weight   = isset( $this->settings['weight'] ) ? $this->settings['weight'] : __( 'Maximum weight', 'correos' );
                }
 
                /**
                 * Init your settings
                 *
                 * @access public
                 * @return void
                 */
                function init() {
                    // Load the settings API
                    $this->init_form_fields(); 
                    $this->init_settings(); 
 
                    // Save settings in admin if you have any defined
                    add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
                }

                public static function get_zone_by_name($zone_name) {
                    global $wpdb;
                    
                    $query = "SELECT zone_id FROM ".$wpdb->prefix."woocommerce_shipping_zones WHERE zone_name = '$zone_name';";
                    $result = $wpdb->get_results($query);
                    
                    if (is_array($result) && !empty($result)) {
                        $result = new WC_Shipping_Zone($result[0]->zone_id);
                    } else {
                        $result = false;
                    }
                    
                    return $result;
                }
 
                /**
                 * Define settings field for this shipping
                 * @return void 
                 */
                function init_form_fields() {
                    $delivery_zones = WC_Shipping_Zones::get_zones();
                    foreach ((array) $delivery_zones as $key => $the_zone ) {

                        $zone_options[ $the_zone['id'] ] = $the_zone['zone_name'];
                        // echo $the_zone['zone_name'];

                    }
 
                    $this->form_fields = array(
 
                        'enabled' => array(
                            'title'       => __( 'Enable', 'correos' ),
                            'type'        => 'checkbox',
                            'description' => __( 'Enable this shipping.', 'correos' ),
                            'default'     => 'yes'
                        ),
                        self::$field_name => array(
                            'title'         => __( 'Nombre del transportista', 'correos' ),
                            'type'          => 'text',
                            'description'   => __( 'Nombre del transportista mostrado en el frontend', 'correos' ),
                            'default'       => 'Shipping name'
                        ),
                        self::$field_description => array(
                            'title'         => __('Descripción', 'correos'),
                            'type'          => 'text',
                            'description'   => __('Descripción del transportista', 'correos'),
                            'default'       => 'description'
                        ),
                        self::$field_shipping_zones => array(
                            'title'             => 'Zonas de envío',
                            'type'              => 'multiselect',
                            'description'       => 'Zonas de envío del transportista',
                            'options'           => $zone_options,
                            'desc_tip'          => true,
                            'select_buttons'    => true,
                            'default'           => ''
                        ),
                        self::$field_weight => array(
                            'title'         => __('Peso máximo', 'correos'),
                            'type'          => 'number',
                            'description'   => __('Peso máximo admitido por el transportista', 'correos'),
                            'default'       => '0.5'
                        )
 
                     );
 
                }
 
                /**
                 * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
                 *
                 * @access public
                 * @param mixed $package
                 * @return void
                 */
                public function calculate_shipping( $package = array() ) {
                    
                    $weight     = 0;
                    $cost       = 0;
                    $country    = $package["destination"]["country"];

                    foreach ( $package['contents'] as $item_id => $values ) 
                    { 
                        $_product   = $values['data']; 
                        $weight     = $weight + $_product->get_weight() * $values['quantity']; 
                    }
 
                    $weight = wc_get_weight( $weight, 'kg' );
 
                    if( $weight <= 10 ) {
 
                        $cost = 0;
 
                    } elseif( $weight <= 30 ) {
 
                        $cost = 5;
 
                    } elseif( $weight <= 50 ) {
 
                        $cost = 10;
 
                    } else {
 
                        $cost = 0;
 
                    }
 
                    $countryZones = array(
                        'HR' => 1,
                        'US' => 3,
                        'GB' => 2,
                        'CA' => 3,
                        'ES' => 0,
                        'DE' => 1,
                        'IT' => 1
                        );
 
                    $zonePrices = array(
                        0 => 0,
                        1 => 30,
                        2 => 50,
                        3 => 70
                        );
 
                    $zoneFromCountry    = $countryZones[ $country ];
                    $priceFromZone      = $zonePrices[ $zoneFromCountry ];
 
                    $cost += $priceFromZone;
 
                    $rate = array(
                        'id'    => $this->id,
                        'label' => $this->title,
                        'cost'  => $cost
                    );
 
                    $this->add_rate( $rate );
                    
                }
            }
        }
    } // end correos_shipping_method
    add_action( 'woocommerce_shipping_init', 'correos_shipping_method' );
 
    function add_correos_shipping_method( $methods ) {
        $methods[] = 'Correos_Shipping_Method';
        return $methods;
    }
    add_filter( 'woocommerce_shipping_methods', 'add_correos_shipping_method' );
 
    function correos_validate_order( $posted )   {
 
        $packages       = WC()->shipping->get_packages();
 
        $chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
         
        if( is_array( $chosen_methods ) && in_array( 'correos', $chosen_methods ) ) {
             
            foreach ( $packages as $i => $package ) {
 
                if ( $chosen_methods[ $i ] != "correos" ) {
                             
                    continue;          
                }
 
                $Correos_Shipping_Method    = new Correos_Shipping_Method();
                $weightLimit                = (int) $Correos_Shipping_Method->settings['weight'];
                $weight                     = 0;
 
                foreach ( $package['contents'] as $item_id => $values ) 
                { 
                    $_product   = $values['data']; 
                    $weight     = $weight + $_product->get_weight() * $values['quantity']; 
                }
 
                $weight = wc_get_weight( $weight, 'kg' );
                
                if( $weight > $weightLimit ) {
 
                        $message = sprintf( __( 'Sorry, %d kg exceeds the maximum weight of %d kg for %s', 'correos' ), $weight, $weightLimit, $Correos_Shipping_Method->title );
                             
                        $messageType = "error";
 
                        if( ! wc_has_notice( $message, $messageType ) ) {
                         
                            wc_add_notice( $message, $messageType );
                      
                        }
                }
            }       
        } 
    }
    add_action( 'woocommerce_review_order_before_cart_contents', 'correos_validate_order' , 10 );
    add_action( 'woocommerce_after_checkout_validation', 'correos_validate_order' , 10 );
}

// On activation
register_activation_hook( __FILE__, 'correosServices_install' );
register_activation_hook( __FILE__, 'correosServices_install_data' );
// On deactivation
register_deactivation_hook( __FILE__, 'correosServices_deactivation' );
// On uninstall
register_uninstall_hook(__FILE__, 'correosServices_uninstall');



global $correos_db_version;
$correos_db_version  = '1.0';

// Create tables
function correosServices_install() {
  global $wpdb;
  global $correos_db_version;

  $installed_version = get_option('correos_db_option');

  // if ($installed_version !== $correos_db_version) {

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    $charset_collate  = $wpdb->get_charset_collate();

    $table_name1 = $wpdb->prefix . 'correos_carrier';
    $table_name2 = $wpdb->prefix . 'correos_preregister';
    $table_name3 = $wpdb->prefix . 'correos_preregister_errors';
    $table_name4 = $wpdb->prefix . 'correos_configuration';
    $table_name5 = $wpdb->prefix . 'correos_request';

    // Table 1 'correos_carrier'
    $sql = "DROP TABLE IF EXISTS $table_name1";
    dbDelta( $sql );

    $sql = "CREATE TABLE IF NOT EXISTS $table_name1 (
      code varchar(5) NOT NULL,
      title varchar(50) NOT NULL,
      delay varchar(80) NOT NULL,
      id_reference int(10) unsigned NOT NULL,
      PRIMARY KEY (code)
    ) $charset_collate;";
    dbDelta( $sql );


    // Table 2 'correos_preregister'
    $sql = "DROP TABLE IF EXISTS $table_name2";
    dbDelta( $sql );

    $sql = "CREATE TABLE IF NOT EXISTS $table_name2 (
      id int(10) unsigned NOT NULL AUTO_INCREMENT,
      id_order int(10) unsigned NOT NULL,
      id_carrier int(10) unsigned NOT NULL,
      carrier_code varchar(5) NOT NULL,
      code_expedition varchar(16) DEFAULT NULL,
      date_response datetime NOT NULL,
      shipment_code varchar(23) DEFAULT NULL,
      shipment_customs_code varchar(23) DEFAULT NULL,
      label_printed timestamp NULL DEFAULT NULL,
      exported timestamp NULL DEFAULT NULL,
      manifest timestamp NULL DEFAULT NULL,
      weight decimal(17,2) NOT NULL DEFAULT '0.00',
      insurance decimal(17,2) NOT NULL DEFAULT '0.00',
      collection_request timestamp NULL DEFAULT NULL,
      PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta( $sql );


    // Table 3 'correos_preregister_errors'
    $sql = "DROP TABLE IF EXISTS $table_name3";
    dbDelta( $sql );

    $sql = "CREATE TABLE IF NOT EXISTS $table_name3 (
      id_order int(10) unsigned NOT NULL,
      error varchar(255) DEFAULT NULL,
       date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";
    dbDelta( $sql );


    // Table 4 'correos_configuration'
    $sql = "DROP TABLE IF EXISTS $table_name4";
    dbDelta( $sql );

    $sql = "CREATE TABLE IF NOT EXISTS $table_name4 (
      name varchar(50) NOT NULL,
      value text,
      PRIMARY KEY (name)
    ) $charset_collate;";
    dbDelta( $sql );


    // Table 5 'correos_request'
    $sql = "DROP TABLE IF EXISTS $table_name5";
    dbDelta( $sql );

    $sql = "CREATE TABLE IF NOT EXISTS $table_name5 (
      id int(10) unsigned NOT NULL AUTO_INCREMENT,
      type enum('quote','order') COLLATE utf8_spanish_ci NOT NULL DEFAULT 'quote',
      id_cart int(10) unsigned NOT NULL DEFAULT '0',
      id_order int(10) unsigned NOT NULL,
      id_carrier int(10) unsigned NOT NULL,
      reference varchar(255) COLLATE utf8_spanish_ci DEFAULT NULL,
      data text COLLATE utf8_spanish_ci NOT NULL,
      date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      UNIQUE KEY id_cart (id_cart,id_carrier)
    ) $charset_collate;";
    dbDelta( $sql );

    update_option('correos_db_version', $correos_db_version);
  // }// end if version is diferent

}// End function correosServices_install


function correosServices_install_data() {
  global $wpdb;
  
  $table_name_data1 = $wpdb->prefix . 'correos_carrier';
  $table_name_data2 = $wpdb->prefix . 'correos_configuration';
  // Insert into table 1 'correos_carrier'
  $wpdb->insert( $table_name_data1, array(
      'code'            => 'S0030',
      'title'           => 'Paquete Postal Internacional Prioritario',
      'delay'           => 'Entrega a domicilio hasta 7 días',
      'id_reference'    => 0, )
  );
  $wpdb->insert( $table_name_data1, array(
      'code'            => 'S0033',
      'title'           => 'Postal EXPRÉS Nacional',
      'delay'           => 'Entrega a domicilio en 1-2 días',
      'id_reference'    => 0, )
  );
  $wpdb->insert( $table_name_data1, array(
      'code'            => 'S0034',
      'title'           => 'Postal EXPRÉS Internacional',
      'delay'           => 'Entrega a domicilio hasta 5 días',
      'id_reference'    => 0, )
  );
  $wpdb->insert( $table_name_data1, array(
      'code'            => 'S0132',
      'title'           => 'Paq 72h Domicilio',
      'delay'           => 'Entrega a domicilio en 2-3 días',
      'id_reference'    => 0, )
  );
  $wpdb->insert( $table_name_data1, array(
      'code'            => 'S0133',
      'title'           => 'Paq 72h Oficina',
      'delay'           => 'Recogida en la oficina que Vd. elija (2-3 días)',
      'id_reference'    => 0, )
  );
  $wpdb->insert( $table_name_data1, array(
      'code'            => 'S0138',
      'title'           => 'DUA Exportación Nacional',
      'delay'           => 'Entrega a domicilio en 3-4 día',
      'id_reference'    => 0, )
  );
  $wpdb->insert( $table_name_data1, array(
      'code'            => 'S0175',
      'title'           => 'Paq 48h CorreosPaq',
      'delay'           => 'Entrega en CorreosPaq en 1-2 días',
      'id_reference'    => 0, )
  );
  $wpdb->insert( $table_name_data1, array(
      'code'            => 'S0176',
      'title'           => 'Paq 48h CorreosPaq',
      'delay'           => 'Entrega a domicilio en 1-2 días',
      'id_reference'    => 0, )
  );
  $wpdb->insert( $table_name_data1, array(
      'code'            => 'S0177',
      'title'           => 'Paq 72h CorreosPaq',
      'delay'           => 'Entrega en HomePaq o CityPaq en 2-3 días',
      'id_reference'    => 0, )
  );
  $wpdb->insert( $table_name_data1, array(
      'code'            => 'S0178',
      'title'           => 'Paq 72h entrega en CityPaq',
      'delay'           => 'Entrega a domicilio en 2-3 días',
      'id_reference'    => 0, )
  );
  $wpdb->insert( $table_name_data1, array(
      'code'            => 'S0198',
      'title'           => 'Postal EXPRÉS LISTA',
      'delay'           => 'Entrega a domicilio en 2-3 días',
      'id_reference'    => 0, )
  );
  $wpdb->insert( $table_name_data1, array(
      'code'            => 'S0235',
      'title'           => 'Paq 48h Domicilio',
      'delay'           => 'Entrega a domicilio en 1-2 días',
      'id_reference'    => 0, )
  );
  $wpdb->insert( $table_name_data1, array(
      'code'            => 'S0236',
      'title'           => 'Paq 48h Oficina',
      'delay'           => 'Recogida en la oficina que Vd. elija (1-2 días)',
      'id_reference'    => 0, )
  );

  //Insert into table 4 'correos_configuration'
  $wpdb->insert( $table_name_data2, array(
      'name'            => 'cashondelivery_modules',
      'value'           => 'cashondelivery,megareembolso,codfee,reembolsocargo,cashondeliveryplus,cashondeliveryfee', )
  );
  $wpdb->insert( $table_name_data2, array(
      'name'            => 'S0236_postalcodes',
      'value'           => '05001,01015,02071,03005,03202,04005,06007,07011,07700,07800,08010,08012,08016,08020,08027,08029,08034,08038,08205,08221,08301,08905,08911,08921,09001,10001,11011,11202,11404,11500,12001,13003,14005,15001,15402,15770,16071,17005,18011,19001,20018,21007,22006,23009,24004,25005,26007,27003,28002,28005,28007,28008,28015,28016,28022,28023,28025,28039,28041,28043,28100,28806,28905,28910,28923,28931,28941,29004,29018,30100,30201,31013,32001,33011,33206,34070,35014,36004,36210,37001,38108,39002,40001,41007,42001,43001,43202,44003,45003,45600,46007,46010,47001,48003,49003,50004,51001,52001', )
  );
  $wpdb->insert( $table_name_data2, array(
      'name'            => 'url_data',
      'value'           => 'https://preregistroenvios.correos.es/preregistroenvios', )
  );
  $wpdb->insert( $table_name_data2, array(
      'name'            => 'url_data_pre',
      'value'           => 'https://preregistroenviospre.correos.es/preregistroenvios', )
  );
  $wpdb->insert( $table_name_data2, array(
      'name'            => 'url_homepaq',
      'value'           => 'https://online.correospaq.es/correospaqws/HomepaqWSService', )
  );
  $wpdb->insert( $table_name_data2, array(
      'name'            => 'url_homepaq_pre',
      'value'           => 'https://onlinepre.correospaq.es/correospaqws/HomepaqWSService', )
  );
  $wpdb->insert( $table_name_data2, array(
      'name'            => 'url_office_locator',
      'value'           => 'http://localizadoroficinas.correos.es/localizadoroficinas', )
  );
  $wpdb->insert( $table_name_data2, array(
      'name'            => 'url_office_locator_pre',
      'value'           => 'http://localizadoroficinaspre.correos.es/localizadoroficinas', )
  );
  $wpdb->insert( $table_name_data2, array(
      'name'            => 'url_servicepaq',
      'value'           => 'https://online.correospaq.es/correospaqws/CorreospaqService', )
  );
  $wpdb->insert( $table_name_data2, array(
      'name'            => 'url_servicepaq_pre',
      'value'           => 'https://onlinepre.correospaq.es/correospaqws/CorreospaqService', )
  );
  $wpdb->insert( $table_name_data2, array(
      'name'            => 'url_tracking',
      'value'           => 'https://online.correos.es/servicioswebLocalizacionMI/localizacionMI.asmx', )
  );

  //Create delivery methods
  


} // end function correosServices_install_data


// On deactivation
function correosServices_deactivation() {
  // Flush caché
}

// On uninstall
function correosServices_uninstall() {
  global $wpdb;

  $sql = "DROP TABLE IF EXISTS " . $wpdb->prefix . "correos_carrier"; $wpdb->query($sql); 
  $sql = "DROP TABLE IF EXISTS " . $wpdb->prefix . "correos_configuration"; $wpdb->query($sql); 
  $sql = "DROP TABLE IF EXISTS " . $wpdb->prefix . "correos_preregister"; $wpdb->query($sql); 
  $sql = "DROP TABLE IF EXISTS " . $wpdb->prefix . "correos_preregister_errors"; $wpdb->query($sql); 
  $sql = "DROP TABLE IF EXISTS " . $wpdb->prefix . "correos_request"; $wpdb->query($sql); 

  delete_option('correos_db_option');
}





// SETTINGS
// create custom plugin settings menu
add_action('admin_menu', 'correos_plugin_setup_menu');

function correos_plugin_setup_menu() {

  //create new top-level menu
  add_menu_page('Correos Settings', 'Correos', 'administrator', __FILE__, 'correos_service_init' , 'dashicons-store' );

  //call register settings function
  add_action( 'admin_init', 'register_correos_plugin_settings' );
}//correos_plugin_setup_menu

function register_correos_plugin_settings() {
  //register our settings
  register_setting( 'correos-plugin-settings-group', 'correos_environment' );
  register_setting( 'correos-plugin-settings-group', 'correos_user' );
  register_setting( 'correos-plugin-settings-group', 'correos_password' );
  register_setting( 'correos-plugin-settings-group', 'correos_ce' );
  register_setting( 'correos-plugin-settings-group', 'correos_numcontrato' );
  register_setting( 'correos-plugin-settings-group', 'correos_numcliente' );
  register_setting( 'correos-plugin-settings-group', 'codigo_producto' );

  register_setting( 'correos-plugin-settings-group', 'correos_nombreremite' );
  register_setting( 'correos-plugin-settings-group', 'correos_apellido1remite' );
  register_setting( 'correos-plugin-settings-group', 'correos_apellido2remite' );
  register_setting( 'correos-plugin-settings-group', 'correos_empresaremite' );
  register_setting( 'correos-plugin-settings-group', 'correos_contactoremite' );
  register_setting( 'correos-plugin-settings-group', 'correos_nifremite' );
  register_setting( 'correos-plugin-settings-group', 'correos_direccionremite' );
  register_setting( 'correos-plugin-settings-group', 'correos_localidadremite' );
  register_setting( 'correos-plugin-settings-group', 'correos_provinciaremite' );
  register_setting( 'correos-plugin-settings-group', 'correos_cpremite' );
  register_setting( 'correos-plugin-settings-group', 'correos_telremite' );
  register_setting( 'correos-plugin-settings-group', 'correos_emailremite' );
  register_setting( 'correos-plugin-settings-group', 'correos_emailmanipulado' );
}

function correos_service_init() {
  ?>
  <div class="wrap">
    <h1><?php _e( 'Integración con API de Correos', 'correos' ); ?></h1>

    <form method="post" action="options.php">
      <?php settings_fields( 'correos-plugin-settings-group' ); ?>
      <?php do_settings_sections( 'correos-plugin-settings-group' ); ?>
      
      <h2><?php _e( 'Datos de Correos', 'correos' ); ?></h2><hr>

      <table class="form-table">

          <tr valign="top">
          <th scope="row"><?php _e( 'Entorno de trabajo', 'correos' ); ?></th>
          <td>
            <select id="correos_environment" name="correos_environment">
              <option value=""><?php _e( 'Elige un entorno', 'correos' ); ?></option>
              <?php 
              global $wpdb;
              $results = $wpdb->get_results( "SELECT value FROM {$wpdb->prefix}correos_configuration WHERE name LIKE 'url_data' OR name LIKE 'url_data_pre'" );
              $iter = 0;
              foreach ($results as $result) {
                if ($iter != 0) {
                  $correos_environment = 'PREproducción';
                } else {
                  $correos_environment = 'PRODUCCIÓN';
                }
                $iter++;
                $selected = (get_option('correos_environment') === $result->value) ? 'selected="selected"' : '' ;
                echo '<option value="' .$result->value. '" ' . $selected . '>' .$correos_environment. '</option>';
              } ?>
            </select>
          </td>
          </tr>

          <tr valign="top">
          <th scope="row"><?php _e( 'Usuario de Correos', 'correos' ); ?></th>
          <td><input type="text" name="correos_user" value="<?php echo esc_attr( get_option('correos_user') ); ?>" /></td>
          </tr>
           
          <tr valign="top">
          <th scope="row"><?php _e( 'Contraseña de Correos', 'correos' ); ?></th>
          <td><input type="password" name="correos_password" value="<?php echo esc_attr( get_option('correos_password') ); ?>" /></td>
          </tr>
          
          <tr valign="top">
          <th scope="row"><?php _e( 'Código Etiquetadora', 'correos' ); ?></th>
          <td><input type="text" name="correos_ce" value="<?php echo esc_attr( get_option('correos_ce') ); ?>" /></td>
          </tr>
          
          <tr valign="top">
          <th scope="row"><?php _e( 'Número de contrato', 'correos' ); ?></th>
          <td><input type="text" name="correos_numcontrato" value="<?php echo esc_attr( get_option('correos_numcontrato') ); ?>" /> <span><?php _e( ' No necesario si se introduce el Código de la etiquetadora', 'correos' ); ?></span></td>
          </tr>
          
          <tr valign="top">
          <th scope="row"><?php _e( 'Número de cliente', 'correos' ); ?></th>
          <td><input type="text" name="correos_numcliente" value="<?php echo esc_attr( get_option('correos_numcliente') ); ?>" /> <span><?php _e( ' No necesario si se introduce el Código de la etiquetadora', 'correos' ); ?></span></td>
          </tr>
          
          <tr valign="top">
          <th scope="row"><?php _e( 'Email empresa manipulado', 'correos' ); ?></th>
          <td><input type="text" name="correos_emailmanipulado" value="<?php echo esc_attr( get_option('correos_emailmanipulado') ); ?>" /> <span><?php _e( ' Separar con comas si son varios correos. NOTA: También se envía al correo de administración de WordPress', 'correos' ); ?></span></td>
          </tr>
          
          <tr valign="top">
          <th scope="row"><?php _e( 'Código de producto', 'correos' ); ?></th>
          <td>
            <select id="codigo_producto" name="codigo_producto">
              <option value=""><?php _e( 'Elige un producto', 'correos' ); ?></option>
              <?php 
              global $wpdb;
              $results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}correos_carrier" );
              foreach ($results as $result) { 
                $selected = (get_option('codigo_producto') === $result->code) ? 'selected="selected"' : '' ;
                echo '<option value="' .$result->code. '" ' . $selected . '>' .$result->title. '</option>';
              } ?>
            </select>
          </td>
          </tr>
      </table>

      <h2><?php _e( 'Datos del remitente', 'correos' ); ?></h2><hr>

      <table class="form-table">
          <tr valign="top">
          <th scope="row"><?php _e( 'Nombre de la empresa', 'correos' ); ?></th>
          <td><input type="text" name="correos_empresaremite" value="<?php echo esc_attr( get_option('correos_empresaremite') ); ?>" /></td>
          </tr>

          <tr valign="top">
          <th scope="row"><?php _e( 'NIF de la empresa', 'correos' ); ?></th>
          <td><input type="text" name="correos_nifremite" value="<?php echo esc_attr( get_option('correos_nifremite') ); ?>" /></td>
          </tr>
          
          <tr valign="top">
          <th scope="row"><?php _e( 'Nombre de la persona de contacto', 'correos' ); ?></th>
          <td><input type="text" name="correos_contactoremite" value="<?php echo esc_attr( get_option('correos_contactoremite') ); ?>" /></td>
          </tr>
          
          <tr valign="top">
          <th scope="row"><?php _e( 'Teléfono de contacto', 'correos' ); ?></th>
          <td><input type="text" name="correos_telremite" value="<?php echo esc_attr( get_option('correos_telremite') ); ?>" /></td>
          </tr>

          <tr valign="top">
          <th scope="row"><?php _e( 'Dirección del remitente', 'correos' ); ?></th>
          <td><input type="text" name="correos_direccionremite" value="<?php echo esc_attr( get_option('correos_direccionremite') ); ?>" /></td>
          </tr>

          <tr valign="top">
          <th scope="row"><?php _e( 'Localidad del remitente', 'correos' ); ?></th>
          <td><input type="text" name="correos_localidadremite" value="<?php echo esc_attr( get_option('correos_localidadremite') ); ?>" /></td>
          </tr>

          <tr valign="top">
          <th scope="row"><?php _e( 'Provincia del remitente', 'correos' ); ?></th>
          <td><input type="text" name="correos_provinciaremite" value="<?php echo esc_attr( get_option('correos_provinciaremite') ); ?>" /></td>
          </tr>

          <tr valign="top">
          <th scope="row"><?php _e( 'Código Postal del remitente', 'correos' ); ?></th>
          <td><input type="text" name="correos_cpremite" value="<?php echo esc_attr( get_option('correos_cpremite') ); ?>" /></td>
          </tr>

          <tr valign="top">
          <th scope="row"><?php _e( 'Email del remitente', 'correos' ); ?></th>
          <td><input type="text" name="correos_emailremite" value="<?php echo esc_attr( get_option('correos_emailremite') ); ?>" /></td>
          </tr>
      </table>
      
      <?php submit_button(); ?>

    </form>

    <h2><?php _e( 'Acceso al listado de etiquetas (33 últimos envíos)', 'correos' ); ?></h2><hr>

      <?php 
      $wp_directory        = plugin_dir_url( __FILE__ ).'pdftmp/';
      $absolute_directory  = realpath(dirname(__FILE__)).'/pdftmp/';
      $files               = glob( $absolute_directory . "*.pdf" );
      $countfiles          = 0;
      // echo "<br><br>Directorio absoluto: ".$absolute_directory."<br>";
      // echo "Directorio WordPress: ".$wp_directory."<br><br>";
      $i = 0;

      echo '<table>
                  <tr>';
      foreach(array_reverse($files) as $url) {
        $countfiles++;
        $path           = parse_url($url, PHP_URL_PATH);
        $pathFragments  = explode('/', $path);
        $end            = end($pathFragments);

        if ( $countfiles > 99 ) {
          unlink($url);     // lo elimina
          // echo "Se ha borrado el archivo " . $end . "<br>";

        } else {
          $i++;
          echo '<td style="padding: 5px;"><a href="' . $wp_directory . $end . '" target="_blank">' . $end . '</a></td>';

          if($i == 3) { // three items in a row. Edit this to get more or less items on a row
              echo '</tr><tr>';
              $i = 0;
          }
          // echo '<a href="' . $wp_directory . $end . '" target="_blank">' . $end . '</a><br>';

        }// end if/else
      }
      echo '    </tr>
        </table><br>';

      echo "<br>Número de archivos: <b>" . $countfiles . "</b> de un límite de 100.";

      ?>
  </div>
  <?php   
}//correos_service_init



/*************************************************************************************************
  **********************************************************************************************
    CORE
  **********************************************************************************************
*************************************************************************************************/

// Trigger when order processing 'woocommerce_thankyou'
add_action( 'woocommerce_order_status_processing', 'preregistroenvios', 10, 1 );
function preregistroenvios( $order_id ) {
    if ( ! $order_id ) {
        return;
    }

    global $woocommerce;

    // 1) Get the Order object
    $order  = wc_get_order($order_id);
    $pos    = strpos( $order->get_shipping_method(), "Correos");
    if ($pos === false) {
        return;
    }

    $currentdate = gmdate('d-m-Y h:i:s');

    /* DATOS DE CORREOS */
    $correosuser         = esc_attr( get_option('correos_user') );
    $correospassword     = esc_attr( get_option('correos_password') );
    $correoetiquetadora  = esc_attr( get_option('correos_ce') );
    $numcontrato         = esc_attr( get_option('correos_numcontrato') );
    $numcliente          = esc_attr( get_option('correos_numcliente') );
    $emailmanipulado     = esc_attr( get_option('correos_emailmanipulado') );
    $codigo_producto     = esc_attr( get_option('codigo_producto') );// S0138
    $URL_SOAP            = esc_attr( get_option('correos_environment') );
    // $URL_SOAP            = 'https://preregistroenvios.correos.es/preregistroenvios';// conseguir de la base de datos

    /* DATOS REMITENTE */
    $nombre_remitente    = esc_attr( get_option('correos_nombreremite') );
    $apellido1_remitente = esc_attr( get_option('correos_apellido1remite') );
    $empresa_remitente   = esc_attr( get_option('correos_empresaremite') ); 
    $empresa_remitente   = str_replace('&', 'y', $empresa_remitente);
    $nif_remitente       = esc_attr( get_option('correos_nifremite') ); 
    $contacto_remitente  = esc_attr( get_option('correos_contactoremite') ); 
    $direccion_remitente = esc_attr( get_option('correos_direccionremite') ); 
    $localidad_remitente = esc_attr( get_option('correos_localidadremite') ); 
    $provincia_remitente = esc_attr( get_option('correos_provinciaremite') ); 
    $cp_remitente        = esc_attr( get_option('correos_cpremite') );
    $telefono_remitente  = esc_attr( get_option('correos_telremite') ); 
    $email_remitente     = esc_attr( get_option('correos_emailremite') );

    /* DATOS DE PEDIDO */
    // 2) Get the Order meta data
    $order_meta              = get_post_meta($order_id);
    $nif_destinatario        = ( isset($order_meta['_customer_dni'][0]) )        ? $order_meta['_customer_dni'][0]        : '';
    $nombre_destinatario     = ( isset($order_meta['_shipping_first_name'][0]) ) ? $order_meta['_shipping_first_name'][0] : $order_meta['_billing_first_name'][0];
    $apellido1_destinatario  = ( isset($order_meta['_shipping_last_name'][0]) )  ? $order_meta['_shipping_last_name'][0]  : $order_meta['_billing_last_name'][0];
    $apellido2_destinatario  = '';
    $empresa_destinatario    = ( isset($order_meta['_shipping_company'][0]) )    ? $order_meta['_shipping_company'][0]    : $order_meta['_billing_company'][0];
    $empresa_destinatario    = str_replace('&', 'y', $empresa_destinatario);
    $direccion_destinatario  = ( isset($order_meta['_shipping_address_1'][0]) )  ? $order_meta['_shipping_address_1'][0] . " - " . $order_meta['_shipping_address_2'][0] : $order_meta['_billing_address_1'][0] . " - " . $order_meta['_billing_address_2'][0];
    $localidad_destinatario  = ( isset($order_meta['_shipping_city'][0]) )       ? $order_meta['_shipping_city'][0]       : $order_meta['_billing_city'][0];
    $provincia_destinatario  = ( isset($order_meta['_shipping_state'][0]) )      ? $order_meta['_shipping_state'][0]      : $order_meta['_billing_state'][0];
    $cp_destinatario         = ( isset($order_meta['_shipping_postcode'][0]) )   ? $order_meta['_shipping_postcode'][0]   : $order_meta['_billing_postcode'][0];
    $telefono_destinatario   = ( isset($order_meta['_shipping_phone'][0]) )      ? $order_meta['_shipping_phone'][0]      : $order_meta['_billing_phone'][0];
    $email_destinatario      = ( isset($order_meta['_shipping_email'][0]) )      ? $order_meta['_shipping_email'][0]      : $order_meta['_billing_email'][0];
    $notas                   = $order->get_customer_order_notes( );
    $items                   = $order->get_items();
    $user_id                 = $order->get_user_id();
    $order_data              = $order->get_data();
    $bought_vital            = wc_customer_bought_product( $order_data['billing']['email'], $user_id, '91' );// ID Vital 91
    $bought_intolerance      = wc_customer_bought_product( $order_data['billing']['email'], $user_id, '2968' );// ID Intolerance 2968
    $ci                      = $order_meta['keep_sample'][0];#Only "Informed Consent" for clients who still have samples in the laboratory
    // foreach ( $items as $item_id => $product ) {
    //   $codigo_analisis = wc_get_order_item_meta($item_id, 'unique_id');
    // }

    // Add some data to label for laboratory
    $observaciones1 = '';
    foreach ( $items as $item_key => $item_values ) {
        $item_id    = $item_values->get_id();
        $item_name  = $item_values->get_name(); // Name of the product
        $item_type  = $item_values->get_type(); // Type of the order item ("line_item")
        $item_data  = $item_values->get_data();
        // $item_id    = $item_data['product_id'];
        // $item_name  = $item_data['name'];
        $item_sku   = $item_data['sku'];
        $extension  = $item_name;
        
        // Don´t send pack if gift Card 
        if ( strpos($item_name, 'Tarjeta') !== false || strpos($item_name, 'Combo') !== false ) {
            continue; // do nothing
        } else {
            // Get unique id
            $codigo_analisis    = wc_get_order_item_meta($item_id, 'unique_id');
            // Get extensions of Vital or Intolerance
            switch ($item_name) {
                case 'Vital':
                    if ($bought_intolerance && $ci) {
                        $extension = '2524';
                    }
                    break;
                case 'Intolerance':
                    if ($bought_vital && $ci) {
                        $extension = '2525';
                    } 
                    break;
            }// switch
        }// if/else
        $observaciones1 .= $codigo_analisis.'-'.$extension.'**';
    }// end foreach

    function varDumpToString($var) {
        ob_start();
        var_dump($var);
        $result = ob_get_clean();
        return $result;
    }

    /********************************************************************************
    *********************************************************************************
    // XML to call WebService for "IDA"
    *********************************************************************************
    ********************************************************************************/
    $xmlSend_ida='<x:Envelope xmlns:x="http://schemas.xmlsoap.org/soap/envelope/" xmlns:pre="http://www.correos.es/iris6/services/preregistroetiquetas">
        <x:Header/>    
        <x:Body>
            <pre:PreregistroEnvio>
                <pre:FechaOperacion>'.$currentdate.'</pre:FechaOperacion>
                <pre:CodEtiquetador>'.$correoetiquetadora.'</pre:CodEtiquetador>
                <pre:Care>000000</pre:Care>
                <pre:TotalBultos>1</pre:TotalBultos>
                <pre:ModDevEtiqueta>2</pre:ModDevEtiqueta>
                <pre:Remitente>
                    <pre:Identificacion>
                        <pre:Nombre>GoGood</pre:Nombre>
                        <pre:Nif>'.$nif_remitente.'</pre:Nif>
                        <pre:Empresa>'.$empresa_remitente.'</pre:Empresa>
                        <pre:PersonaContacto>'.$contacto_remitente.'</pre:PersonaContacto>
                    </pre:Identificacion>
                    <pre:DatosDireccion>
                        <pre:Direccion>'.$direccion_remitente.'</pre:Direccion>
                        <pre:Localidad>'.$localidad_remitente.'</pre:Localidad>
                        <pre:Provincia>'.$provincia_remitente.'</pre:Provincia>
                    </pre:DatosDireccion>
                    <pre:CP>'.$cp_remitente.'</pre:CP>
                    <pre:Telefonocontacto>'.$telefono_remitente.'</pre:Telefonocontacto>
                    <pre:Email>'.$email_remitente.'</pre:Email>
                </pre:Remitente>
                <pre:Destinatario>
                    <pre:Identificacion>
                        <pre:Nombre>'.$nombre_destinatario.'</pre:Nombre>
                        <pre:Apellido1>'.$apellido1_destinatario.'</pre:Apellido1>
                        <pre:Nif>'.$nif_destinatario.'</pre:Nif>
                        <pre:Empresa>'.$empresa_destinatario.'</pre:Empresa>
                    </pre:Identificacion>
                    <pre:DatosDireccion>
                        <pre:Direccion>'.$direccion_destinatario.'</pre:Direccion>
                        <pre:Localidad>'.$localidad_destinatario.'</pre:Localidad>
                        <pre:Provincia>'.$provincia_destinatario.'</pre:Provincia>
                    </pre:DatosDireccion>
                    <pre:CP>'.$cp_destinatario.'</pre:CP>
                    <pre:Pais>ES</pre:Pais>
                    <pre:Email>'.$email_destinatario.'</pre:Email>
                </pre:Destinatario>
                <pre:Envio>
                    <pre:NumBulto>1</pre:NumBulto>
                    <pre:CodProducto>'.$codigo_producto.'</pre:CodProducto>
                    <pre:TipoFranqueo>FP</pre:TipoFranqueo>
                    <ModalidadEntrega>ST</ModalidadEntrega>
                    <pre:Pesos>
                        <pre:Peso>
                            <pre:TipoPeso>R</pre:TipoPeso>
                            <pre:Valor>500</pre:Valor>
                        </pre:Peso>
                    </pre:Pesos>
                    <pre:Observaciones1>'.$observaciones1.'</pre:Observaciones1>
                </pre:Envio>
            </pre:PreregistroEnvio>
        </x:Body>
    </x:Envelope>';

    $ch         = curl_init();
    $headers    = array(
                    "Content-Type: application/soap+xml; charset=utf-8",
                    "Content-Length: ".strlen($xmlSend_ida)
                );
    curl_setopt($ch, CURLOPT_URL, $URL_SOAP);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlSend_ida);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_USERPWD, $correosuser.':'.$correospassword);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $result     = curl_exec($ch);
    $dataXml    = simplexml_load_string($result);
    $dataXml->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
    $error      = $dataXml->xpath('//soap:Fault');


    if (!empty($error)) {
        // Mail to admin
        wp_mail( get_option('admin_email'), 'Correos WebService IDA error 1', 'error1 <br>'.$error[0]->faultstring );
        // TO DO:
        // INSERT INTO `good_correos_preregister_errors` (`id_order`, `error`, `date`) VALUES ('', $error, CURRENT_TIMESTAMP)
    }

    if (!$dataXml->registerXPathNamespace('RespuestaPreregistroEnvio','http://www.correos.es/iris6/services/preregistroetiquetas')){
        wp_mail( get_option('admin_email'), 'Correos WebService IDA error 2', 'error2 <br> No hay RespuestaPreregistroEnvio' );
        // TO DO:
        // INSERT INTO `good_correos_preregister_errors` (`id_order`, `error`, `date`) VALUES ('', $error, CURRENT_TIMESTAMP)
    }

    $error_code = $dataXml->xpath('//RespuestaPreregistroEnvio:Resultado');

    if ($error_code[0] == '0') {  // correct
        // Data for "IDA"
        $date_response      = $dataXml->xpath('//RespuestaPreregistroEnvio:FechaRespuesta');
        $package_data_ida   = $dataXml->xpath('//RespuestaPreregistroEnvio:Bulto[1]');
        $codigo_envio_ida   = $package_data_ida[0]->CodEnvio;
        $label_pdf_ida      = file_put_contents(__DIR__ ."/pdftmp/".$codigo_analisis."-ida.pdf",base64_decode($package_data_ida[0]->Etiqueta->Etiqueta_pdf->Fichero));
        update_post_meta( $order_id, 'expedition_code_ida', (string)$codigo_envio_ida );

    } else {
        // Mail to admin
        wp_mail( get_option('admin_email'), 'Correos WebService IDA error', 'Error code: '.varDumpToString($error_code).' <br>https://drive.google.com/drive/u/0/folders/0B52dNp195uCOWVQwVGNZTTZFbXM' );
        // TO DO:
        // INSERT INTO `good_correos_preregister_errors` (`id_order`, `error`, `date`) VALUES ('', $error, CURRENT_TIMESTAMP)
    }

    /********************************************************************************
    *********************************************************************************
    // XML to call WebService for "VUELTA"
    *********************************************************************************
    ********************************************************************************/
    $xmlSend_vuelta='<x:Envelope xmlns:x="http://schemas.xmlsoap.org/soap/envelope/" xmlns:pre="http://www.correos.es/iris6/services/preregistroetiquetas">
        <x:Header/>    
        <x:Body>
            <pre:PreregistroEnvio>
                <pre:FechaOperacion>'.$currentdate.'</pre:FechaOperacion>
                <pre:CodEtiquetador>'.$correoetiquetadora.'</pre:CodEtiquetador>
                <pre:Care>000000</pre:Care>
                <pre:TotalBultos>1</pre:TotalBultos>
                <pre:ModDevEtiqueta>2</pre:ModDevEtiqueta>
                <pre:Remitente>
                    <pre:Identificacion>
                        <pre:Nombre>'.$nombre_destinatario.'</pre:Nombre>
                        <pre:Apellido1>'.$apellido1_destinatario.'</pre:Apellido1>
                        <pre:Nif>'.$nif_destinatario.'</pre:Nif>
                        <pre:PersonaContacto>'.$nombre_destinatario.'</pre:PersonaContacto>
                    </pre:Identificacion>
                    <pre:DatosDireccion>
                        <pre:Direccion>'.$direccion_destinatario.'</pre:Direccion>
                        <pre:Localidad>'.$localidad_destinatario.'</pre:Localidad>
                        <pre:Provincia>'.$provincia_destinatario.'</pre:Provincia>
                    </pre:DatosDireccion>
                    <pre:CP>'.$cp_destinatario.'</pre:CP>
                    <pre:Email>'.$email_destinatario.'</pre:Email>
                </pre:Remitente>
                <pre:Destinatario>
                    <pre:Identificacion>
                        <pre:Nombre>GoGood</pre:Nombre>
                        <pre:Apellido1>'.$contacto_remitente.'</pre:Apellido1>
                        <pre:Nif>'.$nif_remitente.'</pre:Nif>
                        <pre:Empresa>'.$empresa_remitente.'</pre:Empresa>
                    </pre:Identificacion>
                    <pre:DatosDireccion>
                        <pre:Direccion>'.$direccion_remitente.'</pre:Direccion>
                        <pre:Localidad>'.$localidad_remitente.'</pre:Localidad>
                        <pre:Provincia>'.$provincia_remitente.'</pre:Provincia>
                    </pre:DatosDireccion>
                    <pre:CP>'.$cp_remitente.'</pre:CP>
                    <pre:Pais>ES</pre:Pais>
                    <pre:Email>'.$email_remitente.'</pre:Email>
                </pre:Destinatario>
                <pre:Envio>
                    <pre:NumBulto>1</pre:NumBulto>
                    <pre:CodProducto>S0135</pre:CodProducto>
                    <pre:TipoFranqueo>FP</pre:TipoFranqueo>
                    <ModalidadEntrega>ST</ModalidadEntrega>
                    <pre:Pesos>
                        <pre:Peso>
                            <pre:TipoPeso>R</pre:TipoPeso>
                            <pre:Valor>500</pre:Valor>
                        </pre:Peso>
                    </pre:Pesos>
                    <pre:Observaciones1>'.$observaciones1.'</pre:Observaciones1>
                    <pre:CodigoIda>'.$codigo_envio_ida.'</pre:CodigoIda>
                </pre:Envio>
            </pre:PreregistroEnvio>
        </x:Body>
    </x:Envelope>';

    $ch         = curl_init();
    $headers    = array(
                    "Content-Type: application/soap+xml; charset=utf-8",
                    "Content-Length: ".strlen($xmlSend_vuelta)
                );
    curl_setopt($ch, CURLOPT_URL, $URL_SOAP);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlSend_vuelta);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_USERPWD, $correosuser.':'.$correospassword);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $result     = curl_exec($ch);
    $dataXml    = simplexml_load_string($result);
    $dataXml->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
    $error      = $dataXml->xpath('//soap:Fault');


    if (!empty($error)) {
        // Mail to admin
        wp_mail( get_option('admin_email'), 'Correos WebService VUELTA error 1', 'error1 <br>'.$error[0]->faultstring );
        // TO DO:
        // INSERT INTO `good_correos_preregister_errors` (`id_order`, `error`, `date`) VALUES ('', $error, CURRENT_TIMESTAMP)
    }

    if (!$dataXml->registerXPathNamespace('RespuestaPreregistroEnvio','http://www.correos.es/iris6/services/preregistroetiquetas')){
        // echo "error2";
        wp_mail( get_option('admin_email'), 'Correos WebService VUELTA error 2', 'error2 <br> No hay RespuestaPreregistroEnvio' );
        // TO DO:
        // INSERT INTO `good_correos_preregister_errors` (`id_order`, `error`, `date`) VALUES ('', $error, CURRENT_TIMESTAMP)
    }

    $error_code = $dataXml->xpath('//RespuestaPreregistroEnvio:Resultado');

    if ($error_code[0] == '0') {  // correct
        // Data for "VUELTA"
        $date_response              = $dataXml->xpath('//RespuestaPreregistroEnvio:FechaRespuesta');
        $package_data_vuelta        = $dataXml->xpath('//RespuestaPreregistroEnvio:Bulto[1]');
        $codigo_envio_vuelta        = $package_data_vuelta[0]->CodEnvio;
        $label_pdf_vuelta           = file_put_contents(__DIR__ ."/pdftmp/".$codigo_analisis."-vuelta.pdf",base64_decode($package_data_vuelta[0]->Etiqueta->Etiqueta_pdf->Fichero));
        update_post_meta( $order_id, 'expedition_code_vuelta', (string)$codigo_envio_vuelta );

    } else {
        // Mail to admin
        wp_mail( get_option('admin_email'), 'Correos WebService VUELTA error', 'Error code: '.varDumpToString($error_code).' <br>https://drive.google.com/drive/u/0/folders/0B52dNp195uCOWVQwVGNZTTZFbXM' );
        // TO DO:
        // INSERT INTO `good_correos_preregister_errors` (`id_order`, `error`, `date`) VALUES ('', $error, CURRENT_TIMESTAMP)
    }

    // Create attachments to email
    $attachments = array();
    if ( $label_pdf_ida && $label_pdf_vuelta ) {
	    $attachments[] = WP_PLUGIN_DIR . '/woocommerce-correos/pdftmp/' . $codigo_analisis . '-ida.pdf';
	    $attachments[] = WP_PLUGIN_DIR . '/woocommerce-correos/pdftmp/' . $codigo_analisis . '-vuelta.pdf';
    }

    //Send email
    if ($label_pdf_ida && $label_pdf_vuelta ) { //&& $label_pdf_pendrive
        $to                 = $emailmanipulado . ',' . get_option('admin_email');
        $subject            = '[' . $codigo_analisis . '] Etiquetas para el pedido #' . $order_id;
        $message            = 'Etiquetas para la IDA y VUELTA del pedido <b>#' . $order_id . '</b> con el código de análisis: <b>' . $codigo_analisis . '</b><br>';
        $message           .= '<br>==================================================================================================<br><br>';
        $message           .= 'Se han realizado dos peticiones de pre-registro con los siguientes códigos de expedición: <br><br>';
        $message           .= 'Cod Expedición IDA: <b>' . $codigo_envio_ida . '</b><br>';
        $message           .= 'Cod Expedición VUELTA: <b>' . $codigo_envio_vuelta . '</b><br>';
        // $message           .= 'Cod Expedición PENDRIVE: <b>' . $expedition_code_pendrive . '</b><br>';
        $message           .= '<br>==================================================================================================<br>';
        $headers           .= "MIME-Version: 1.0\r\n";
        $headers           .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
        
        wp_mail( $to, $subject, $message, $headers, $attachments );
    }// if label_pdf_vuelta etc

}// end function preregistroenvios
?>
