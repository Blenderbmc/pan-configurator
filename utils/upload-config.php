<?php

/*
 * Copyright (c) 2014 Palo Alto Networks, Inc. <info@paloaltonetworks.com>
 * Author: Christophe Painchaud <cpainchaud _AT_ paloaltonetworks.com>
 *
 * Permission to use, copy, modify, and distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.

 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
*/

print "***********************************************\n";
print "************ UPLOAD CONFIG UTILITY ************\n\n";

set_include_path( get_include_path() . PATH_SEPARATOR . dirname(__FILE__).'/../');
require_once("lib/panconfigurator.php");


function display_usage_and_exit($shortMessage = false)
{
    global $argv;
    print PH::boldText("USAGE: ")."php ".basename(__FILE__)." in=file.xml|api://... out=file.xml|api://... [more arguments]".
        "actions=action1:arg1 ['filter=(type is.group) or (name contains datacenter-)']\n";
    print "php ".basename(__FILE__)." help          : more help messages\n";
    print PH::boldText("\nExamples:\n");
    print " - php ".basename(__FILE__)." in=api://192.169.50.10/running-config out=local.xml'\n";
    print " - php ".basename(__FILE__)." in=local.xml out=api://192.169.50.10 preserveMgmtsystem injectUserAdmin2\n";
    print " - php ".basename(__FILE__)." in=local.xml out=api://192.169.50.10 toXpath=/config/shared/address\n";

    if( !$shortMessage )
    {
        print PH::boldText("\nListing available arguments\n\n");

        global $supportedArguments;

        ksort($supportedArguments);
        foreach( $supportedArguments as &$arg )
        {
            print " - ".PH::boldText($arg['niceName']);
            if( isset( $arg['argDesc']))
                print '='.$arg['argDesc'];
            //."=";
            if( isset($arg['shortHelp']))
                print "\n     ".$arg['shortHelp'];
            print "\n\n";
        }

        print "\n\n";
    }

    exit(1);
}

function display_error_usage_exit($msg)
{
    fwrite(STDERR, PH::boldText("\n**ERROR** ").$msg."\n\n");
    display_usage_and_exit(true);
}

print "\n";

$configInput = null;
$configOutput = null;
$errorMessage = '';
$debugAPI = false;
$loadConfigAfterUpload = false;



$supportedArguments = Array();
$supportedArguments['in'] = Array('niceName' => 'in', 'shortHelp' => 'input file or api. ie: in=config.xml  or in=api://192.168.1.1 or in=api://0018CAEC3@panorama.company.com', 'argDesc' => '[filename]|[api://IP]|[api://serial@IP]');
$supportedArguments['out'] = Array('niceName' => 'out', 'shortHelp' => 'output file to save config after changes. Only required when input is a file. ie: out=save-config.xml', 'argDesc' => '[filename]');
$supportedArguments['debugapi'] = Array('niceName' => 'DebugAPI', 'shortHelp' => 'prints API calls when they happen');
$supportedArguments['fromxpath'] = Array('niceName' => 'fromXpath', 'shortHelp' => 'select which part of the config to inject in destination');
$supportedArguments['toxpath'] = Array('niceName' => 'toXpath', 'shortHelp' => 'inject xml directly in some parts of the candidate config');
$supportedArguments['loadafterupload'] = Array('niceName' => 'loadAfterUpload', 'shortHelp' => 'load configuration after upload happened');
$supportedArguments['help'] = Array('niceName' => 'help', 'shortHelp' => 'this message');
$supportedArguments['apitimeout'] = Array('niceName' => 'apiTimeout', 'shortHelp' => 'in case API takes too long time to anwer, increase this value (default=60)');
$supportedArguments['preservemgmtconfig'] = Array('niceName' => 'preserveMgmtConfig', 'shortHelp' => 'this message');
$supportedArguments['preservemgmtusers'] = Array('niceName' => 'preserveMgmtUsers', 'shortHelp' => 'this message');
$supportedArguments['preservemgmtsystem'] = Array('niceName' => 'preserveMgmtSystem', 'shortHelp' => 'preserves what is in /config/devices/entry/deviceconfig/system');
$supportedArguments['injectuseradmin2'] = Array('niceName' => 'injectUserAdmin2', 'shortHelp' => 'adds user "admin2" with password "admin" in administrators');



PH::processCliArgs();


foreach ( PH::$args as $index => &$arg )
{
    if( !isset($supportedArguments[$index]) )
    {
        //var_dump($supportedArguments);
        display_error_usage_exit("unsupported argument provided: '$index'");
    }
}

if( isset(PH::$args['help']) )
{
    display_usage_and_exit();
}


if( ! isset(PH::$args['in']) )
    display_error_usage_exit('"in" is missing from arguments');
$configInput = PH::$args['in'];
if( !is_string($configInput) || strlen($configInput) < 1 )
    display_error_usage_exit('"in" argument is not a valid string');

if( ! isset(PH::$args['out']) )
    display_error_usage_exit('"out" is missing from arguments');
$configOutput = PH::$args['out'];
if( !is_string($configOutput) || strlen($configOutput) < 1 )
    display_error_usage_exit('"out" argument is not a valid string');


if( isset(PH::$args['debugapi'])  )
    $debugAPI = true;
else
    $debugAPI = false;

if( isset(PH::$args['loadafterupload']) )
{
    $loadConfigAfterUpload = true;
}

if( isset(PH::$args['fromxpath']) )
{
   if( !isset(PH::$args['toxpath']) )
   {
       display_error_usage_exit("'fromXpath' option must be used with 'toXpath'");
   }
}

if( !isset(PH::$args['apiTimeout']) )
{
    $apiTimeoutValue = 30;
}
else
    $apiTimeoutValue = PH::$args['apiTimeout'];


$doc = new DOMDocument();

print "Opening/downloading original configuration...";

//
// What kind of config input do we have.
//     File or API ?
//
$configInput = PH::processIOMethod($configInput, true);

if( $configInput['status'] == 'fail' )
{
    fwrite(STDERR, "\n\n**ERROR** " . $configInput['msg'] . "\n\n");exit(1);
}

if( $configInput['type'] == 'file' )
{
    print "{$configInput['filename']} ... ";
    $doc->Load($configInput['filename']);
}
elseif ( $configInput['type'] == 'api'  )
{
    if($debugAPI)
        $configInput['connector']->setShowApiCalls(true);

    print "{$configInput['connector']->apihost} ... ";

    /** @var PanAPIConnector $inputConnector */
    $inputConnector = $configInput['connector'];

    if( !isset($configInput['filename']) || $configInput['filename'] == '' || $configInput['filename'] == 'candidate-config' )
        $doc = $inputConnector->getCandidateConfig();
    elseif ( $configInput['filename'] == 'running-config' )
        $doc = $inputConnector->getRunningConfig();
    elseif ( $configInput['filename'] == 'merged-config' || $configInput['filename'] == 'merged' )
        $doc = $inputConnector->getMergedConfig();
    else
        $doc = $inputConnector->getSavedConfig($configInput['filename']);


}
else
    derr('not supported yet');

print " OK!!\n\n";

if( isset(PH::$args['fromxpath']) )
{
    print " * fromXPath is specified with value '".PH::$args['fromxpath']."'\n";
    $foundInputXpathList = DH::findXPath(PH::$args['fromxpath'], $doc);

    if( $foundInputXpathList === FALSE )
        derr("invalid xpath syntax");

    if( $foundInputXpathList->length == 0 )
        derr("xpath returned empty results");

    print "    * found ".$foundInputXpathList->length." results from Xpath:\n";

    foreach($foundInputXpathList as $xpath)
    {
        print "       - ".DH::elementToPanXPath($xpath)."\n";
    }

    print "\n";

}




print "Now saving/uploading that configuration to ";


//
// What kind of config output do we have.
//     File or API ?
//
$configOutput = PH::processIOMethod($configOutput, false);

if( $configOutput['status'] == 'fail' )
{
    fwrite(STDERR, "\n\n**ERROR** " . $configOutput['msg'] . "\n\n");exit(1);
}

if( $configOutput['type'] == 'file' )
{
    if( isset(PH::$args['toxpath']) )
    {
        derr("toXpath options was used, it's incompatible with a file output. Make a feature request !!!  ;)");
    }
    print "{$configOutput['filename']} ... ";
    $doc->save($configOutput['filename']);
}
elseif ( $configOutput['type'] == 'api'  )
{
    if( $debugAPI )
        $configOutput['connector']->setShowApiCalls(true);

    if( isset(PH::$args['toxpath']) )
    {
        print "Sending SET command to API...";
        if( isset(PH::$args['toxpath']) )
        {
            $stringToSend = '';
            foreach($foundInputXpathList as $xpath)
            {
                $stringToSend .= DH::dom_to_xml($xpath,-1, false);
            }
        }
        else
            $stringToSend = DH::dom_to_xml(DH::firstChildElement($doc),-1,false);

        $configOutput['connector']->sendSetRequest(PH::$args['toxpath'], $stringToSend);
        print "OK!";
    }
    else
    {
        if (  isset(PH::$args['preservemgmtconfig']) ||
              isset(PH::$args['preservemgmtusers']) )
        {
            print "Option 'preserveXXXXX was used, we will first download the running config ...";
            $runningConfig = $configOutput['connector']->getRunningConfig();
            print "OK!\n";

            $xpathQrunning = new DOMXPath($runningConfig);
            $xpathQlocal = new DOMXPath($doc);

            $xpathQueryList = Array();

            if (  isset(PH::$args['preservemgmtconfig']) ||
                isset(PH::$args['preservemgmtusers']) )
            {
                $xpathQueryList[] = '/config/mgt-config/users';
            }

            if (  isset(PH::$args['preservemgmtconfig']) ||
                isset(PH::$args['preservemgmtsystem']) )
            {
                $xpathQueryList[] = '/config/devices/entry/deviceconfig/system';
            }


            if (  isset(PH::$args['preservemgmtconfig']) )
            {
                $xpathQueryList[] = '/config/mgt-config';
                $xpathQueryList[] = "/config/devices/entry[@name='localhost.localdomain']/deviceconfig";
                $xpathQueryList[] = '/config/shared/authentication-profile';
                $xpathQueryList[] = '/config/shared/authentication-sequence';
                $xpathQueryList[] = '/config/shared/certificate';
                $xpathQueryList[] = '/config/shared/log-settings';
                $xpathQueryList[] = '/config/shared/local-user-database';
                $xpathQueryList[] = '/config/shared/admin-role';
            }

            foreach ($xpathQueryList as $xpathQuery)
            {
                $xpathResults = $xpathQrunning->query($xpathQuery);
                if ($xpathResults->length > 1)
                {
                    //var_dump($xpathResults);
                    derr('more than one one results found for xpath query: ' . $xpathQuery);
                }
                if ($xpathResults->length == 0)
                    $runningNodeFound = false;
                else
                    $runningNodeFound = true;

                $xpathResultsLocal = $xpathQlocal->query($xpathQuery);
                if ($xpathResultsLocal->length > 1)
                {
                    //var_dump($xpathResultsLocal);
                    derr('none or more than one one results found for xpath query: ' . $xpathQuery);
                }
                if ($xpathResultsLocal->length == 0)
                    $localNodeFound = false;
                else
                    $localNodeFound = true;

                if ($localNodeFound == false && $runningNodeFound == false)
                {
                    continue;
                }

                if ($localNodeFound && $runningNodeFound)
                {
                    $localParentNode = $xpathResultsLocal->item(0)->parentNode;
                    $localParentNode->removeChild($xpathResultsLocal->item(0));
                    $newNode = $doc->importNode($xpathResults->item(0), true);
                    $localParentNode->appendChild($newNode);
                    continue;
                }

                if ($localNodeFound == false && $runningNodeFound)
                {
                    $newXpath = explode('/', $xpathQuery);
                    if (count($newXpath) < 2)
                        derr('unsupported, debug xpath query: ' . $xpathQuery);

                    unset($newXpath[count($newXpath) - 1]);
                    $newXpath = implode('/', $newXpath);

                    $xpathResultsLocal = $xpathQlocal->query($newXpath);
                    if ($xpathResultsLocal->length != 1)
                    {
                        derr('unsupported, debug xpath query: ' . $newXpath);
                    }

                    $newNode = $doc->importNode($xpathResults->item(0), true);
                    $localParentNode = $xpathResultsLocal->item(0);
                    $localParentNode->appendChild($newNode);


                    continue;
                }

                //derr('unsupported');
            }

        }

        if( isset(PH::$args['injectuseradmin2']) )
        {
            $usersNode = DH::findXPathSingleEntryOrDie('/config/mgt-config/users', $doc);
            $newUserNode = DH::importXmlStringOrDie($doc, '<entry name="admin2"><phash>$1$bgnqjgob$HmenJzuuUAYmETzsMcdfJ/</phash><permissions><role-based><superuser>yes</superuser></role-based></permissions></entry>');
            $usersNode->appendChild($newUserNode);
        }

        if ($debugAPI)
            $configOutput['connector']->setShowApiCalls(true);

        if ($configOutput['filename'] !== null)
            $saveName = $configOutput['filename'];
        else
            $saveName = 'stage0.xml';

        print "{$configOutput['connector']->apihost}/$saveName ... ";

        $configOutput['connector']->uploadConfiguration(DH::firstChildElement($doc), $saveName, false);
    }
}
else
    derr('not supported yet');

print "OK!\n";


if( $loadConfigAfterUpload )
{
    print "Loading config in the firewall (will display warnings if any) ...\n";
    /** @var PanAPIConnector $targetConnector */
    $targetConnector = $configOutput['connector'];
    $xmlResponse = $targetConnector->sendCmdRequest('<load><config><from>' . $saveName . '</from></config></load>', true, 600);

    $xmlResponse = DH::firstChildElement($xmlResponse);

    if( $xmlResponse === false )
        derr('unexpected error !');

    $msgElement = DH::findFirstElement('msg', $xmlResponse);

    if( $msgElement !== false )
    {
        foreach($msgElement->childNodes as $msg )
        {
            if( $msg->nodeType != 1)
                continue;

            print " - ".$msg->nodeValue."\n";
        }
    }
}



print "\n************ DONE: UPLOAD CONFIG UTILITY ************\n";
print   "*****************************************************";
print "\n\n";




