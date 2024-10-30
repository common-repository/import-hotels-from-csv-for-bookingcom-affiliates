=== Import hotels from CSV for Booking.com affiliates ===
Author: Juan Carlos Moscardó Pérez
Contributors: Juan Carlos Moscardó Pérez
Tags: Booking.com, affiliate, import, tsv, csv, booking, hotels, integration, affiliation programme 
Requires at least: 3.4
Tested up to: 4.4.2
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.en.html

This plugin will import the Booking.com hotels from TSV(csv) files to your wordpress site.

== Description ==

**You must have a Booking.com Affiliate account in order to import the booking.com hotels using this plugin.**

This plugin allows you to import easyly Booking.com hotels from TSV files. Before of that you should have downloaded  these hotels datasets from Booking.com Affiliate area (Downloads > Hotel datasets).

Before use this plugin you have to do:

 * Download hotels TSV files of the cities that you want import hotels from Booking.com Affiliate Partner Centre > Downloads, 
 * Place these tsv files in tsv-import folder located in the plugin folder.
 * Create a draft post or custom type template. (It will be used to create the hotel posts)

This plugin will create posts (of the same type of the post template). The numbers of post will be created may vary depending the hotels to import and languages (if you have WPML installed ).

It is strongly recommended to:

 * edit the TSV files and  keep only the cities and hotels you want to import to your wordpress site.
 * **create a database backup of your wordpress site**.

= Features = 

* Compatible with wordpress post types (posts/pages) and custom post types.
* Easy template creation.
* TSV/CSV cache and hotel indexing for performance
* Easy to use. Insert Destination city ID, post ID and click validate/import
* Replacements fields compatible with Visual Composer
* Import system compatible with WPML
* Remote Hotel Image URL inside posts or as featured image (Do not need to download hotel images)

= Possible customisation =

Use these fields inside your post template.

* **{{hotel_id}}**  =>  Hotel ID
* **{{hotel_name}}**  =>  Property name
* **{{hotel_address}}**  =>  Property address
* **{{hotel_zip}}**  =>  Post code
* **{{hotel_city_hotel}}**  =>  Property city
* **{{hotel_cc1}}**  =>  Property country code
* **{{hotel_ufi}}**  =>  Unique city identifier (destination id), also known as city_id or dest_id
* **{{hotel_class}}**  =>  Star rating of property
* **{{hotel_currencycode}}**  =>  The currency in which prices are given for this property (three characters, uppercase - e.g - EUR)
* **{{hotel_minrate}}**  =>  The lowest rate charged by the property, calculated on average
* **{{hotel_maxrate}}**  =>  The highest rate charged by the property, calculated on average
* **{{hotel_preferred}}**  =>  If value is 1, this property is a Booking.com preferred property
* **{{hotel_public_ranking}}**  =>  The rank of the property (higher is better)
* **{{hotel_nr_rooms}}**  =>  The total number of rooms at the property
* **{{hotel_longitude}}**  =>  Decimal longitude
* **{{hotel_latitude}}**  =>  Decimal latitude
* **{{hotel_hotel_url}}**  =>  The property URL on Booking.com
* **{{hotel_photo_url}}**  =>  URL for main property photo
* **{{hotel_desc_(language)}}**  =>  Short description of property change (language) => en, es, fr ...  {{hotel_desc_en}}
* **{{hotel_city_unique}}**  =>  Unique city name for use in URL
* **{{hotel_city_preferred}}**  =>  Preferred name of city for Booking.com use
* **{{hotel_continent_id}}**  =>  sThe unique ID for the continent on which the property is located

The plugin will replace the fields above with the hotel information. For example:

{{hotel_name}}  will be replaced with the name of the hotel

{{hotel_hotel_url}}  will be replaced with booking.com hotel url
...
... 

== Installation ==

Please, renember that you must be Booking.com affiliate to use this plugin.

1. Download the plugin from https://wordpress.org/plugins/
2. Login in your Wordpress as admin role.
3. Go to: Plugins > Add New > Upload. 
4. Select the downloaded file and click "Install now".
5. Place your TSC/CSV hotels files in the tsv-uploads folder inside the plugin
6. Do wordpress database backup. Just in case
7. Use the plugin

After that, I will be able to:

* Import al hotels from a city.
* Use different post templates for hotel imports.

== Screenshots ==

1. First of all you need to downdoad from "Booking.com Affiliate Partner Center" the hotels datasets (TSV files).
2. Once you upload the hotels datasets to <PLUGIN DIR>/tsv-uploads/. You need to Generate cache files.
3. Create a post draft as template, using (customization fields, adding translations, categories, etc etc).
4. Insert destinations ID and the draft post ID (template already created) and click for validation.
5. The destination id exists in the TSV files and the post ID is valid as hotel template. You can continue by clicking Import hotels
6. Impoer RESULTS. List of hotels (posts, status, languages) imported or updated. Only import or modified hotels 10 each time.
7. List of posts created (ex. portfolios categories Hotels and Mojacar), By default created as Draft, though It can be force to publish
8. Final post created. {{Name of field}} have been replaced by the hotel value.Ex. {{hotel_name}} => Real name of the hotel

== Changelog ==

= 0.8.1 =
* First version

= 0.8.2 =
* Add screenshots and more info