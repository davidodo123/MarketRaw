<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

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
define( 'AUTH_KEY',          '>KCc0W-PkA;j{YG@cfPf9SRCVN8WFJW_Kuy$%,`}<%7j =@:V ,t-4,d59+eVP3L' );
define( 'SECURE_AUTH_KEY',   '?#9Mynm<--sP8u+bs[eBDtzi`pSMIX#5+[(Y_}nSwLD&/9UIiV.L0g44w[qqiAVB' );
define( 'LOGGED_IN_KEY',     'XAh4IGlapw}N_t+Fr#%_z*KoW;=$Y=&zXip171stMI29./QGAyd0~XS<Rn:=i}TL' );
define( 'NONCE_KEY',         'a2/uaP]%.swq&5>XQ[$;+=#]Tt_psKr9.=qq_7rNmBfm&XiAG-KG`BeBT.tL vlQ' );
define( 'AUTH_SALT',         'SoI(G/x&-H>&%KCDe]=20*t|YYslR3|1EaWxAFB+uh@({<,*e}IWF(wL$ @eajg$' );
define( 'SECURE_AUTH_SALT',  'Hi!pD.|koo{8h:Q~}0_4[+AN;QX/x3tAF6>w{yr.K(_p_Xmri`Tgl4T:GJCe!b@y' );
define( 'LOGGED_IN_SALT',    '$TP`a{u`V&2{J$2w-hnW(dP_=)a)g#?kThl)eNMTy{CEpZ8fBo9v}q@&^|Kg(%Mv' );
define( 'NONCE_SALT',        '3XA2dwnsL,3vi}xf,&M,,xXCq3$u1Rc&]V(i-B|l6noMt-$B,6sSs/vf5_(%N^[d' );
define( 'WP_CACHE_KEY_SALT', 'mJB 0w7(xT$dPX Xpy1&uGC<7HyH.r<g/=C.sxadQ{$8ZO?T+(>9S!JU@A:c!Wm>' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
