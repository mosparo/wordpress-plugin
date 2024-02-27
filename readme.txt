=== mosparo Integration ===
Contributors: mosparo
Tags: mosparo, integration, spam-protection, forms, api-client
Requires at least: 5.4
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.8
License: MIT

The plugin adds the functionality to your website to use mosparo in the WordPress comment and account forms (login, lost password, register) or a form from Contact Form 7, Elementor Form, Everest Forms, Formidable, Gravity Forms, Ninja Forms, or WPForms.
	 
== Description ==
If you want to protect your forms in WordPress with mosparo, this plugin will help you with this task. The mosparo Integration plugin can add the mosparo box to the WordPress comment and account forms (login, lost password and register) or the forms from the plugins Contact Form 7, Elementor Form, Everest Forms, Formidable, Gravity Forms, Ninja Forms, or WPForms. The Comments module is compatible with the WooCommerce reviews (mosparo Integration v1.8+).
	 
To use this plugin, you need an installation of mosparo so that the plugin can communicate with mosparo. Please find all information regarding mosparo on the website [mosparo.io](https://mosparo.io).

You can use different projects for the different modules. This is required, for example, if you want to use mosparo for the account forms. There you want to enable the lockout security setting in mosparo. But this security setting may not be active for standard contact forms, so you must use two mosparo connections to two individual projects in mosparo.
	 
== Installation ==

= Automatic installation =

1. Go to the Plugins Menu in WordPress
2. Search for “mosparo Integration”
3. Click “Install”

= Manual download =

1. Download the plugin from the plugin site on wordpress.org
2. Extract the ZIP file
3. Upload the "mosparo-integration" folder to the /wp-content/plugins/ directory
4. Activate the plugin through the "Plugins" menu in WordPress

After installing and activating the plugin, please go to "Settings" > "mosparo Integration" and add a connection to your mosparo installation. See the Configuration section for more information.

= Configuration =

You can find all mosparo settings under "Settings" > "mosparo Integration" in the WordPress administration. Add at least one connection and enable the modules you want to use mosparo with. You can find all the information you need for the connection in the project settings in your mosparo project.

= Define the connection in `wp-config.php` =

If you want to define the mosparo connection in the WordPress configuration file (`wp-config.php`), follow the following steps:

1. Open the `wp-config.php` file in an editor.
2. Find the following line:
~~~php
/* That's all, stop editing! Happy publishing. */
~~~
3. Add the following lines **before** the found line. Replace the placeholders (for example, `<Project-UUID>`) with the connection settings provided by your mosparo project.
~~~php
define('WP_MOSPARO_HOST', '<URL-of-your-mosparo-installation>'); // Starting with https://
define('WP_MOSPARO_UUID', '<Project-UUID>');
define('WP_MOSPARO_PUBLIC_KEY', '<Project-Public-Key>');
define('WP_MOSPARO_PRIVATE_KEY', '<Project-Private-Key>');
define('WP_MOSPARO_VERIFY_SSL', true); // Should be true but if you do not have a valid certificate, change this to false
~~~
4. Open the WordPress Administration and go to the mosparo Integration settings.
5. Enable the modules you want to use.

It's possible to add only one connection in the `wp-config.php` file, which will be the default connection for all modules. You can add additional connections in the WordPress administration, which then overrides the connection from the `wp-config.php` file.

= Configure network-wide connection =

If you have a WordPress multisite network, you can enable the mosparo Integration plugin network-wide and configure the enabled modules and the available connections in the network settings.

Go to "Settings" > "mosparo Integration" in the network administration, add connections, and enable modules.

**Important:** A website can always add additional connections and enable additional modules.

= Default connection priority =

The origin of a connection will select the connection for a module:

1. The `wp-config.php` file connection is always the default connection.
2. _(Multisite only)_ If a connection is defined for a module in the network settings, the connection from the network settings will be used.
3. If a connection is defined for a module in the website settings, the connection from the website settings will be used.

== Upgrade Notice ==

= 1.5 =

**Everest Forms**
Because of a missing event in the Everest Forms plugin, the invisible mode of mosparo can only work in the normal submission mode, not in the AJAX submission mode. We're waiting for an additional event in the Everest Forms plugin, which makes the mosparo plugin compatible again.

== Changelog ==

= 1.8 =
*Release Date: 27th February 2023*

* [Enhancement] Added the option configure the mosparo connection in the wp-config.php file
* [Enhancement] Added the compatibility of the plugin with the WordPress Multisite. Configure connections and enable modules in the network administration.
* [Bugfix] Fixed the compatibility with the WooCommerce reviews

= 1.7 =
*Release Date: 5th November 2023*

* [Enhancement] The use of the plugin has been simplified by adding a how-to-use text and a link to the settings page.

= 1.6 =
*Release Date: 21st October 2023*

* [Fix] Adjusted the dependencies so the plugin will not have a problem with other plugins that use Guzzle or other dependencies.
* [Fix] Contact Form 7: Fixed an issue with select fields that have no options or use a data provider like Listo

= 1.5 =
*Release Date: 25th July 2023*

* [Enhancement] Prepare the modules for the invisible mode of mosparo, which will be available with mosparo v1.0
* [Fix] Hide the label for the mosparo field in the Everest Forms and Formidable plugins

= 1.4.1 =
*Release Date: 4th May 2023*

* [Fix] Fixed the not-working CSS URL cache cronjob
* [Fix] Updated the dependencies to the latest versions

= 1.4 =
*Release Date: 27th April 2023*

* [Enhancement] Added a module form Elementor Form
* [Fix] Fixed the header of the mosparo settings page in the admin interface when Elementor is active

= 1.3 =
*Release Date: 15th April 2023*

* [Enhancement] Optimized the protection with the verifiable fields check in all modules (except comments and account)
* [Fix] Fixed the repeater field in the Formidable forms
* [Fix] Fixed the repeater field in the NinjaForms forms

= 1.2 =
*Release Date: 12th April 2023*

* [Fix] Check for the mosparo field in the form before executing the validation (Modules: Contact Form 7, Formidable, NinjaForms, and WPForms)

= 1.1 =
*Release Date: 14th March 2023*

* [Enhancement] Added the modules for Everest Forms, Formidable, Gravity Forms, and the WordPress account forms.
* [Enhancement] Added the ability to configure multiple connections to different mosparo projects
