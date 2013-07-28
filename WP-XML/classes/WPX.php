<?php
/*
 * WPX.php
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

class WPX
extends Xao_AppDoc
{
    public $arrConfig;
    private $strNamespacePrefix = "wpx";
    private $strNamespaceURI = "https://github.com/tezza1971/WP-XML/blob/master/wpx.xsd";
    public $uriContent;
    public $blnProcessed;
    public $uriXslTemplate;
    public $ndPosts;

    /**
     * The FACADE constructor method initialises a few instance variables and
     * then calls the parent constructor.
     */
    public function __construct($arrConfig, $blnDebug = false) {
        $this->blnDebug = $blnDebug;
        $this->arrConfig = $arrConfig;
        parent::__construct("wpx");
        $this->IntroduceNs($this->strNamespaceURI,$this->strNamespacePrefix);
    }

    public function GetPosts($arrArgs = '') {
    	if(!is_array($arrArgs)) $arrArgs = $this->arrConfig['getPostsArgs'];
        $arrPosts = get_posts($arrArgs);
    	if(!is_array($arrPosts)) return;
    	if(!$this->ndPosts) $this->ndPosts = $this->ndAppendToRoot("posts");
        $this->ndHashToAttribs($this->ndPosts, $arrArgs);
    	foreach($arrPosts as $objPost) {
    		$ndPost = $this->ndAppendToNode(
    			$this->ndPosts,
    			"post",
    			"<![CDATA[".$objPost->post_content."]]>"
    		);
    		unset($objPost->post_content);
    		$arrAttribs = (array)$objPost;
    		$arrAttribs['post_id'] = $arrAttribs['ID'];
    		unset($arrAttribs['ID']);
    		$this->ndHashToAttribs($ndPost,$arrAttribs);
    	}
    	// if(is_array($arrPosts)) $this->ndPosts = $this->ndHashToXml($arrPosts);
    }

    /**
     * This function will return the name of a template. If it can't find one
     * matching the same name and location of the content file, then it will
     * fall back to the default template as specified in the config file.
     * Note that anything here can be overriden by passing a template URL to the
     * Transform() method call.
     *
     * @return  URI to the XSL file used as a template
     */
    function uriFindTemplate() {
                                        // Don't do anything if we already know
                                        // where it is
        if($this->uriXslTemplate) return $this->uriXslTemplate;
                                        // Start by assuming the default
        $this->uriXslTemplate = $this->arrConfig["defaultTemplate"];
                                        // See if there is a specific one to
                                        // override the default one
		// TODO.. implement some rules for finding alternative XSL templates
                                        // return whatever is left
        return $this->uriXslTemplate;
    }

    /**
     * This method overrides the parent method but eventually calls it. As you
     * can see, the FACADE rules can be overriden by supplying the URI of an
     * existing stylesheet (not checked).
     *
     * @param   URI An existing stylesheet to use in spite of any found ones.
     */
    function Transform($uriXslOverride = "") {
        if($uriXslOverride) {
            $this->ndBuildXslPi($uriXslOverride);
        }
        else {
            if($tpl = $this->uriFindTemplate()) {
                $this->ndBuildXslPi($tpl);
            }
        }
        parent::Transform();
    }

}