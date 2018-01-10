<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'sixandahalf');

/** MySQL database username */
define('DB_USER', 'sixandahalf');

/** MySQL database password */
define('DB_PASSWORD', 'reneeninaftp!2');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'D_R(;azD)_yXU8?{T!l3)o;qZgg9m+~fK((]66*p(e0k/0W;c7hA5Pn6s1VW~{t-');
define('SECURE_AUTH_KEY',  '/B*Isc],IH*sJdQjnp&/pw`#/PXSVE^$HQVS(M97W D8;W5j)}iN,wD>$jiP{+YT');
define('LOGGED_IN_KEY',    'rUrUOfr#<R0-]&%g96Th>J7 d[H-Rt@>Q::um$d T[[ws6@<?U[/+GxK/EV|lX`j');
define('NONCE_KEY',        '94F5/wkb<I-tyfO2{JQb+Q!oViw,S93^<lo&9Z_wb=(]kzg_0Ib`o0%0]RDm%&#x');
define('AUTH_SALT',        '`h IG`wuc$A[o}?x},&k9{d-/y]+#s<,!?E7&5;O[V,u8*B~$^xObx<,{ZKc?X= ');
define('SECURE_AUTH_SALT', 'vEBZ?$|NE8Z_Vm&$ z&Ag$ kUt3kKVw1tb]ihv@+l:<o&d|T`OH!h]Q/oM3Hb4xK');
define('LOGGED_IN_SALT',   '*EKtZkhRa&g05l;<UBotsHUZ`Bf.]C#.*xy:oo,Ne`zDiQlSJi{_WJ*LX:0: k/_');
define('NONCE_SALT',       '%oF|./&YIT2Ae@9Y#Mzy4yhM OLbgsf9k=[!RAgR4[:o`vy QV]H~2ESXh(i3k%2');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);


/* JELLY SHOP CODE */
define( 'WP_MEMORY_LIMIT', '96M' );
define ('WPLANG', 'ko_KR');


/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
