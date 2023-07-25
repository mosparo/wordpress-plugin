=== mosparo Integration ===
Contributors: mosparo
Tags: mosparo, integration, spam-protection, forms, api-client
Requires at least: 5.4
Tested up to: 6.2
Requires PHP: 7.4
Stable tag: 1.4.1
License: MIT

The plugin adds the functionality to your website to use mosparo in the WordPress comment and account forms (login, lost password, register) or a form from Contact Form 7, Elementor Form, Everest Forms, Formidable, Gravity Forms, Ninja Forms, or WPForms.
	 
== Description ==
If you want to protect your forms in WordPress with mosparo, this plugin will help you with this task. The mosparo Integration plugin can add the mosparo box to the WordPress comment and account forms (login, lost password and register) or the forms from the plugins Contact Form 7, Elementor Form, Everest Forms, Formidable, Gravity Forms, Ninja Forms, or WPForms.
	 
To use this plugin, you need an installation of mosparo so that the plugin can communicate with mosparo. Please find all information regarding mosparo on the website [mosparo.io](https://mosparo.io).

You can use different projects for the different modules. This is required, for example, if you want to use mosparo for the account forms. There you want to enable the lockout security setting in mosparo. But this security setting may not be active for standard contact forms, so you must use two mosparo connections to two individual projects in mosparo.
	 
== Installation ==
After installing the plugin, please go to Settings > mosparo Integration and add a connection to your mosparo installation. For this, you need the host, the UUID, the public, and the private key of the mosparo project. You can add multiple connections to use different connections for the different modules.

== Upgrade Notice ==

= 1.5 =

**Everest Forms**
Because of a missing event in the Everest Forms plugin, the invisible mode of mosparo can only work in the normal submission mode, not in the AJAX submission mode. We're waiting for an additional event in the Everest Forms plugin, which makes the mosparo plugin compatible again.

== Changelog ==

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
