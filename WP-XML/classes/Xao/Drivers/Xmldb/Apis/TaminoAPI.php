<?php

/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4: */


/**
* @file    TaminoAPI.cpp
* @brief   This file contains all code for the TaminoAPI class.
* @version 0.5.0
*/


/*

Class Name: TaminoAPI

Version: 0.5.0

This class provides functions to access the Tamino XML Server via an
HTTP web server interface.

hcanges in 0.5.0:
- added new method setDefineMode()

changes in 0.4.0:
- added new method admin()
- added new method getRootDomNode()

changes in 0.3.0:
- added new method getResultHttpCode()
- added new method diagnose()
- added new method setMediaType()
- added new method setUsername($sAuthUsername)
- added new method setPassword($sAuthPassword)
- added automatic freeing of DOM objects to reduce memory usage
- added new method setContentTransferEncoding($sContentTransferEncoding)

-------------------------------------------------------------------------------------- 

Copyright 2003 - 2004 by Software AG 

Uhlandstrasse 12, D-64297 Darmstadt, GERMANY 

All rights reserved 

This software is the proprietary information of Software AG ('Information'). 
You shall not disclose such Information and shall use it only in accordance with 
the terms of the license agreement you entered into with Software AG or its 
distributors. 

<--------------------------------------------

Download License for Source Samples 


Software AG License Terms 
Version 1.3


1. Objective
Software AG provides free of charge add-on Software ("Program") on its 
Community Source Web Site (the "Site"). The Program is created by Software 
AG and is intended to be used only in addition to applications based on the 
Software AG product line Tamino, which is available under a separate license 
agreement. You have obtained the right to access the Site by licensing Tamino 
and/or Tamino X-Studio from Software AG and upon registration to this Site. 
By downloading the Program you accept the terms of this present agreement ("License").

Software AG provides the Program in source form ("Source Code") form on the Site. 


2. Grant of Rights
Software AG hereby grants you a non-exclusive copyright license to use, 
reproduce, modify, display, perform, distribute or sublicense the Program. 
Distribution and sublicensing to third parties is only permitted by providing 
the Program free of charge and by binding the third party also to this 
obligation.


2.1 Distribution/Sublicensing
You are permitted to distribute/sublicense the Program under your own license 
agreement, provided that: (a) the Program complies with the terms and conditions 
of this License; and (b) its license agreement (i) effectively disclaims on 
behalf of Software AG all warranties and conditions as disclaimed by this 
License; (ii) effectively excludes on behalf of Software AG all liability for 
damages as excluded in this License.

Distributions must reproduce applicable copyright statements and notices and 
must include the following acknowledgement in the documentation, if any: 

Copyright (C) Software AG, Germany 2001 - 2003. All Rights Reserved. 

In addition, in case of contributions you must identify yourself as the 
originator of the contribution, if any, in a manner that reasonably allows 
subsequent recipients to identify the originator of the contribution. 
"Contribution" means changes and/or additions to the Program.

2.2 Commercial Distribution 
In addition to 2.1 the following applies to Commercial distributors:

Commercial distributors of software may accept certain responsibilities with 
respect to end users, business partners and the like. Each distributor who 
includes the Program in a commercial product offering should do so in a manner 
which does not create potential liability for Software AG. Therefore, if a 
distributor includes the Program in a commercial product offering, such 
distributor hereby agrees to defend and indemnify Software AG against any 
damages, losses and costs (collectively "Losses") arising from claims, lawsuits 
and other legal actions brought by a third party against Software AG caused by 
the acts or omissions of the distributor in connection with its distribution of 
the Program in a commercial product offering. The distributor must a) promptly 
notify Software AG in writing of such claim, and b) allow Software AG to 
control, and cooperate in the defense and any related settlement negotiations. 

3. Property Rights
Software AG is sole owner of the industrial and intellectual property rights and 
copyright to the Original Program and accompanying user documentation. 
References made in or on the Original Program to the copyright or to other 
industrial Property rights must not be altered, deleted or obliterated in any 
manner.

No title or interest in or to any trademarks, service marks, trade names or 
patents of Software AG is granted by this License.

The name of Software AG may not be used to endorse or promote products derived 
from this software without specific prior written permission.

4. NO WARRANTY
THE PROGRAM IS PROVIDED ONLY ON AN "AS IS" BASIS, WITHOUT WARRANTIES OR 
CONDITIONS OF ANY KIND, EITHER EXPRESSED OR IMPLIED INCLUDING, BUT NOT LIMITED 
TO THE IMPLIED WARRANTY OF MERCHANTABILITY, TITLE, FITNESS FOR PARTICULAR 
PURPOSE, OR NONINFRINGEMENT, AND ANY OTHER WARRANTY ARISING BY STATUTE, 
OPERATION OF LAW, COURSE OF DEALING OR PERFORMANCE OR USAGE OF TRADE.

5. DISCLAIMER OF LIABILITY
UNDER NO CIRCUMSTANCES SHALL SOFTWARE AG BE LIABLE FOR ANY DAMAGES WHATSOEVER, 
INCLUDING ANY SPECIAL, CONSEQUENTIAL, EXEMPLARY, INCIDENTAL OR INDIRECT DAMAGES 
(INCLUDING, BUT NOT LIMITED TO, LOSS OF PROFITS, REVENUES, DATA AND/OR USE OR 
OTHER FINANCIAL LOSS) ARISING OUT OF OR IN CONNECTION HEREWITH OR THE USE OF; 
OR INABILITY TO USE THE PROGRAM AND ITS DOCUMENTATION. SOME JURISDICTIONS DO NOT 
ALLOW THE EXCLUSION OR LIMITATION OF INCIDENTAL OR CONSEQUENTIAL DAMAGES; BUT MAY 
ALLOW LIABILITY TO BE LIMITED. IN SUCH CASES A PARTY'S LIABILITIES SHALL BE 
LIMITED TO EURO 25.

6. Updates and Maintenance
This License does not grant any rights, license or Interest in or to support, 
improvements, modifications, enhancements or updates to the Program, its 
Documentation and the Contributions. 

7. No Compatibility
This License does not grant compatibility with future versions of the Program.

8. Termination
Software AG reserves the right to terminate this License immediately for good 
cause, whereby good cause is understood e. g. as any breach of the license terms 
or any infringement of third party rights by Licensee.

9. Export Laws
It is your responsibility as licensee to comply with any export regulations 
applicable in licensee's jurisdiction. 

10. Additional Terms and Conditions
You further agree to comply with the Terms and Conditions of the Tamino Community 
available at http://developer.softwareag.com/tamino/legal.htm, which are subject 
to changes and which apply to this License. 

11. Miscellaneous
The Invalidity of any Provision shall not affect the other terms of this License 
Agreement. These License terms represent the complete and exclusive statement 
concerning this License between the Parties. No modification or amendment of 
these terms will be binding unless acknowledged in writing. These License terms 
shall be governed and construed by the Laws of the Federal Republic of Germany.

*/


// *****************************************************************
// PREREQUISITS:
//  In order to use the TaminoAPI class you will need a current PHP4
//  (at least 4.3.0) with the DOM XML extension installed.  This API
//  uses features of Tamino that are available as of version 3.x
//
// NOTE:
//  All functions that may be called directly contain JavaDoc-style
//  comments - the other functions are local helper functions and
//  should not be called directly.  The functions starting with
//  "print" are helper functions for displaying information useful
//  for debugging purposes.
//
// public functions are:
//  TaminoAPI()
//  admin()
//  closeCursor()
//  commit()
//  endSession()
//  define($sSchema)
//  delete($sInput)
//  diagnose($sFunctionName)
//  fetchCursor()
//  getCursorHandle()
//  getFullResultBody()
//  getFullResultDom()
//  getFullResultHeader()
//  getResultDomNode()
//  getResultHttpCode()
//  getResultMessageCode1()
//  getResultMessageCode2()
//  getResultMessageLine1()
//  getResultMessageLine2()
//  getResultMessageText1()
//  getResultMessageText2()
//  getRootDomNode()
//  getServerVersion()
//  openCursor()
//  plainUrlAddressing($sQuery)
//  process($sInput)
//  query($sQuery)
//  setCollection($sCollection)
//  setEncoding($sEncoding)
//  setHttpRequestMethod($sMethod)
//  setMediaType($sMediaType)
//  startSession()
//  undefine($sSchema)
//  xquery($sQuery)
// *****************************************************************

// operation constants
define ("TAMINO_OP_ADMIN", "_admin");
define ("TAMINO_OP_CONNECT", "_connect");
define ("TAMINO_OP_COMMIT", "_commit");
define ("TAMINO_OP_CURSOR", "_cursor");
define ("TAMINO_OP_DEFINE", "_define");
define ("TAMINO_OP_DELETE", "_delete");
define ("TAMINO_OP_DIAGNOSE", "_diagnose");
define ("TAMINO_OP_DISCONNECT", "_disconnect");
define ("TAMINO_OP_PROCESS", "_process");
define ("TAMINO_OP_UNDEFINE", "_undefine");
define ("TAMINO_OP_XQUERY", "_xquery");
define ("TAMINO_OP_XQL", "_XQL");
define ("TAMINO_OP_PURLA", "plainUrlAddressing");

// other constants
define ("TAMINO_MULTIPART_BOUNDARY", "TaminoApiMultipartBoundary");
define ("TAMINO_MULTIPART_BOUNDARY_START", "--".TAMINO_MULTIPART_BOUNDARY);
define ("TAMINO_MULTIPART_BOUNDARY_END", "--".TAMINO_MULTIPART_BOUNDARY."--");
define ("TAMINO_DEFINE_MODE_NORMAL", "");
define ("TAMINO_DEFINE_MODE_VALIDATE", "validate");
define ("TAMINO_DEFINE_MODE_TEST", "test");
define ("TAMINO_DEFINE_MODE_VALIDATETEST", "test,validate");


class TaminoAPI
{

    // ****************
    // MEMBER VARIABLES
    // ****************


    var $_sConnectHost;
    var $_iConnectPort;
    var $_sDatabaseName;
    var $_sAuthUsername;
    var $_sAuthPassword;

    var $_sCollection;

    var $_sServerVersion;

    var $_sRequestHeader;
    var $_sRequestBody;

    var $_sResultHeader;
    var $_sResultBody;
    var $_domResultBody;
    var $_bResultDomCreated;
    var $_iResultHttpCode;

    var $_iResultMessageCode1;
    var $_iResultMessageCode2;
    var $_sResultMessageText1;
    var $_sResultMessageText2;
    var $_sResultMessageLine1;
    var $_sResultMessageLine2;

    // cursors
    var $_bCursorOpened;
    var $_sCursorLastHandle;
    var $_sCursorScroll;
    var $_sCursorSensitive;

    // misc
    var $_bDebugOn;
    var $_bReturnValue;
    var $_sEncoding;
    var $_sHttpRequestMethod;
    var $_sMediaType;
    var $_sContentTransferEncoding;
    var $_sDefineMethod;

    // session information
    var $_bSessionActive;
    var $_iSessionId;
    var $_iSessionKey;

    // error information
    var $_sLastErrorMessage;
    var $_sLastErrorCode;

    // debug information
    var $_countCreateDom;
    var $_countFreeDom;



    // ****************
    // PUBLIC FUNCTIONS
    // ****************

  
    /**
    * @brief   set the content transfer encoding used in POST requests;
    *          by default no encoding is used
    * @param   $sContentTransferEncoding (in) any one of "base64" or "binary"
    */
    function setContentTransferEncoding($sContentTransferEncoding)
    {
        $this->_sContentTransferEncoding = $sContentTransferEncoding;
    }


    /**
    * @brief   set the character encoding, e.g. "iso-8859-1" or "UTF-8"
    * @param   $sEncoding (in) the character encoding as string
    */
    function setEncoding($sEncoding)
    {
        $this->_sEncoding = $sEncoding;
    }


    /**
    * @brief   set the HTTP request method used for requests except for queries
    *          via query() or xquery()
    * @param   $sMethod (in) request method, currently "POST" or "GET"
    * @return  true
    */
    function setHttpRequestMethod($sMethod)
    {
        if ($sMethod == "GET") {
           $this->_sHttpRequestMethod = "GET";
        } elseif ($sMethod == "POST") {
          $this->_sHttpRequestMethod = "POST";
        } else {
            return false;
        }
        return true;
    }


    /**
    * @brief   set the HTTP request method used for requests except for queries
    *          via query() or xquery()
    * @param   $sMethod (in) request method, currently "POST" or "GET"
    * @return  true
    */
    function setMediaType($sMediaType)
    {
        $this->_sMediaType = $sMediaType;
        return true;
    }


    /**
    * @brief   close the cursor identified by the given handler
    * @return  true on success, false on error
    */
    function closeCursor($sCursorHandle)
    {
        $this->_bReturnValue = true;

        $sParameters = "&_handle=".$sCursorHandle;

        $this->sendRequestUsingGet($this->_sCollection, TAMINO_OP_CURSOR, "close", $sParameters);
        if (!($this->_bReturnValue)) {
            return $this->_bReturnValue;
        } else {
            $this->getMessageFromResultDom();
        }
        return $this->_bReturnValue;
    }


    /**
    * @brief   open a cursor with the given parameters and return the
    *          cursor handler
    * @param   sScroll (in) value for the _SCROLL parameter
    * @param   sSensitive (in) value for the _SENSITIVE parameter
    * @return  true
    */
    function openCursor($sScroll, $sSensitive)
    {
        $this->_bCursorOpened = true;
        $this->_sCursorLastHandle = "";
        $this->_sCursorScroll = $sScroll;
        $this->_sCursorSensitive = $sSensitive;
        return true;
    }


    /**
    * @brief   send a diagnose command to Tamino
    * @param   sFunctionName (in) name of the diagnose command
    * @return  true on success, false on error
    */
    function diagnose($sFunctionName)
    {
        $this->_bReturnValue = true;
        $this->sendRequestUsingGet($this->_sCollection, TAMINO_OP_DIAGNOSE, $sFunctionName, "");
        if (!($this->_bReturnValue)) {
            return $this->_bReturnValue;
        } else {
            $this->getMessageFromResultDom();
        }
        return $this->_bReturnValue;
    }


    /**
    * @brief   send an admin command to Tamino
    * @param   sAdminCall (in) name of the admin function including parameters
    * @return  true on success, false on error
    */
    function admin($sAdminCall)
    {
        $this->_bReturnValue = true;
        $this->sendRequestUsingGet($this->_sCollection, TAMINO_OP_ADMIN, $sAdminCall, "");
        if (!($this->_bReturnValue)) {
            return $this->_bReturnValue;
        } else {
            $this->getMessageFromResultDom();
        }
        return $this->_bReturnValue;
    }    


    /**
    * @brief   fetch the data according to the given cursor parameters
    * @param   sCursorHandle (in) the handle string of the cursor
    * @param   iPosition (in) position where 1 is the first
    * @param   iQuantity (in) number of documents to return
    * @return  true on success, false on error
    */
    function fetchCursor($sCursorHandle, $iPosition, $iQuantity)
    {
        $this->_bReturnValue = true;

        $sParameters  = "&_position=".$iPosition;
        $sParameters .= "&_quantity=".$iQuantity;
        $sParameters .= "&_handle=".$sCursorHandle;

        $this->sendRequestUsingGet($this->_sCollection, TAMINO_OP_CURSOR, "fetch", $sParameters);
        if (!($this->_bReturnValue)) {
            return $this->_bReturnValue;
        } else {
            $this->getMessageFromResultDom();
        }
        return $this->_bReturnValue;
    }


    /**
    * @brief   get the handle of the last opened cursor
    * @return  cursor handle, false on error
    */
    function getCursorHandle()
    {
        if (strLen($this->_sCursorLastHandle) > 0) {
            return $this->_sCursorLastHandle;
        } else {
            return false;
        }
    }


    /**
    * @brief   get the first message code from the Tamino response
    * @return  first message code
    */
    function getResultMessageCode1()
    {
        return $this->_iResultMessageCode1;
    }


    /**
    * @brief   get the second message code from the Tamino response
    * @return  second message code
    */
    function getResultMessageCode2()
    {
        return $this->_iResultMessageCode2;
    }


    /**
    * @brief   get the first message text from the Tamino response
    * @return  first message text
    */
    function getResultMessageText1()
    {
        return $this->_sResultMessageText1;
    }


    /**
    * @brief   get the second message text from the Tamino response
    * @return  second message text
    */
    function getResultMessageText2()
    {
        return $this->_sResultMessageText2;
    }


    /**
    * @brief   get the first messageline text from the Tamino response
    * @return  first messageline text
    */
    function getResultMessageLine1()
    {
        return $this->_sResultMessageLine1;
    }


    /**
    * @brief   get the second messageline text from the Tamino response
    * @return  second messageline text
    */
    function getResultMessageLine2()
    {
        return $this->_sResultMessageLine2;
    }


    /**
    * @brief   get result body as a string
    * @return  result body, false on error
    */
    function getFullResultBody()
    {
        if ($this->_sResultBody) {
            return $this->_sResultBody;
        }
        return false;
    }


    /**
    * @brief   get result header as a string
    * @return  result header, false on error
    */
    function getFullResultHeader()
    {
        if ($this->_sResultHeader) {
            return $this->_sResultHeader;
        }
        return false;
    }


    /**
    * @brief   get a DOM repesenting the response document returned from Tamino
    * @return  pointer to the document node, false on error
    */
    function getFullResultDom()
    {
        if ($this->_bResultDomCreated) {
            return $this->_domResultBody;
        }
        return false;
    }


    /**
    * @brief   get a pointer to the result node in the DOM repesenting the response 
    *          document returned from Tamino
    * @return  pointer to the result node, false on error
    */
    function getResultDomNode()
    {
        $resultValue = false;

        if ( ($this->_bResultDomCreated) && ($this->_domResultBody) ) {
            $bFoundResultNode = false;

            $domnodeRootElement = $this->_domResultBody->documentElement;
            $domnodeCurrent = $domnodeRootElement->first_child();
            while ( ($domnodeCurrent != NULL) && ($bFoundResultNode == false) ) {
                if ($domnodeCurrent->nodeType == XML_ELEMENT_NODE) {
                    if ($domnodeCurrent->nodeName == "result") {
                        $resultValue = $domnodeCurrent;
                        $bFoundResultNode = true;
                    }
                }
                $domnodeCurrent = $domnodeCurrent->next_sibling();
            }
        }
        return $resultValue;
    }


    /**
    * @brief   get a pointer to the root node in the DOM repesenting the response 
    *          document returned from Tamino
    * @return  pointer to the root node, false on error
    */
    function getRootDomNode()
    {
        $resultValue = false;

        if ( ($this->_bResultDomCreated) && ($this->_domResultBody) ) {
            $resultValue = $this->_domResultBody->documentElement;
        }
        return $resultValue;
    }


    /**
    * @brief   get the HTTP response code of the last sent request
    * @return  HTTP response code
    */
    function getResultHttpCode()
    {
        return $this->_iResultHttpCode;
    }


    /**
    * @brief   send the given data to Tamino via the "_process" command
    * @param   $sInput (in) data to be stored in Tamino
    * @return  true on success, false on error
    */
    function process($sInput)
    {
        $this->_bReturnValue = true;
        $this->sendRequest($this->_sCollection, TAMINO_OP_PROCESS, $sInput);
        $this->getMessageFromResultDom();
        return $this->_bReturnValue;
    }


    /**
    * @brief   define the given XML Schema
    * @param   $sSchema (in) the XML Schema
    * @return  true on success, false on error
    */
    function define($sSchema)
    {
        $sCollection = "";
        $this->_bReturnValue = true;
        if (strLen($this->_sDefineMethod) == 0)
            $this->sendRequest($this->_sCollection, TAMINO_OP_DEFINE, $sSchema);
        else {
            if ($this->_sHttpRequestMethod == "GET") {
                $this->sendRequestUsingGet($sCollection, TAMINO_OP_DEFINE, $sSchema, "&_mode=".$this->_sDefineMethod);
            } else {
                $sMore = "Content-Disposition: form-data; name=\"_mode\"\r\n";
                $sMore .= "\r\n";
                $sMore .= $this->_sDefineMethod;
                $this->sendRequestUsingPost($sCollection, TAMINO_OP_DEFINE, $sSchema, $sMore);
            }
        }
        $this->getMessageFromResultDom();
        return $this->_bReturnValue;
    }


    /**
    * @brief   set the method used when defining a schema
    * @param   $sMethod (in) method: TAMINO_DEFINE_MODE_NORMAL, TAMINO_DEFINE_MODE_VALIDATE,
    *          TAMINO_DEFINE_MODE_TESTTAMINO_DEFINE_MODE_VALIDATETEST
    */
    function setDefineMode($sMethod)
    {
        $this->_sDefineMethod = $sMethod;
    }


    /**
    * @brief   undefine the Schema with the given name
    * @param   $sSchema (in) name of the Schema to undefine
    * @return  true on success, false on error
    */
    function undefine($sSchema)
    {
        $this->_bReturnValue = true;
        $this->sendRequest($this->_sCollection, TAMINO_OP_UNDEFINE, $sSchema);
        $this->getMessageFromResultDom();
        return $this->_bReturnValue;
    }


    /**
    * @brief   send a delete request to Tamino
    * @param   $sInput (in) X-Query epxression describing the document to be deleted
    * @return  true on success, false on error
    */
    function delete($sInput)
    {
        $this->_bReturnValue = true;
        $this->sendRequest($this->_sCollection, TAMINO_OP_DELETE, $sInput);
        $this->getMessageFromResultDom();
        return $this->_bReturnValue;
    }


    /**
    * @brief   send a plain URL addressing request to Tamino, starting with the doctype
    *          name, e.g. "nonXML/@3" will get the document with ino:id="3" in the
    *          doctype "nonXML"
    * @param   $sQuery (in) plain URL addressing request
    * @return  true on success, false on error
    */
    function plainUrlAddressing($sQuery)
    {
        $this->_bReturnValue = true;
        $this->sendRequest($this->_sCollection, TAMINO_OP_PURLA, $sQuery);
        return $this->_bReturnValue;
    }


    /**
    * @brief   set the username in the current object - note that only one
    *          user is allowed to access Tamino within one session, so care
    *          needs to be taken not to change the username within one active
    *          session
    * @param   $sAuthUsername (in)
    */
    function setUsername($sAuthUsername)
    {
        $this->_sAuthUsername = $sAuthUsername;
        return true;
    }


    /**
    * @brief   set the password in the current object
    * @param   $sAuthPassword (in)
    */
    function setPassword($sAuthPassword)
    {
        $this->_sAuthPassword = $sAuthPassword;
        return true;
    }


    /**
    * @brief   constructor for the TaminoAPI class
    * @param   $sConnectHost (in) name or IP address of the host where the web server hosting 
    *                             the Tamino web server module is running
    * @param   $iConnectPort (in) port on which the web server is listening
    * @param   $sDatabaseName (in) name of the Tamino database
    * @param   $sAuthUsername (in) name of the user to connect with - if an empty string is
    *                              set, no authentication header will be sent to Tamino
    * @param   $sAuthPassword (in) password of the user
    */
    function TaminoAPI($sConnectHost, $iConnectPort, $sDatabaseName, $sAuthUsername, $sAuthPassword)
    {
        // set passed values
        $this->_sConnectHost = $sConnectHost;
        $this->_iConnectPort = $iConnectPort;
        $this->_sDatabaseName = $sDatabaseName;
        $this->_sAuthUsername = $sAuthUsername;
        $this->_sAuthPassword = $sAuthPassword;

        // set initial default values
        $this->_bDebugOn = false;
        $this->_bSessionActive = false;
        $this->_sServerVersion = "unknown";
        $this->_bResultDomCreated = false;
        $this->_sEncoding = "UTF-8";
        $this->_sHttpRequestMethod = "GET";
        $this->_sCursorScroll = "";
        $this->_sCursorSensitive = "";
        $this->_bCursorOpened = false;
        $this->_sCursorLastHandle = "";
        $this->_sMediaType = "";
        $this->_countCreateDom = 0;
        $this->_countFreeDom = 0;
        $this->_sContentTransferEncoding = "";
    }


    /**
    * @brief   get the version of the Tamino server - this method will only return
    *          a correct value after a request has already been sent to Tamino
    * @return  version of server, false if no request has been sent to Tamino yet
    */
    function getServerVersion()
    {
        $this->_bReturnValue = true;
        $this->sendRequest("", TAMINO_OP_DIAGNOSE, "ping");
        $this->getFromHeaderVersion();
        if ($this->_bReturnValue) {
            $this->getMessageFromResultDom();
        }
        if ($this->_bReturnValue) {
            return $this->_sServerVersion;
        }
        return false;
    }
  
  
    /**
    * @brief   start a new session
    * @return  true on success, false on error
    */
    function startSession()
    {
        $this->_bReturnValue = true;
        $this->_bSessionActive = true;
        $this->sendRequest("", TAMINO_OP_CONNECT, "*");
        if ($this->_bReturnValue) {
            $this->getMessageFromResultDom();
            if ($this->_bReturnValue)
                $this->_bSessionActive = true;
            else
                $this->_bSessionActive = false;
        } else {
            $this->_bSessionActive = false;
        }
        return $this->_bReturnValue;
    }
  

    /**
    * @brief   send a commit command
    * @return  true on success, false on error
    */
    function commit()
    {
        $this->_bReturnValue = true;
        $this->sendRequest("", TAMINO_OP_COMMIT, "*");
        $this->getMessageFromResultDom();
        return $this->_bReturnValue;
    }


    /**
    * @brief   end the current session
    * @return  true on success, false on error or if no session is currently active
    */
    function endSession()
    {
        $this->_bReturnValue = true;
        if ($this->_bSessionActive) {
            $this->sendRequest("", TAMINO_OP_DISCONNECT, "*");
            $this->_iSessionId = 0;
            $this->_iSessionKey = 0;
            $this->getMessageFromResultDom();
            if ($this->_bReturnValue) {    
                $this->_bSessionActive = false;
            }
        } else {
            $_sLastErrorMessage = "cannot end session when no session is currently active";
            $this->_bReturnValue = false;
        }
        return $this->_bReturnValue;
    }
  
  
    /**
    * @brief   set the name of the collection which should be used for the following
    *          requests
    * @param   $sCollection (in) name or the collection
    */
    function setCollection($sCollection)
    {
        $this->_sCollection = $sCollection;
    }
  

    /**
    * @brief   send an X-Query query to Tamino
    * @param   $sQuery (in) X-Query query
    * @return  true on success, false on error
    */
    function query($sQuery)
    {
        $this->_bReturnValue = true;

        if ($this->_bCursorOpened) {
            $sParameters  = "&_cursor=open";
            $sParameters .= "&_scroll=".urlEncode($this->_sCursorScroll);
            $sParameters .= "&_sensitive=".urlEncode($this->_sCursorSensitive);
        } else {
            $sParameters = "";
        }
        $this->sendRequestUsingGet($this->_sCollection, TAMINO_OP_XQL, $sQuery, $sParameters);
        if (!($this->_bReturnValue)) {
            return $this->_bReturnValue;
        } else {
            $this->getMessageFromResultDom();
            if ($this->_bCursorOpened) {
                $this->_sCursorScroll = "";
                $this->_sCursorSensitive = "";
                $this->_bCursorOpened = false;
                $this->getCursorHandleFromResultDom();
            }
        }
        return $this->_bReturnValue;
    }


    /**
    * @brief   send an XQuery query to Tamino
    * @param   $sQuery (in) XQuery query
    * @return  true on success, false on error
    */
    function xquery($sQuery)
    {
        $this->_bReturnValue = true;

        if ($this->_bCursorOpened) {
            $sParameters  = "&_cursor=open";
            $sParameters .= "&_scroll=".urlEncode($this->_sCursorScroll);
            $sParameters .= "&_sensitive=".urlEncode($this->_sCursorSensitive);
        } else {
            $sParameters = "";
        }
        $this->sendRequestUsingGet($this->_sCollection, TAMINO_OP_XQUERY, $sQuery, $sParameters);
        if (!($this->_bReturnValue)) {
            return $this->_bReturnValue;
        } else {
            $this->getMessageFromResultDom();
            if ($this->_bCursorOpened) {
                $this->_sCursorScroll = "";
                $this->_sCursorSensitive = "";
                $this->_bCursorOpened = false;
                $this->getCursorHandleFromResultDom();
            }
        }
        return $this->_bReturnValue;
    }
  
  
    // *****************  
    // PRIVATE FUNCTIONS
    // *****************

  
    function createResultDom()
    {
        if ($this->_bResultDomCreated == false) 
        {
            $this->_domResultBody = domxml_open_mem($this->_sResultBody);
            $this->_countCreateDom++;
            $this->_bResultDomCreated = true;
        }
    }


    function freeResultDom()
    {
        $this->_domResultBody->free();
        $this->_countFreeDom++;
    }


    function getMessageFromResultDom()
    {
        $this->_iResultMessageCode1 = "";
        $this->_sResultMessageText1 = "";
        $this->_sResultMessageLine1 = "";
        $this->_iResultMessageCode2 = "";
        $this->_sResultMessageText2 = "";
        $this->_sResultMessageLine2 = "";

        if (!$this->_bResultDomCreated) {
            $this->createResultDom();
        }

        $count = 0;
        if ($this->_domResultBody) {
            $domnodeRootElement = $this->_domResultBody->documentElement;
            $domnodeCurrent = $domnodeRootElement->first_child();
            while ($domnodeCurrent != NULL) {
                if ($domnodeCurrent->nodeType == XML_ELEMENT_NODE) {
                    if ($domnodeCurrent->nodeName == "message") {
                        $nodelistAttributes = $domnodeCurrent->attributes();
                        $numAttributes = count($nodelistAttributes);
                        for ($i = 0; $i < $numAttributes; $i++) {
                            if ($nodelistAttributes[$i]->name() == "returnvalue") {
                                $sMessageText = "";
                                $sMessageLine = "";
                                $iReturnValue = intVal($nodelistAttributes[$i]->value());
                                $domnodeCurrent2 = $domnodeCurrent->first_child();
                                while ($domnodeCurrent2 != NULL) {
                                    if ($domnodeCurrent2->nodeType == XML_ELEMENT_NODE) {
                                        if ($domnodeCurrent2->nodeName == "messageline") {
                                            $sMessageLine = $domnodeCurrent2->get_content();
                                        } elseif ($domnodeCurrent2->nodeName == "messagetext") {
                                            $sMessageText = $domnodeCurrent2->get_content();
                                        }
                                    }
                                    $domnodeCurrent2 = $domnodeCurrent2->next_sibling();
                                }
                                if ($count == 0) {
                                    $this->_iResultMessageCode1 = $iReturnValue;
                                    $this->_sResultMessageText1 = $sMessageText;
                                    $this->_sResultMessageLine1 = $sMessageLine;
                                    $count++;
                                } else {
                                    $this->_iResultMessageCode2 = $iReturnValue;
                                    $this->_sResultMessageText2 = $sMessageText;
                                    $this->_sResultMessageLine2 = $sMessageLine;
                                }
                            }
                        }
                    }
                }
                $domnodeCurrent = $domnodeCurrent->next_sibling();
            }
        }
        if ( (intVal($this->_iResultMessageCode1) > 0) || (intVal($this->_iResultMessageCode2) > 0) )
        {
            $this->_bReturnValue = false;
        }
    }


    function getCursorHandleFromResultDom()
    {
        $bReturnValue = false;
        if (!$this->_bResultDomCreated) {
            $this->createResultDom();
        }

        $count = 0;
        if ($this->_domResultBody) {
            $domnodeRootElement = $this->_domResultBody->documentElement;
            $domnodeCurrent = $domnodeRootElement->first_child();
            while ($domnodeCurrent != NULL) {
                if ($domnodeCurrent->nodeType == XML_ELEMENT_NODE) {
                    if ($domnodeCurrent->nodeName == "cursor") {
                        $nodelistAttributes = $domnodeCurrent->attributes();
                        $numAttributes = count($nodelistAttributes);
                        for ($i = 0; $i < $numAttributes; $i++) {
                            if ($nodelistAttributes[$i]->name() == "handle") {
                                $this->_sCursorLastHandle = $nodelistAttributes[$i]->value();
                                $bReturnValue = true;
                            }
                        }
                    }
                }
                $domnodeCurrent = $domnodeCurrent->next_sibling();
            }
        }
        return $bReturnValue;
    }


    function sendRequest($sCollection, $sCommandType, $sCommandValue)
    {
        if ( ($this->_sHttpRequestMethod == "GET") || ($sCommandType == TAMINO_OP_PURLA) ){
            $this->sendRequestUsingGet($sCollection, $sCommandType, $sCommandValue, "");
        } else {
            $this->sendRequestUsingPost($sCollection, $sCommandType, $sCommandValue, "");
        }
    }


    function sendRequestUsingGet($sCollection, $sCommandType, $sCommandValue, $sParameters)
    {
        if ($this->_bResultDomCreated)
            $this->freeResultDom();
        $this->_sRequestHeader = "";
        $this->_sRequestBody = "";
        $this->_sResultHeader = "";
        $this->_sResultBody = "";
        $this->_bResultDomCreated = false;
        $this->_iResultMessageCode1 = 0;
        $this->_iResultMessageCode2 = 0;

        // open socket to web server
        $fp = fSockOpen($this->_sConnectHost, $this->_iConnectPort, $errno, $errstr, 60);
        if (!$fp) {
            $this->_iResultHttpCode = 404;
            $this->_bReturnValue = false;
        } else {
            // construct header
            if (strPos($this->_sDatabaseName, "/") > 0)
              $this->_sRequestHeader  .= "GET /".$this->_sDatabaseName;
            else
              $this->_sRequestHeader  .= "GET /tamino/".$this->_sDatabaseName;
            if (strLen($sCollection) > 0) {
                $this->_sRequestHeader .= "/".$sCollection;
            }
            if ($sCommandType == TAMINO_OP_PURLA) {
                $this->_sRequestHeader .= "/".$sCommandValue;
            } else {
                $this->_sRequestHeader .= "?".$sCommandType."=".urlEncode($sCommandValue)."&_ENCODING=".$this->_sEncoding;
            }
            if (strLen($sParameters) > 0) {
                $this->_sRequestHeader .= $sParameters;
            }
            $this->_sRequestHeader .= " HTTP/1.0\r\n";
            if ($this->_bSessionActive) {
                $this->_sRequestHeader .= "X-INO-Sessionid: ".$this->_iSessionId."\r\n";
                $this->_sRequestHeader .= "X-INO-Sessionkey: ".$this->_iSessionKey."\r\n";
            }
            $this->_sRequestHeader .= "Connection: close\r\n";
            if ($this->_sMediaType == "")
                $this->_sRequestHeader .= "Content-Type: text/xml; charset=".$this->_sEncoding."\r\n";
            else
                $this->_sRequestHeader .= "Content-Type: ".$this->_sMediaType."; charset=".$this->_sEncoding."\r\n";

            if ( (strLen($this->_sAuthUsername) > 0) && (strLen($this->_sAuthPassword) > 0) ) {
                $authString = $this->_sAuthUsername.":".$this->_sAuthPassword;
                $this->_sRequestHeader .= "Authorization: Basic ".base64_encode($authString)."\r\n";
            }
            $this->_sRequestHeader .= "\r\n";

            // send request
            fputs ($fp, $this->_sRequestHeader);

            // receive result
            $bReceiveHeader = true;
            while (!fEOF($fp)) {
                $plainLine = fGetS($fp, 4096);
                if ($bReceiveHeader) {
                    if ($plainLine == "\r\n") {
                        $bReceiveHeader = false;
                    } else {
                        $this->_sResultHeader .= $plainLine;
                    }
                } else {
                    $this->_sResultBody .= $plainLine;
                }
            }
      
            // close connection
            fClose($fp);

            $this->getFromHeaderResponseCode();
            if ($this->_iResultHttpCode != 200) {
                $this->_bReturnValue = false;
            }

            if ($this->_bSessionActive) {
                $this->getFromHeaderSessionId();
                $this->getFromHeaderSessionKey();
            }
        }
    }


    function sendRequestUsingPost($sCollection, $sCommandType, $sCommandValue, $sMoreParts)
    {
        if ($this->_bResultDomCreated)
            $this->freeResultDom();
        $this->_sRequestHeader = "";
        $this->_sRequestBody = "";
        $this->_sResultHeader = "";
        $this->_sResultBody = "";
        $this->_bResultDomCreated = false;
        $this->_iResultMessageCode1 = 0;
        $this->_iResultMessageCode2 = 0;

        // open socket to web server
        $fp = fSockOpen($this->_sConnectHost, $this->_iConnectPort, $errno, $errstr, 60);
        if (!$fp) {
            $this->_iResultHttpCode = 404;
            $this->_bReturnValue = false;
        } else {
            // construct body
            $this->_sRequestBody  = TAMINO_MULTIPART_BOUNDARY_START;
            $this->_sRequestBody .= "\r\n";
            $this->_sRequestBody .= "Content-Disposition: form-data; name=\"".$sCommandType."\"\r\n";
            if ($this->_sMediaType == "")
                $this->_sRequestBody .= "Content-Type: text/xml";
            else
                $this->_sRequestBody .= "Content-Type: ".$this->_sMediaType;
            if ($this->_sEncoding != "")
                $this->_sRequestBody .= "; charset=".$this->_sEncoding;
            $this->_sRequestBody .= "\r\n";
            if ($this->_sContentTransferEncoding != "")
            {
                if ($this->_sContentTransferEncoding == "binary")
                {
                    $this->_sRequestBody .= "content-transfer-encoding: binary\r\n";
                }
                elseif ($this->_sContentTransferEncoding == "base64")
                {
                    $this->_sRequestBody .= "content-transfer-encoding: base64\r\n";
                    $sCommandValue = base64_encode($sCommandValue);
                }
            }
            $this->_sRequestBody .= "\r\n";
            $this->_sRequestBody .= $sCommandValue;
            $this->_sRequestBody .= "\r\n";
            if (strLen($sMoreParts) > 0)
            {
              $this->_sRequestBody .= "\r\n";
              $this->_sRequestBody  .= TAMINO_MULTIPART_BOUNDARY_START;
              $this->_sRequestBody .= "\r\n";
              $this->_sRequestBody .= $sMoreParts;
              $this->_sRequestBody .= "\r\n";
            }
            $this->_sRequestBody .= TAMINO_MULTIPART_BOUNDARY_END;
            $this->_sRequestBody .= "\r\n";

            // construct header
            if (strPos($this->_sDatabaseName, "/") > 0)
              $this->_sRequestHeader  .= "POST /".$this->_sDatabaseName;
            else
              $this->_sRequestHeader  .= "POST /tamino/".$this->_sDatabaseName;
            if (strLen($sCollection) > 0) {
                $this->_sRequestHeader .= "/".$sCollection;
            }
            $this->_sRequestHeader .= " HTTP/1.0\r\n";
            if ($this->_bSessionActive) {
                $this->_sRequestHeader .= "X-INO-Sessionid: ".$this->_iSessionId."\r\n";
                $this->_sRequestHeader .= "X-INO-Sessionkey: ".$this->_iSessionKey."\r\n";
            }
            $this->_sRequestHeader .= "Content-Type: multipart/form-data; boundary=".TAMINO_MULTIPART_BOUNDARY."\r\n";
            if ( (strLen($this->_sAuthUsername) > 0) && (strLen($this->_sAuthPassword) > 0) ) {
                $authString = $this->_sAuthUsername.":".$this->_sAuthPassword;
                $this->_sRequestHeader .= "Authorization: Basic ".base64_encode($authString)."\r\n";
            }
            $this->_sRequestHeader .= "Content-Length: ".strLen($this->_sRequestBody)."\r\n";
            $this->_sRequestHeader .= "\r\n";

            // send request
            fputs ($fp, $this->_sRequestHeader.$this->_sRequestBody);

            // receive result
            $bReceiveHeader = true;
            while (!fEOF($fp)) {
                $plainLine = fGetS($fp, 4096);
                if ($bReceiveHeader) {
                    if ($plainLine == "\r\n") {
                        $bReceiveHeader = false;
                    } else {
                        $this->_sResultHeader .= $plainLine;
                    }
                } else {
                    $this->_sResultBody .= $plainLine;
                }
            }
      
            // close connection
            fClose($fp);

            $this->getFromHeaderResponseCode();
            if ($this->_iResultHttpCode != 200) {
                $this->_bReturnValue = false;
            }

            if ($this->_bSessionActive) {
                $this->getFromHeaderSessionId();
                $this->getFromHeaderSessionKey();
            }
        }
    }
  

    function getFromHeaderResponseCode()
    {
        $sLines = split("\r\n", $this->_sResultHeader);
        $sHttpResponse = subStr($sLines[0], 9, 3);
        $this->_iResultHttpCode = intVal($sHttpResponse);
    }


    function getFromHeaderVersion()
    {
        $sLines = split("\r\n", $this->_sResultHeader);
        $iNumLines = count($sLines);
        for ($i = 0; $i < $iNumLines; $i++) {
            if (subStr($sLines[$i], 0, 15) == "X-INO-Version: ") {
                $this->_sServerVersion = subStr($sLines[$i], 15, strLen($sLines[$i])-15);
            }
        }
    }

  
    function getFromHeaderSessionKey()
    {
        $sLines = split("\r\n", $this->_sResultHeader);
        $iNumLines = count($sLines);
        for ($i = 0; $i < $iNumLines; $i++) {
            if (subStr($sLines[$i], 0, 18) == "X-INO-Sessionkey: ") {
                $this->_iSessionKey = subStr($sLines[$i], 18, strLen($sLines[$i])-18);
            }
        }
    }
  
  
    function getFromHeaderSessionId()
    {
        $sLines = split("\r\n", $this->_sResultHeader);
        $iNumLines = count($sLines);
        for ($i = 0; $i < $iNumLines; $i++) {
            if (subStr($sLines[$i], 0, 17) == "X-INO-Sessionid: ") {
                $this->_iSessionId = intVal(subStr($sLines[$i], 17, strLen($sLines[$i])-17));
            }
        }
    }
  


    // **************************
    // DEBUGGING HELPER FUNCTIONS
    // **************************


    function printResultHeader()
    {
        echo "<table cellpadding=\"5\" cellspacing=\"0\" border=\"0\" bgcolor=\"#eeeeee\"><tr><td>\n";
        echo "<font face=\"courier new,courier\" size=\"2\">\n";    
        echo str_replace("\n", "<br />", str_replace(" ", "&nbsp;", htmlEntities($this->_sResultHeader)));
        echo "</font>\n";
        echo "</td></tr></table>\n";
    }
 
  
    function printResultBody()
    {
        $output = $this->_sResultBody;
        $output = str_replace("&", "&amp;", $output);
        $output = str_replace("<", "&lt;", $output);
        $output = str_replace(">", "&gt;", $output);
        $output = str_replace("'", "&apos;", $output);
        $output = str_replace('"', "&quot;", $output);
        echo "<table cellpadding=\"5\" cellspacing=\"0\" border=\"0\" bgcolor=\"#eeeeee\"><tr><td>\n";
        echo "<font face=\"courier new,courier\" size=\"2\">\n";    
        echo str_replace("\n", "<br />", str_replace(" ", "&nbsp;", $output));
        echo "</font>\n";
        echo "</td></tr></table>\n";
    }
  
  
    function printRequestHeader()
    {
        echo "<table cellpadding=\"5\" cellspacing=\"0\" border=\"0\" bgcolor=\"#eeeeee\"><tr><td>\n";
        echo "<font face=\"courier new,courier\" size=\"2\">\n";    
        echo str_replace("\n", "<br />", str_replace(" ", "&nbsp;", htmlEntities($this->_sRequestHeader)));
        echo "</font>\n";
        echo "</td></tr></table>\n";
    }


    function printRequestBody()
    {
        echo "<table cellpadding=\"5\" cellspacing=\"0\" border=\"0\" bgcolor=\"#eeeeee\"><tr><td>\n";
        echo "<font face=\"courier new,courier\" size=\"2\">\n";    
        echo str_replace("\n", "<br />", str_replace(" ", "&nbsp;", htmlEntities($this->_sRequestBody)));
        echo "</font>\n";
        echo "</td></tr></table>\n";
    }

    function printInternals()
    {
        echo "<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" bgcolor=\"#aaaaaa\"><tr><td bgcolor=\"#aaaaaa\">\n";
        echo "<table cellpadding=\"2\" cellspacing=\"1\" border=\"0\" bgcolor=\"#aaaaaa\">\n";
        echo "<tr>\n";
        echo "<td bgcolor=\"#dddddd\"><font face=\"arial,helvetica\" size=\"2\">_countCreateDom</font></td>\n";
        echo "<td bgcolor=\"#eeeeee\"><font face=\"arial,helvetica\" size=\"2\">".$this->_countCreateDom."</font></td>\n";
        echo "</tr>\n"; 
        echo "<tr>\n";
        echo "<td bgcolor=\"#dddddd\"><font face=\"arial,helvetica\" size=\"2\">_countFreeDom</font></td>\n";
        echo "<td bgcolor=\"#eeeeee\"><font face=\"arial,helvetica\" size=\"2\">".$this->_countFreeDom."</font></td>\n";
        echo "</tr>\n";
        echo "</table>";
        echo "</td></tr></table>\n";
    }

  
    function printContext()
    {
        echo "<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" bgcolor=\"#aaaaaa\"><tr><td bgcolor=\"#aaaaaa\">\n";
        echo "<table cellpadding=\"2\" cellspacing=\"1\" border=\"0\" bgcolor=\"#aaaaaa\">\n";
        echo "<tr>\n";
        echo "<td bgcolor=\"#dddddd\"><font face=\"arial,helvetica\" size=\"2\">Server Version</font></td>\n";
        echo "<td bgcolor=\"#eeeeee\"><font face=\"arial,helvetica\" size=\"2\">".$this->_sServerVersion."</font></td>\n";
        echo "</tr>\n"; 
        echo "<tr>\n";
        echo "<td bgcolor=\"#dddddd\"><font face=\"arial,helvetica\" size=\"2\">Session Active</font></td>\n";
        echo "<td bgcolor=\"#eeeeee\"><font face=\"arial,helvetica\" size=\"2\">";
        if ($this->_bSessionActive) {
            echo "yes";
        } else {
            echo "no";
        }
        echo "</font></td>\n";
        echo "</tr>\n";   
        echo "<tr>\n";
        echo "<td bgcolor=\"#dddddd\"><font face=\"arial,helvetica\" size=\"2\">Session Id</font></td>\n";
        echo "<td bgcolor=\"#eeeeee\"><font face=\"arial,helvetica\" size=\"2\">".$this->_iSessionId."</font></td>\n";
        echo "</tr>\n";
        echo "<tr>\n";
        echo "<td bgcolor=\"#dddddd\"><font face=\"arial,helvetica\" size=\"2\">Session Key</font></td>\n";
        echo "<td bgcolor=\"#eeeeee\"><font face=\"arial,helvetica\" size=\"2\">".$this->_iSessionKey."</font></td>\n";
        echo "</tr>\n";
        echo "<tr>\n";
        echo "<td bgcolor=\"#dddddd\"><font face=\"arial,helvetica\" size=\"2\">Result HTTP Code</font></td>\n";
        echo "<td bgcolor=\"#eeeeee\"><font face=\"arial,helvetica\" size=\"2\">".$this->_iResultHttpCode."</font></td>\n";
        echo "</tr>\n";
        echo "<tr>\n";
        echo "<td bgcolor=\"#dddddd\"><font face=\"arial,helvetica\" size=\"2\">Result Message Code 1</font></td>\n";
        echo "<td bgcolor=\"#eeeeee\"><font face=\"arial,helvetica\" size=\"2\">".$this->_iResultMessageCode1."</font></td>\n";
        echo "</tr>\n";
        echo "<tr>\n";
        echo "<td bgcolor=\"#dddddd\"><font face=\"arial,helvetica\" size=\"2\">Result Message Text 1</font></td>\n";
        echo "<td bgcolor=\"#eeeeee\"><font face=\"arial,helvetica\" size=\"2\">".$this->_sResultMessageText1."</font></td>\n";
        echo "</tr>\n";
        echo "<tr>\n";
        echo "<td bgcolor=\"#dddddd\"><font face=\"arial,helvetica\" size=\"2\">Result Message Line 1</font></td>\n";
        echo "<td bgcolor=\"#eeeeee\"><font face=\"arial,helvetica\" size=\"2\">".$this->_sResultMessageLine1."</font></td>\n";
        echo "</tr>\n";
        echo "<tr>\n";
        echo "<td bgcolor=\"#dddddd\"><font face=\"arial,helvetica\" size=\"2\">Result Message Code 2</font></td>\n";
        echo "<td bgcolor=\"#eeeeee\"><font face=\"arial,helvetica\" size=\"2\">".$this->_iResultMessageCode2."</font></td>\n";
        echo "</tr>\n";
        echo "<tr>\n";
        echo "<td bgcolor=\"#dddddd\"><font face=\"arial,helvetica\" size=\"2\">Result Message Text 2</font></td>\n";
        echo "<td bgcolor=\"#eeeeee\"><font face=\"arial,helvetica\" size=\"2\">".$this->_sResultMessageText2."</font></td>\n";
        echo "</tr>\n";
        echo "<tr>\n";
        echo "<td bgcolor=\"#dddddd\"><font face=\"arial,helvetica\" size=\"2\">Result Message Line 2</font></td>\n";
        echo "<td bgcolor=\"#eeeeee\"><font face=\"arial,helvetica\" size=\"2\">".$this->_sResultMessageLine2."</font></td>\n";
        echo "</tr>\n";
        echo "<tr>\n";
        echo "<td bgcolor=\"#dddddd\"><font face=\"arial,helvetica\" size=\"2\">Encoding</font></td>\n";
        echo "<td bgcolor=\"#eeeeee\"><font face=\"arial,helvetica\" size=\"2\">".$this->_sEncoding."</font></td>\n";
        echo "</tr>\n";
        echo "<tr>\n";
        echo "<td bgcolor=\"#dddddd\"><font face=\"arial,helvetica\" size=\"2\">HTTP Request Method</font></td>\n";
        echo "<td bgcolor=\"#eeeeee\"><font face=\"arial,helvetica\" size=\"2\">".$this->_sHttpRequestMethod."</font></td>\n";
        echo "</tr>\n";
        echo "<tr>\n";
        echo "<td bgcolor=\"#dddddd\"><font face=\"arial,helvetica\" size=\"2\">last cursor handle</font></td>\n";
        echo "<td bgcolor=\"#eeeeee\"><font face=\"arial,helvetica\" size=\"2\">".$this->_sCursorLastHandle."</font></td>\n";
        echo "</tr>\n";
        echo "</table>";
        echo "</td></tr></table>\n";
    }
  
  
    function debugOn()
    {
        $this->_bDebugOn = true;
    }
  
  
    function debugOff()
    {
        $this->_bDebugOn = false;
    }
}