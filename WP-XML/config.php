<?php
/*
 * config.php
 *
 * This script provides the class definition for DomDoc. Since the DomDoc class
 * provides the basis for XAO, all the requirements checks for XAO are done
 * first up in this script. In general, however, all the code in XAO is object
 * oriented. For more information on the DomDoc class itself, see the doc
 * comment directly preceding the class declaration.
 *
 * @author       Terence Kearns
 * @version      0.0 alpha
 * @copyright    Terence Kearns 2013
 * @license      GPL3 - http://www.gnu.org/licenses/gpl-3.0.html
 * @link         http://wp-xml.com
 * @package      WPX (Wordpress XML)

 */

$arrConfig = array(
    defaultTemplate => "index.xsl",
    getPostsArgs => array(
        'posts_per_page'  => 5,
        'offset'          => 0,
        'category'        => '',
        'orderby'         => 'post_date',
        'order'           => 'DESC',
        'include'         => '',
        'exclude'         => '',
        'meta_key'        => '',
        'meta_value'      => '',
        'post_type'       => 'post',
        'post_mime_type'  => '',
        'post_parent'     => '',
        'post_status'     => 'publish',
        'suppress_filters' => true
    )

);

