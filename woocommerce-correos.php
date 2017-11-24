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

    <h2><?php _e( 'Acceso al listado de etiquetas', 'correos' ); ?></h2><hr>

      <?php 
      $wp_directory        = plugin_dir_url( __FILE__ ).'pdftmp/';
      $absolute_directory  = realpath(dirname(__FILE__)).'/pdftmp/';
      $files               = glob( $absolute_directory . "*.pdf" );
      $countfiles          = 0;
      // echo "<br><br>Directorio absoluto: ".$absolute_directory."<br>";
      // echo "Directorio WordPress: ".$wp_directory."<br><br>";
      $i = 0;

      if ($files) {      

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

      }// End if

      echo "<br>Número de archivos: <b>" . $countfiles . "</b> de un límite de 100.";

      // TO DO: Conservar sólo las 50 últimas e ir borrando las antiguas
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
//add_action( 'woocommerce_order_status_processing', 'preregistroenvios', 10, 1 );
function preregistroenvios( $order_id ) {
  if ( ! $order_id ) {
    return;
  }
  global $woocommerce;
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
  $apellido2_remitente = esc_attr( get_option('correos_apellido2remite') );
  $empresa_remitente   = esc_attr( get_option('correos_empresaremite') ); 
	$nif_remitente       = esc_attr( get_option('correos_nifremite') ); 
	$contacto_remitente  = esc_attr( get_option('correos_contactoremite') ); 
	$direccion_remitente = esc_attr( get_option('correos_direccionremite') ); 
	$localidad_remitente = esc_attr( get_option('correos_localidadremite') ); 
	$provincia_remitente = esc_attr( get_option('correos_provinciaremite') ); 
	$cp_remitente        = esc_attr( get_option('correos_cpremite') );
	$telefono_remitente  = esc_attr( get_option('correos_telremite') ); 
	$email_remitente     = esc_attr( get_option('correos_emailremite') );


	/* DATOS DE PEDIDO */
  // 1) Get the Order object
  $order = wc_get_order($order_id);

  // 2) Get the Order meta data
  $order_meta              = get_post_meta($order_id);
  $nif_destinatario        = ( isset($order_meta['_customer_dni'][0]) )        ? $order_meta['_customer_dni'][0]        : '';
  $nombre_destinatario     = ( isset($order_meta['_shipping_first_name'][0]) ) ? $order_meta['_shipping_first_name'][0] : $order_meta['_billing_first_name'][0];
  $apellido1_destinatario  = ( isset($order_meta['_shipping_last_name'][0]) )  ? $order_meta['_shipping_last_name'][0]  : $order_meta['_billing_last_name'][0];
  $apellido2_destinatario  = '';
  $empresa_destinatario    = ( isset($order_meta['_shipping_company'][0]) )    ? $order_meta['_shipping_company'][0]    : $order_meta['_billing_company'][0];
  $direccion_destinatario  = ( isset($order_meta['_shipping_address_1'][0]) )  ? $order_meta['_shipping_address_1'][0] . " - " . $order_meta['_shipping_address_2'][0] : $order_meta['_billing_address_1'][0] . " - " . $order_meta['_billing_address_2'][0];
  $localidad_destinatario  = ( isset($order_meta['_shipping_city'][0]) )       ? $order_meta['_shipping_city'][0]       : $order_meta['_billing_city'][0];
  $provincia_destinatario  = ( isset($order_meta['_shipping_state'][0]) )      ? $order_meta['_shipping_state'][0]      : $order_meta['_billing_state'][0];
  $cp_destinatario         = ( isset($order_meta['_shipping_postcode'][0]) )   ? $order_meta['_shipping_postcode'][0]   : $order_meta['_billing_postcode'][0];
  $telefono_destinatario   = ( isset($order_meta['_shipping_phone'][0]) )      ? $order_meta['_shipping_phone'][0]      : $order_meta['_billing_phone'][0];
  $email_destinatario      = ( isset($order_meta['_shipping_email'][0]) )      ? $order_meta['_shipping_email'][0]      : $order_meta['_billing_email'][0];
  $notas                   = $order->get_customer_order_notes( );
  $items                   = $order->get_items();
  foreach ( $items as $item_key => $item_values ) {
    $item_id    = $item_values->get_id();
    $item_name  = $item_values->get_name();
    // Don´t add unique ID if Card gift
    if ( !strpos($item_name, 'Tarjeta') ) {
      $codigo_analisis  = wc_get_order_item_meta($item_id, 'unique_id');
    }  
  }// end foreach

  // Exit if Card gift
  if ( ! $direccion_destinatario ) {
    return;
  }

// # ONLY FOR TEST 
// print '<pre>' . htmlspecialchars(print_r(get_defined_vars(), true)) . '</pre>';
// die();
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
            <pre:NumContrato>'.$numcontrato.'</pre:NumContrato>
            <pre:NumCliente>'.$numcliente.'</pre:NumCliente>
            <pre:Care>000000</pre:Care>
            <pre:TotalBultos>1</pre:TotalBultos>
            <pre:ModDevEtiqueta>2</pre:ModDevEtiqueta>
            <pre:Remitente>
                <pre:Identificacion>
                    <pre:Nombre>'.$nombre_remitente.'</pre:Nombre>
                    <pre:Apellido1>'.$apellido1_remitente.'</pre:Apellido1>
                    <pre:Apellido2>'.$apellido2_remitente.'</pre:Apellido2>
                    <pre:Nif>'.$nif_remitente.'</pre:Nif>
                    <pre:Empresa>'.$empresa_remitente.'</pre:Empresa>
                    <pre:PersonaContacto>'.$contacto_remitente.'</pre:PersonaContacto>
                </pre:Identificacion>
                <pre:DatosDireccion>
                    <pre:TipoDireccion>.</pre:TipoDireccion>
                    <pre:Direccion>'.$direccion_remitente.'</pre:Direccion>
                    <pre:Numero></pre:Numero>
                    <pre:Portal></pre:Portal>
                    <pre:Bloque></pre:Bloque>
                    <pre:Escalera></pre:Escalera>
                    <pre:Piso></pre:Piso>
                    <pre:Puerta></pre:Puerta>
                    <pre:Localidad>'.$localidad_remitente.'</pre:Localidad>
                    <pre:Provincia>'.$provincia_remitente.'</pre:Provincia>
                </pre:DatosDireccion>
                <pre:CP>'.$cp_remitente.'</pre:CP>
                <pre:Telefonocontacto>'.$telefono_remitente.'</pre:Telefonocontacto>
                <pre:Email>'.$email_remitente.'</pre:Email>
                <pre:DatosSMS>
                    <pre:NumeroSMS>.</pre:NumeroSMS>
                    <pre:Idioma>1</pre:Idioma>
                </pre:DatosSMS>
            </pre:Remitente>
            <pre:Destinatario>
                <pre:Identificacion>
                    <pre:Nombre>'.$nombre_destinatario.'</pre:Nombre>
                    <pre:Apellido1>'.$apellido1_destinatario.'</pre:Apellido1>
                    <pre:Apellido2></pre:Apellido2>
                    <pre:Nif>'.$nif_destinatario.'</pre:Nif>
                    <pre:Empresa>'.$empresa_destinatario.'</pre:Empresa>
                </pre:Identificacion>
                <pre:DatosDireccion>
                    <pre:TipoDireccion>.</pre:TipoDireccion>
                    <pre:Direccion>'.$direccion_destinatario.'</pre:Direccion>
                    <pre:Numero></pre:Numero>
                    <pre:Portal></pre:Portal>
                    <pre:Bloque></pre:Bloque>
                    <pre:Escalera></pre:Escalera>
                    <pre:Piso></pre:Piso>
                    <pre:Puerta></pre:Puerta>
                    <pre:Localidad>'.$localidad_destinatario.'</pre:Localidad>
                    <pre:Provincia>'.$provincia_destinatario.'</pre:Provincia>
                </pre:DatosDireccion>
                <pre:CP>'.$cp_destinatario.'</pre:CP>
                <pre:Pais>España</pre:Pais>
                <pre:Telefonocontacto>'.$telefono_destinatario.'</pre:Telefonocontacto>
                <pre:Email>'.$email_destinatario.'</pre:Email>
            </pre:Destinatario>
            <pre:Envio>
                <pre:NumBulto>1</pre:NumBulto>
                <pre:CodProducto>'.$codigo_producto.'</pre:CodProducto>
                <pre:ReferenciaCliente>'.$codigo_analisis.'</pre:ReferenciaCliente>
                <pre:ReferenciaCliente2></pre:ReferenciaCliente2>
                <pre:TipoFranqueo>FP</pre:TipoFranqueo>
                <ModalidadEntrega>ST</ModalidadEntrega>
                <pre:Pesos>
                    <pre:Peso>
                        <pre:TipoPeso>R</pre:TipoPeso>
                        <pre:Valor>500</pre:Valor>
                    </pre:Peso>
                </pre:Pesos>
                <pre:ExisteEnvioVueltaLI>S</pre:ExisteEnvioVueltaLI>
                <pre:Observaciones1>'.$codigo_analisis.'</pre:Observaciones1>
                <pre:Observaciones2></pre:Observaciones2>
            </pre:Envio>
        </pre:PreregistroEnvio>
    </x:Body>
</x:Envelope>';

// echo "<pre>";
// echo "IDA: ". htmlentities( $xmlSend_ida);
// echo "</pre>";
      
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $URL_SOAP);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlSend_ida);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Autorization:Basic dzAwMTcyNjE6c1k3VVdFakY="."Content-Type: text/xml; charset=utf-8","SOAPAction:PreRegistro"));
        curl_setopt($ch, CURLOPT_USERPWD, $correosuser.':'.$correospassword);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($ch);

// echo "IDA: <br>";
// echo "Codigo de producto: ".$codigo_producto."<br>";
// echo 'Credenciales: '.$correosuser.':'.$correospassword.'<br>';
// echo 'Codigo Etiquetadora: '.$correoetiquetadora.'<br>';
// echo 'Entorno: '.$URL_SOAP.'<br>';
// echo "<pre>";
// var_dump($result);
// echo "</pre>";
// die();

// FALLA EL PRODUCTO: "31-03-2017 14:44:4211159 El envío no se ha registrado. Producto no válido"

        $dataXml = simplexml_load_string($result);
        $dataXml->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $error = $dataXml->xpath('//soap:Fault');
        if (!empty($error)) {
            echo "error1 <br>";
            echo $error[0]->faultstring;
            // TO DO:
            // INSERT INTO `good_correos_preregister_errors` (`id_order`, `error`, `date`) VALUES ('', $error, CURRENT_TIMESTAMP)
        }

        if (!$dataXml->registerXPathNamespace('RespuestaPreregistroEnvio','http://www.correos.es/iris6/services/preregistroetiquetas')){
            echo "error2";
            // TO DO:
            // INSERT INTO `good_correos_preregister_errors` (`id_order`, `error`, `date`) VALUES ('', $error, CURRENT_TIMESTAMP)
        }

        $error_code = $dataXml->xpath('//RespuestaPreregistroEnvio:Resultado');

         if ($error_code[0] == '0') {  // correct
            $date_response          = $dataXml->xpath('//RespuestaPreregistroEnvio:FechaRespuesta');
            $package_data_ida       = $dataXml->xpath('//RespuestaPreregistroEnvio:Bulto');
             // Add expedition code to order meta
            $expedition_code_ida    = $package_data_ida[0]->CodEnvio;
              update_post_meta( $order_id, 'expedition_code_ida', (string)$expedition_code_ida );

// echo "<pre>";
// var_dump($package_data_ida);
// echo "</pre>";die(); 
              
              // Create PDF label
              $label_pdf_ida  = file_put_contents(__DIR__ ."/pdftmp/".$codigo_analisis."-ida.pdf",base64_decode($package_data_ida[0]->Etiqueta->Etiqueta_pdf->Fichero));

// echo "<pre>";
// var_dump($label_pdf_ida);
// echo "</pre>";die();

              // Create attachments to email
              $attachments = array();
              if ( $label_pdf_ida ) {
                $attachments[] = WP_PLUGIN_DIR . '/woocommerce-correos/pdftmp/' . $codigo_analisis . '-ida.pdf';
              } 

        } else {
          echo "Error code: ".$error_code[0];
          var_dump($error_code);
          die();
            // TO DO:
            // INSERT INTO `good_correos_preregister_errors` (`id_order`, `error`, `date`) VALUES ('', $error, CURRENT_TIMESTAMP)
        }


/********************************************************************************
*********************************************************************************
// XML to call WebService for "vuelta"
*********************************************************************************
********************************************************************************/
$xmlSend_vuelta='<x:Envelope xmlns:x="http://schemas.xmlsoap.org/soap/envelope/" xmlns:pre="http://www.correos.es/iris6/services/preregistroetiquetas">
    <x:Header/>
    <x:Body>
        <pre:PreregistroEnvio>
            <pre:FechaOperacion>'.$currentdate.'</pre:FechaOperacion>
            <pre:CodEtiquetador>'.$correoetiquetadora.'</pre:CodEtiquetador>
            <pre:NumContrato>'.$numcontrato.'</pre:NumContrato>
            <pre:NumCliente>'.$numcliente.'</pre:NumCliente>
            <pre:Care>000000</pre:Care>
            <pre:TotalBultos>1</pre:TotalBultos>
            <pre:ModDevEtiqueta>2</pre:ModDevEtiqueta>
            
            <pre:Remitente>
                <pre:Identificacion>
                    <pre:Nombre>'.$nombre_destinatario.'</pre:Nombre>
                    <pre:Apellido1>'.$apellido1_destinatario.'</pre:Apellido1>
                    <pre:Apellido2></pre:Apellido2>
                    <pre:Nif>'.$nif_destinatario.'</pre:Nif>
                    <pre:Empresa>'.$empresa_destinatario.'</pre:Empresa>
                </pre:Identificacion>
                <pre:DatosDireccion>
                    <pre:TipoDireccion>.</pre:TipoDireccion>
                    <pre:Direccion>'.$direccion_destinatario.'</pre:Direccion>
                    <pre:Numero></pre:Numero>
                    <pre:Portal></pre:Portal>
                    <pre:Bloque></pre:Bloque>
                    <pre:Escalera></pre:Escalera>
                    <pre:Piso></pre:Piso>
                    <pre:Puerta></pre:Puerta>
                    <pre:Localidad>'.$localidad_destinatario.'</pre:Localidad>
                    <pre:Provincia>'.$provincia_destinatario.'</pre:Provincia>
                </pre:DatosDireccion>
                <pre:CP>'.$cp_destinatario.'</pre:CP>
                <pre:Pais>España</pre:Pais>
                <pre:Telefonocontacto>'.$telefono_destinatario.'</pre:Telefonocontacto>
                <pre:Email>'.$email_destinatario.'</pre:Email>
            </pre:Remitente>
            
            <pre:Destinatario>
                <pre:Identificacion>
                    <pre:Nombre>'.$nombre_remitente.'</pre:Nombre>
                    <pre:Apellido1>'.$apellido1_remitente.'</pre:Apellido1>
                    <pre:Apellido2>'.$apellido2_remitente.'</pre:Apellido2>
                    <pre:Nif>'.$nif_remitente.'</pre:Nif>
                    <pre:Empresa>'.$empresa_remitente.'</pre:Empresa>
                    <pre:PersonaContacto>'.$contacto_remitente.'</pre:PersonaContacto>
                </pre:Identificacion>
                <pre:DatosDireccion>
                    <pre:TipoDireccion>.</pre:TipoDireccion>
                    <pre:Direccion>'.$direccion_remitente.'</pre:Direccion>
                    <pre:Numero></pre:Numero>
                    <pre:Portal></pre:Portal>
                    <pre:Bloque></pre:Bloque>
                    <pre:Escalera></pre:Escalera>
                    <pre:Piso></pre:Piso>
                    <pre:Puerta></pre:Puerta>
                    <pre:Localidad>'.$localidad_remitente.'</pre:Localidad>
                    <pre:Provincia>'.$provincia_remitente.'</pre:Provincia>
                </pre:DatosDireccion>
                <pre:CP>'.$cp_remitente.'</pre:CP>
                <pre:Telefonocontacto>'.$telefono_remitente.'</pre:Telefonocontacto>
                <pre:Email>'.$email_remitente.'</pre:Email>
                <pre:DatosSMS>
                    <pre:NumeroSMS>'.$telefono_remitente.'</pre:NumeroSMS>
                    <pre:Idioma>1</pre:Idioma>
                </pre:DatosSMS>       
            </pre:Destinatario>

            <pre:Envio>
                <pre:NumBulto>1</pre:NumBulto>
                <pre:CodProducto>S0135</pre:CodProducto>
                <pre:ReferenciaCliente>'.$codigo_analisis.'</pre:ReferenciaCliente>
                <pre:ReferenciaCliente2></pre:ReferenciaCliente2>
                <pre:TipoFranqueo>FP</pre:TipoFranqueo>
                <pre:Pesos>
                    <pre:Peso>
                        <pre:TipoPeso>R</pre:TipoPeso>
                        <pre:Valor>500</pre:Valor>
                    </pre:Peso>
                </pre:Pesos>
                <pre:Observaciones1>'.$codigo_analisis.'</pre:Observaciones1>
                <pre:Observaciones2></pre:Observaciones2>
                <pre:CodigoIda>'.$expedition_code_ida.'</pre:CodigoIda>
            </pre:Envio>
        </pre:PreregistroEnvio>
    </x:Body>
</x:Envelope>';

// echo "<pre>";
// echo "<br>VUELTA: " . htmlentities( $xmlSend_vuelta);
// echo "</pre>";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $URL_SOAP);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlSend_vuelta);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Autorization:Basic dzAwMTcyNjE6c1k3VVdFakY="."Content-Type: text/xml; charset=utf-8","SOAPAction:PreRegistro"));
        curl_setopt($ch, CURLOPT_USERPWD, $correosuser.':'.$correospassword);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($ch);

// echo "VUELTA: <br>";
// echo "Código de producto: S0135<br>";
// echo "<pre>";
// var_dump($result);
// echo "</pre>";
// die(); 

        $dataXml = simplexml_load_string($result);
        $dataXml->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $error = $dataXml->xpath('//soap:Fault');
        if (!empty($error)) {
            echo "error1 <br>";
            echo $error[0]->faultstring;
            // TO DO:
            // INSERT INTO `good_correos_preregister_errors` (`id_order`, `error`, `date`) VALUES ('', $error, CURRENT_TIMESTAMP)

        }

        if (!$dataXml->registerXPathNamespace('RespuestaPreregistroEnvio','http://www.correos.es/iris6/services/preregistroetiquetas')){
            echo "error2";
            // TO DO:
            // INSERT INTO `good_correos_preregister_errors` (`id_order`, `error`, `date`) VALUES ('', $error, CURRENT_TIMESTAMP)
        }

        $error_code = $dataXml->xpath('//RespuestaPreregistroEnvio:Resultado');

         if ($error_code[0] == '0') {  // correct
            $date_response        = $dataXml->xpath('//RespuestaPreregistroEnvio:FechaRespuesta');
            $package_data_vuelta  = $dataXml->xpath('//RespuestaPreregistroEnvio:Bulto');

// echo "<pre>";
// var_dump($package_data_vuelta);
// echo "</pre>";die();

            // Add expedition code to order meta
            $expedition_code_vuelta = $package_data_vuelta[0]->CodEnvio;
              update_post_meta( $order_id, 'expedition_code_vuelta', (string)$expedition_code_vuelta );

            // Create PDF label
            $label_pdf_vuelta = file_put_contents(__DIR__ ."/pdftmp/".$codigo_analisis."-vuelta.pdf",base64_decode($package_data_vuelta[0]->Etiqueta->Etiqueta_pdf->Fichero));

// echo "<pre>";
// var_dump($label_pdf_vuelta);
// echo "</pre>";die();

            if ($label_pdf_vuelta) {
              $attachments[]  = WP_PLUGIN_DIR . '/woocommerce-correos/pdftmp/' . $codigo_analisis . '-vuelta.pdf';
            }// if label_pdf_vuelta

        } else {
          echo "Error code: ".$error_code[0];
          var_dump($error_code);
          die();
            // TO DO:
            // INSERT INTO `good_correos_preregister_errors` (`id_order`, `error`, `date`) VALUES ('', $error, CURRENT_TIMESTAMP
        }


/********************************************************************************
*********************************************************************************
// XML to call WebService for "PENDRIVE"
*********************************************************************************
********************************************************************************/
$xmlSend_pendrive='<x:Envelope xmlns:x="http://schemas.xmlsoap.org/soap/envelope/" xmlns:pre="http://www.correos.es/iris6/services/preregistroetiquetas">
    <x:Header/>
    <x:Body>
        <pre:PreregistroEnvio>
            <pre:FechaOperacion>'.$currentdate.'</pre:FechaOperacion>
            <pre:CodEtiquetador>'.$correoetiquetadora.'</pre:CodEtiquetador>
            <pre:NumContrato>'.$numcontrato.'</pre:NumContrato>
            <pre:NumCliente>'.$numcliente.'</pre:NumCliente>
            <pre:Care>000000</pre:Care>
            <pre:TotalBultos>1</pre:TotalBultos>
            <pre:ModDevEtiqueta>2</pre:ModDevEtiqueta>
            <pre:Remitente>
                <pre:Identificacion>
                    <pre:Nombre>'.$nombre_remitente.'</pre:Nombre>
                    <pre:Apellido1>'.$apellido1_remitente.'</pre:Apellido1>
                    <pre:Apellido2>'.$apellido2_remitente.'</pre:Apellido2>
                    <pre:Nif>'.$nif_remitente.'</pre:Nif>
                    <pre:Empresa>'.$empresa_remitente.'</pre:Empresa>
                    <pre:PersonaContacto>'.$contacto_remitente.'</pre:PersonaContacto>
                </pre:Identificacion>
                <pre:DatosDireccion>
                    <pre:TipoDireccion>.</pre:TipoDireccion>
                    <pre:Direccion>'.$direccion_remitente.'</pre:Direccion>
                    <pre:Numero></pre:Numero>
                    <pre:Portal></pre:Portal>
                    <pre:Bloque></pre:Bloque>
                    <pre:Escalera></pre:Escalera>
                    <pre:Piso></pre:Piso>
                    <pre:Puerta></pre:Puerta>
                    <pre:Localidad>'.$localidad_remitente.'</pre:Localidad>
                    <pre:Provincia>'.$provincia_remitente.'</pre:Provincia>
                </pre:DatosDireccion>
                <pre:CP>'.$cp_remitente.'</pre:CP>
                <pre:Telefonocontacto>'.$telefono_remitente.'</pre:Telefonocontacto>
                <pre:Email>'.$email_remitente.'</pre:Email>
                <pre:DatosSMS>
                    <pre:NumeroSMS>.</pre:NumeroSMS>
                    <pre:Idioma>1</pre:Idioma>
                </pre:DatosSMS>
            </pre:Remitente>
            <pre:Destinatario>
                <pre:Identificacion>
                    <pre:Nombre>'.$nombre_destinatario.'</pre:Nombre>
                    <pre:Apellido1>'.$apellido1_destinatario.'</pre:Apellido1>
                    <pre:Apellido2></pre:Apellido2>
                    <pre:Nif>'.$nif_destinatario.'</pre:Nif>
                    <pre:Empresa>'.$empresa_destinatario.'</pre:Empresa>
                </pre:Identificacion>
                <pre:DatosDireccion>
                    <pre:TipoDireccion>.</pre:TipoDireccion>
                    <pre:Direccion>'.$direccion_destinatario.'</pre:Direccion>
                    <pre:Numero></pre:Numero>
                    <pre:Portal></pre:Portal>
                    <pre:Bloque></pre:Bloque>
                    <pre:Escalera></pre:Escalera>
                    <pre:Piso></pre:Piso>
                    <pre:Puerta></pre:Puerta>
                    <pre:Localidad>'.$localidad_destinatario.'</pre:Localidad>
                    <pre:Provincia>'.$provincia_destinatario.'</pre:Provincia>
                </pre:DatosDireccion>
                <pre:CP>'.$cp_destinatario.'</pre:CP>
                <pre:Pais>España</pre:Pais>
                <pre:Telefonocontacto>'.$telefono_destinatario.'</pre:Telefonocontacto>
                <pre:Email>'.$email_destinatario.'</pre:Email>
            </pre:Destinatario>
            <pre:Envio>
                <pre:NumBulto>1</pre:NumBulto>
                <pre:CodProducto>'.$codigo_producto.'</pre:CodProducto>
                <pre:ReferenciaCliente>'.$codigo_analisis.'</pre:ReferenciaCliente>
                <pre:ReferenciaCliente2></pre:ReferenciaCliente2>
                <pre:TipoFranqueo>FP</pre:TipoFranqueo>
                <pre:Pesos>
                    <pre:Peso>
                        <pre:TipoPeso>R</pre:TipoPeso>
                        <pre:Valor>500</pre:Valor>
                    </pre:Peso>
                </pre:Pesos>
                <pre:Observaciones1>'.$codigo_analisis.'</pre:Observaciones1>
                <pre:Observaciones2></pre:Observaciones2>
            </pre:Envio>
        </pre:PreregistroEnvio>
    </x:Body>
</x:Envelope>';

// echo "<pre>";
// echo "<br>VUELTA: " . htmlentities( $xmlSend_pendrive);
// echo "</pre>";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $URL_SOAP);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlSend_pendrive);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Autorization:Basic dzAwMTcyNjE6c1k3VVdFakY="."Content-Type: text/xml; charset=utf-8","SOAPAction:PreRegistro"));
        curl_setopt($ch, CURLOPT_USERPWD, $correosuser.':'.$correospassword);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($ch);

// echo "PENDRIVE<br>";
// echo "Código de producto: ".$codigo_producto."<br>";
// echo "<pre>";
// var_dump($result);
// echo "</pre>";
// die(); 

        $dataXml = simplexml_load_string($result);
        $dataXml->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $error = $dataXml->xpath('//soap:Fault');
        if (!empty($error)) {
            echo "error1 <br>";
            echo $error[0]->faultstring;
            // TO DO:
            // INSERT INTO `good_correos_preregister_errors` (`id_order`, `error`, `date`) VALUES ('', $error, CURRENT_TIMESTAMP)

        }

        if (!$dataXml->registerXPathNamespace('RespuestaPreregistroEnvio','http://www.correos.es/iris6/services/preregistroetiquetas')){
            echo "error2";
            // TO DO:
            // INSERT INTO `good_correos_preregister_errors` (`id_order`, `error`, `date`) VALUES ('', $error, CURRENT_TIMESTAMP)
        }

        $error_code = $dataXml->xpath('//RespuestaPreregistroEnvio:Resultado');

         if ($error_code[0] == '0') {  // correct
            $date_response = $dataXml->xpath('//RespuestaPreregistroEnvio:FechaRespuesta');
            $package_data  = $dataXml->xpath('//RespuestaPreregistroEnvio:Bulto');

// echo "<pre>";
// var_dump($package_data);
// echo "</pre>";die();

            // Add expedition code to order meta
            $expedition_code_pendrive = $package_data[0]->CodEnvio;
              update_post_meta( $order_id, 'expedition_code_pendrive', (string)$expedition_code_pendrive );

            // Create PDF label
            $label_pdf_pendrive = file_put_contents(__DIR__ ."/pdftmp/".$codigo_analisis."-pendrive.pdf",base64_decode($package_data[0]->Etiqueta->Etiqueta_pdf->Fichero));

// echo "<pre>";
// var_dump($label_pdf_pendrive);
// echo "</pre>";die();

            if ($label_pdf_pendrive) {
              $attachments[]  = WP_PLUGIN_DIR . '/woocommerce-correos/pdftmp/' . $codigo_analisis . '-pendrive.pdf';
            }// if label_pdf_vuelta

        } else {
          echo "Error code: ".$error_code[0];
          var_dump($error_code);
          die();
            // TO DO:
            // INSERT INTO `good_correos_preregister_errors` (`id_order`, `error`, `date`) VALUES ('', $error, CURRENT_TIMESTAMP
        }

        //Send email
        if ($label_pdf_ida && $label_pdf_vuelta && $label_pdf_pendrive) {
          $to                 = $emailmanipulado . ',' . get_option('admin_email');
          $subject            = '[' . $codigo_analisis . '] Etiquetas para el pedido #' . $order_id;
          $message            = 'Etiquetas para la IDA, VUELTA y PENDRIVE del pedido <b>#' . $order_id . '</b> con el código de análisis: <b>' . $codigo_analisis . '</b><br>';
          $message           .= '<br>==================================================================================================<br><br>';
          $message           .= 'Se han realizado dos peticiones de pre-registro con los siguientes códigos de expedición: <br><br>';
          $message           .= 'Cod Expedición IDA: <b>' . $expedition_code_ida . '</b><br>';
          $message           .= 'Cod Expedición VUELTA: <b>' . $expedition_code_vuelta . '</b><br>';
          $message           .= 'Cod Expedición PENDRIVE: <b>' . $expedition_code_pendrive . '</b><br>';
          $message           .= '<br>==================================================================================================<br>';
          $headers           .= "MIME-Version: 1.0\r\n";
          $headers           .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
          
          wp_mail( $to, $subject, $message, $headers, $attachments );
        }// if label_pdf_vuelta etc

}// end function preregistroenvios
?>
