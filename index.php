<?php
/*
 * index.php
 *
 * This is the index.php file as per the wordpress themes specification.
 * I'm not really sure what's supposed to go here yet but I'm sure I'll work it
 * out as I go along. It won't be your conventional html code that's for sure!
 * If someone experienced with WP wants to give me a hand describaing the http
 * request paradigm that is typical of WP, I would be most grateful. Then I can
 * work out what XML needs to be produced and when.
 *
 * @author       Terence Kearns
 * @version      0.0 alpha
 * @copyright    Terence Kearns 2013
 * @license      GPL3 - http://www.gnu.org/licenses/gpl-3.0.html
 * @link         http://wp-xml.com
 * @package      WPX (Wordpress XML)
 */
                                        // sets $arrConfig global;
include_once dirname(__FILE__).'/config.php';
                                        // wordpress convetion
include_once dirname(__FILE__).'/functions.php';
                                        // requires PHP 5.1.2
spl_autoload_register('WpxAutoLoad');
                                        // instantiate a child of AppDoc
$objWPX = new WPX($arrConfig,true);
                                        // Set the Xao debug flag
$objWPX->blnDebug = true;
                                        // Retrieve the posts as per default
                                        // post params described in config.php
$objWPX->GetPosts();
                                        // send the XML (or XSL) to the client
$objWPX->Send();