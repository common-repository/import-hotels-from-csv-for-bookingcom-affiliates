<?php
/**
 Plugin Name: Import hotels from CSV for Booking.com affiliates
 Version: 0.8.2
 Description: This plugin will import the Booking.com hotels from TSV(csv) files to your wordpress site.
 Author: Juan Carlos Moscardó Pérez
 Text Domain: ihfc_text_domain
 Domain Path: /languages
 @package import-hotels-from-csv-for-bookingcom-affiliates
 License: GPLv3
  
    Copyright (C) 2016  Juan Carlos Moscardó Pérez

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
define('IHFC_TEXT_DOMAIN', 'ihfc_text_domain');
define('IHFC_PLUGIN_NAME', 'NAME');
define('IHFC_PLUGIN_VERSION', '0.8.2');
define('IHFC_PLUGIN_SCRIPT_VERSION', '0.8.2');
define('IHFC_PLUGIN_FILE', plugin_basename(__FILE__));
define('IHFC_PLUGIN_DIR_PATH', plugin_dir_path(__FILE__));
define('IHFC_PLUGIN_DIR_URL', plugin_dir_url(__FILE__));
define('IHFC_SETTINGS_OPTIONS', "ihfc_setting_options");
define('IHFC_SETTINGS_OPTIONS_PAGE', 'ihfc-settings-page');
define('IHFC_PLUGIN_UPLOAD_CSV_DIR_PATH', IHFC_PLUGIN_DIR_PATH.'tsv-uploads'.DIRECTORY_SEPARATOR);
define('IHFC_PLUGIN_UPLOAD_CSV_DIR_TEXT', '&lt;PLUGINS DIR&gt;'.DIRECTORY_SEPARATOR.'import-hotels-from-csv-for-bookingcom-affiliates'.DIRECTORY_SEPARATOR.'tsv-uploads'.DIRECTORY_SEPARATOR);
define("IHFC_MAX_HOTELS_IMPORT_AT_ONCE",10);
define("IHFC_MIN_EXECUTION_TIME",300);

include(IHFC_PLUGIN_DIR_PATH."classes/class-ihfc-init.php");
include(IHFC_PLUGIN_DIR_PATH."classes/class-ihfc-csv-helper.php");
include(IHFC_PLUGIN_DIR_PATH."classes/class-ihfc-hotel.php");
include(IHFC_PLUGIN_DIR_PATH."classes/class-ihfc-wp-utils.php");
include(IHFC_PLUGIN_DIR_PATH."classes/class-ihfc-admin-settings.php");
include(IHFC_PLUGIN_DIR_PATH."classes/class-ihfc-front-replacemets.php");
include(IHFC_PLUGIN_DIR_PATH."classes/class-ihfc-import-hotels-process.php");
include(IHFC_PLUGIN_DIR_PATH."classes/class-ihfc-replace.php");
IhfcInit::initAll();
