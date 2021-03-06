<?php

/*
 * Copyright (c) 2014-2017 Christophe Painchaud <shellescape _AT_ gmail.com>
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

class AppStore extends ObjStore
{
    /** @var array|App[] */
	public $apps=Array();
	
	public $parentCentralStore = null;
	
	public static $childn = 'App';


    /** @var null|AppStore  */
    public static $predefinedStore = null;

    /**
     * @return AppStore|null
     */
    public static function getPredefinedStore()
    {
        if( self::$predefinedStore !== null )
            return self::$predefinedStore;

        self::$predefinedStore = new AppStore(null);
        self::$predefinedStore->setName('predefined Apps');
        self::$predefinedStore->load_from_predefinedfile();

        return self::$predefinedStore;
    }
	
	public function __construct($owner)
	{
		$this->classn = &self::$childn;
		
		$this->owner = $owner;
		$this->o = &$this->apps;
		
		$this->findParentCentralStore();
	}

    /**
     * @param $name string
     * @param $ref
     * @return null|App
     */
	public function find($name, $ref=null)
	{
		return $this->findByName($name,$ref);
	}

    /**
     * @param $name string
     * @param $ref
     * @return null|App
     */
    public function findorCreate($name, $ref=null)
    {
        $f = $this->findByName($name,$ref);

        if( $f !== null )
            return $f;

        $f = $this->createTmp($name, $ref);

        return $f;
    }


	/**
	* return an array with all Apps in this store
	*
	*/
	public function apps()
	{
		return $this->o;
	}


	/**
	*
	* @ignore
	*/
	protected function findParentCentralStore()
	{
		$this->parentCentralStore = null;

		if( $this->owner )
		{
			$curo = $this;
			while( isset($curo->owner) && $curo->owner !== null )
			{

				if( isset($curo->owner->appStore) &&
					$curo->owner->appStore !== null			)
				{
					$this->parentCentralStore = $curo->owner->appStore;
					//print $this->toString()." : found a parent central store: ".$parentCentralStore->toString()."\n";
					return;
				}
				$curo = $curo->owner;
			}
		}

		//print $this->toString().": no parent store found\n";

	}

    public function load_from_domxml( DOMElement $xml )
    {
        foreach ($xml->childNodes as $appx)
        {
            if( $appx->nodeType != XML_ELEMENT_NODE )
                continue;

            $appName= DH::findAttribute('name', $appx);
            if( $appName === FALSE )
                derr("app name not found\n");

            $app = new App($appName, $this);
            $app->type = 'predefined';
            $this->add($app);

            $cursor = DH::findFirstElement('default', $appx);
            if ( $cursor === false )
                continue;

            $protocur = DH::findFirstElement('ident-by-ip-protocol', $cursor);
            if( $protocur !== false )
            {
                $app->proto = $protocur->textContent;
            }

            $icmpcur = DH::findFirstElement('ident-by-icmp-type', $cursor);
            if( $icmpcur !== false )
            {
                $app->icmpsub = $icmpcur->textContent;
            }

            $cursor = DH::findFirstElement('port', $cursor);
            if( $cursor === false )
                continue;

            foreach( $cursor->childNodes as $portx )
            {
                if( $portx->nodeType != XML_ELEMENT_NODE )
                    continue;

                /** @var  $portx DOMElement */

                $ex = explode('/', $portx->textContent );

                if( count($ex) != 2 )
                    derr('unsupported port description: '.$portx->textContent);

                if( $ex[0] == 'tcp' )
                {
                    $exports = explode(',', $ex[1]);
                    $ports = Array();

                    if( count($exports) < 1 )
                        derr('unsupported port description: '.$portx->textContent);

                    foreach( $exports as &$sport )
                    {
                        if( $sport == 'dynamic')
                        {
                            $ports[] = Array( 0 => 'dynamic' );
                            continue;
                        }
                        $tmpex = explode('-', $sport);
                        if( count($tmpex) < 2 )
                        {
                            $ports[] = Array( 0 => 'single' , 1 => $sport );
                            continue;
                        }

                        $ports[] = Array( 0 => 'range' , 1 => $tmpex[0], 2 => $tmpex[1] );

                    }
                    //print_r($ports);

                    if( $app->tcp === null )
                        $app->tcp = $ports;
                    else
                        $app->tcp = array_merge($app->tcp, $ports);
                }
                elseif( $ex[0] == 'udp' )
                {
                    $exports = explode(',', $ex[1]);
                    $ports = Array();

                    if( count($exports) < 1 )
                        derr('unsupported port description: '.$portx->textContent);

                    foreach( $exports as &$sport )
                    {
                        if( $sport == 'dynamic')
                        {
                            $ports[] = Array( 0 => 'dynamic' );
                            continue;
                        }
                        $tmpex = explode('-', $sport);
                        if( count($tmpex) < 2 )
                        {
                            $ports[] = Array( 0 => 'single' , 1 => $sport );
                            continue;
                        }

                        $ports[] = Array( 0 => 'range' , 1 => $tmpex[0], 2 => $tmpex[1] );

                    }
                    //print_r($ports);

                    if( $app->udp === null )
                        $app->udp = $ports;
                    else
                        $app->udp = array_merge($app->udp, $ports);
                }
                elseif( $ex[0] == 'icmp' )
                {
                    $app->icmp = $ex[1];
                }
                else
                    derr('unsupported port description: '.$portx->textContent);


            }

            $cursor = DH::findFirstElement('use-applications', $appx);
            if( $cursor !== false )
            {
                foreach($cursor->childNodes as $depNode)
                {
                    if( $depNode->nodeType != XML_ELEMENT_NODE )
                        continue;

                    $depName = $depNode->textContent;
                    if( strlen($depName) < 1 )
                        derr("dependency name length is < 0");
                    $depApp = $this->findOrCreate($depName);
                    $app->explicitUse[] = $depApp;
                }
            }

            $cursor = DH::findFirstElement('implicit-use-applications', $appx);
            if( $cursor !== false )
            {
                foreach($cursor->childNodes as $depNode)
                {
                    if( $depNode->nodeType != XML_ELEMENT_NODE )
                        continue;

                    $depName = $depNode->textContent;
                    if( strlen($depName) < 1 )
                        derr("dependency name length is < 0");
                    $depApp = $this->findOrCreate($depName);
                    $app->implicitUse[] = $depApp;
                }
            }
        }

    }

	public function loadcontainers_from_domxml( &$xmlDom )
	{
		foreach( $xmlDom->childNodes as $appx )
		{
			if( $appx->nodeType != 1 ) continue;

			$app = new App($appx->tagName, $this);
			$app->type = 'predefined';
			$this->add($app);

			$app->subapps = Array();

			//print "found container ".$app->name()."\n";

			$cursor = DH::findFirstElement('functions', $appx );
			if( $cursor === FALSE )
				continue;

			foreach( $cursor->childNodes as $function)
			{
				$app->subapps[] = $this->findOrCreate($function->textContent);
				//print "  subapp: ".$subapp->name()." type :".$subapp->type."\n";
			}

		}
	}


	public function load_from_predefinedfile( $filename = null )
	{
		if( $filename === null )
		{
			$filename = dirname(__FILE__).'/predefined.xml';
		}

        $xmlDoc = new DOMDocument();
        $xmlDoc->load($filename);

        $cursor = DH::findXPathSingleEntryOrDie('/predefined/application', $xmlDoc);

		$this->load_from_domxml( $cursor );

        $cursor = DH::findXPathSingleEntryOrDie('/predefined/application-container', $xmlDoc);

		$this->loadcontainers_from_domxml( $cursor );

		// fixing someone mess ;)
		$app = $this->findOrCreate('ftp');
		$app->tcp[] = Array( 0 => 'dynamic');
	}

}





