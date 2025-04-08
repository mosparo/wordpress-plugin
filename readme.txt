=== mosparo Integration ===
Contributors: mosparo
Tags: mosparo, integration, spam-protection, forms, api-client
Requires at least: 5.4
Tested up to: 6.7.2
Requires PHP: 7.4
Stable tag: 1.13.5
License: MIT

The plugin adds the functionality to use mosparo in WordPress forms or forms from Contact Form 7, Everest Form, and other plugins.
	 
== Description ==
If you want to protect your forms in WordPress with mosparo, this plugin will help you with this task. After installing the plugin, you have to configure the connection to your mosparo installation and add the mosparo field to your form. The mosparo Integration plugin is compatible with the following plugins and forms:

- Contact Form 7
- Divi Contact Forms (**not** Email Optin or Login forms)
- Elementor Form
- Everest Forms
- Formidable
- Forminator (only forms, not polls or quizzes)
- Gravity Forms
- JetFormBuilder
- Memberpress Account Forms (login, lost password)
- Ninja Forms
- WPForms
- WooCommerce Account Forms (login, lost password, and registration)
- WordPress Account Forms (login, lost password, and registration) (also compatible with *Theme My Login*)
- WordPress Comments (and WooCommerce Reviews)

To use this plugin, you need an installation of mosparo so that the plugin can communicate with mosparo. Please find all information regarding mosparo on the website [mosparo.io](https://mosparo.io).

You can use different projects for the different modules. For example, this is useful for using mosparo for the account forms. There, you want to enable the lockout security setting in mosparo. However, this security setting may not be active for standard contact forms, so you must use two mosparo connections to two individual projects in mosparo.
	 
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

= 1.11.0 =

**Formidable**
Because of an error when editing a form, we had to change the name of the mosparo field in Formidable. After upgrading to v1.11.0, you must add the mosparo field to your form again.

= 1.5 =

**Everest Forms**
Because of a missing event in the Everest Forms plugin, the invisible mode of mosparo can only work in the normal submission mode, not in the AJAX submission mode. We're waiting for an additional event in the Everest Forms plugin, which makes the mosparo plugin compatible again.

== Changelog ==

= 1.13.5 =
*Release Date: 8th April 2025*

* [Bugfix] Added a fix for all account form modules to ignore the password field, especially when the password is visible.

= 1.13.4 =
*Release Date: 27th March 2025*

* [Bugfix] Fixing the handling of values with leading or trailing spaces in Contact Form 7, Divi, Everest Forms, Forminator, Gravity Forms, and JetFormBuilder forms.
* [Bugfix] Fixed the handling of select fields with the ‘multiple’ option in Contact Form 7 forms.

= 1.13.3 =
*Release Date: 4th March 2025*

* [Bugfix] Fixing the incorrect spam handling in the Jet Form Builder module when the tokens are missing.

= 1.13.2 =
*Release Date: 20th February 2025*

* [Bugfix] Opening an Elementor popup multiple times didn't initialize the mosparo box again after the first opening.

= 1.13.1 =
*Release Date: 17th February 2025*

* [Bugfix] Fixed the initialization method for the Divi Theme. Before, the module was only compatible with the Divi Builder plugin.

= 1.13.0 =
*Release Date: 16th February 2025*

* [Enhancement] Added a module to protect Divi contact forms.

= 1.12.2 =
*Release Date: 20th January 2025*

* [Bugfix] Gravity Forms: Fixed another issue with the handling of conditionally hidden fields.
* [Bugfix] WPForms: Fixed the initialization of the module in some cases where WPForms is not fully loaded.

= 1.12.1 =
*Release Date: 18th January 2025*

* [Bugfix] Gravity Forms: Fixed the selector and logic to handle conditionally hidden fields correctly.
* [Maintenance] Updated the backend dependencies

= 1.12.0 =
*Release Date: 31st October 2024*

* [Enhancement] Added a module to protect Forminator forms.

= 1.11.1 =
*Release Date: 19th August 2024*

* [Bugfix] Fixed the deployment issue for the JetFormBuilder module.
* [Bugfix] Fixed typos regarding the name of the JetFormBuilder module.
* [Bugfix] Fixed a wrong text domain in the JetFormBuilder.
* [Bugfix] Added the correct container for the Memberpress mosparo field.

= 1.11.0 =
*Release Date: 17th August 2024*

* [Enhancement] Added a module to protect JetFormBuilder forms.
* [Enhancement] Added a module to protect Memberpress account forms (Login and lost password).
* [Change] Changed the internal name of the Formidable field type.
* [Bugfix] Fixed a bug with the initialization of mosparo when adding the mosparo field to a form in the WordPress administration.

= 1.10.0 =
*Release Date: 5th July 2024*

* [Enhancement] Added a module to protect the WooCommerce account forms. Contributed by nmcodeeu (https://github.com/nmcodeeu).
* [Change] Modules can now only be enabled if the dependencies are fulfilled. Contributed by nmcodeeu (https://github.com/nmcodeeu).
* [Change] Added module settings for the two account modules. Contributed by nmcodeeu (https://github.com/nmcodeeu).
* [Change] Added an info message about JavaScript optimization plugins.
* [Change] Added a better error handling if the refresh CSS URL cache action does not work correctly.
* [Bugfix] Multiple bugs were fixed with the modules table because WordPress officially does not support two tables on the same page.
* [Bugfix] Fixed the reset password functionality in the user administration.

= 1.9.1 =
*Release Date: 18th April 2024*

* [Bugfix] Elementor: Fixed an issue with the initialization in forms in Elementor popups

= 1.9 =
*Release Date: 29th March 2024*

* [Change] Replaced the jQuery code with Vanilla JS
* [Bugfix] Fixed the issue with the verification in Ninja Forms
* [Bugfix] Fixed the invisible mode in Ninja Forms

= 1.8.1 =
*Release Date: 27th February 2024*

* [Bugfix] Wrong version number generated an issue with the WordPress release management

= 1.8 =
*Release Date: 27th February 2024*

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
