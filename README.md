&nbsp;
<p align="center">
    <img src="https://github.com/mosparo/mosparo/blob/master/assets/images/mosparo-logo.svg?raw=true" alt="mosparo logo contains a bird with the name Mo and the mosparo text"/>
</p>

<h1 align="center">
    Integration for WordPress
</h1>
<p align="center">
    This WordPress plugin adds the required functionality to use mosparo in your WordPress form.
</p>

-----

## Description

The WordPress plugin adds the required functionality to your WordPress website to use mosparo in your forms.
This plugin is compatible with WordPress comments and account forms (login, lost password, registration), Contact Form 7, Elementor Form, Everest Forms, Formidable, Gravity Forms, JetFormBuilder, Memberpress account forms (login, lost password), Ninja Forms, WooCommerce reviews and account forms (login, lost password, registration) and WPForms.

The plugin is compatible with WordPress multisite.

## Requirements

To use the plugin, you must meet the following requirements:

- A mosparo project
- A WordPress website
- A user with the ability to install plugins
- If you want to use it in your contact form, you need a compatible contact form plugin

## Installation

To use the plugin, please follow this installation instruction:

1. Log in to your WordPress website and go to the plugins
2. Click "Install" to install the new plugin
3. Search for "mosparo Integration"
4. Install the plugin with the name "mosparo Integration"
5. Activate the plugin
6. Go to "Settings" > "mosparo Integration" and add a connection to connect your WordPress website with your mosparo installation
7. Enable the needed modules
8. If you want to add it to your contact form, edit your form and add the mosparo field

## Configuration

You can find all mosparo settings under "Settings" > "mosparo Integration" in the WordPress administration.

### Define the connection in `wp-config.php`

If you want to define the mosparo connection in the WordPress configuration file (`wp-config.php`), follow the following steps:

1. Open the `wp-config.php` file in an editor.
2. Find the following line:
```php
/* That's all, stop editing! Happy publishing. */
```
3. Add the following lines **before** the found line. Replace the placeholders (for example, `<Project-UUID>`) with the connection settings provided by your mosparo project.
```php
define('WP_MOSPARO_HOST', '<URL-of-your-mosparo-installation>'); // Starting with https://
define('WP_MOSPARO_UUID', '<Project-UUID>');
define('WP_MOSPARO_PUBLIC_KEY', '<Project-Public-Key>');
define('WP_MOSPARO_PRIVATE_KEY', '<Project-Private-Key>');
define('WP_MOSPARO_VERIFY_SSL', true); // Should be true but if you do not have a valid certificate, change this to false
```
4. Open the WordPress Administration and go to the mosparo Integration settings.
5. Enable the modules you want to use.

It's possible to add only one connection in the `wp-config.php` file, which will be the default connection for all modules. You can add additional connections in the WordPress administration, which then overrides the connection from the `wp-config.php` file.

### Configure network-wide connection

If you have a WordPress multisite network, you can enable the mosparo Integration plugin network-wide and configure the enabled modules and the available connections in the network settings.

Go to "Settings" > "mosparo Integration" in the network administration, add connections, and enable modules.

**Important:** A website can always add additional connections and enable additional modules.

### Default connection priority

The origin of a connection will select the connection for a module:

1. The `wp-config.php` file connection is always the general default connection.
2. _(Multisite only)_ If a connection is defined for a module in the network settings, the connection from the network settings will be used.
3. If a connection is defined for a module in the website settings, the connection from the website settings will be used.

## Upgrade Notice

### Version 1.5

#### Everest Forms
Because of a missing event in the Everest Forms plugin, the invisible mode of mosparo can only work in the normal submission mode, not in the AJAX submission mode. We're waiting for an additional event in the Everest Forms plugin, which makes the mosparo plugin compatible again.
