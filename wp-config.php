<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '8<?3(7u.X%D<PY+)W;$B2}E5[P4XD08R&Gfktl*utyH]JSj_DQsyl0x<,pTpTq(V' );
define( 'SECURE_AUTH_KEY',  'R[s8`L@&^NXY;E]yHW55{E7Xmz(yU-a=d=Z2.}:[agI 7P~pdaxtnWED.4hH0YY-' );
define( 'LOGGED_IN_KEY',    'i^E?_%#0TwYdBphIGu`8fhf0Jn3g6a`s[q8;,M.#`d]s-TDDo@QJk,aY=P)9mD.g' );
define( 'NONCE_KEY',        ')JbA1~gN,6=d^0p*^jYZaG*FtvHyF0cX.{8|#@[W*@R;0%0qEUy5LFCa$Lv;mX$.' );
define( 'AUTH_SALT',        'PVj]~AU8i:U3o=qowE (?f,O0_$i-yxz3[WsY^#Bx8Z-;y_*50.rxht!A3Ng6QLX' );
define( 'SECURE_AUTH_SALT', 'M4.btpNI,=bBnv=-bS:^Lt<N{V:s_Dg=]OD%HHnit|Fl^pPBrJ1LT5oz)uy0!q5^' );
define( 'LOGGED_IN_SALT',   'vXJa0/|DA>UV9>4 ->(}:!ZNm*lGpM 3Dkw]qL8y-,(6mS@ZVBCWTO>M-lN?/BJ ' );
define( 'NONCE_SALT',       'pf 8]n:f{4*&P2X=F>5.9o15IgizTt3iRswqVy%{io]FaAkjhj_C5f.xzYA8zO~|' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
