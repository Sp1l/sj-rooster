<?php

/*class dagWijziging extends dag {
   function __construct() {
      parent::__construct();
   }

   public function getUrl() { 
      if ( $this->url == "" ) { 
         $urlBase = 'http://pcsintjoris.mwp.nl/Portals/0/sintjoris/Documenten/Roosters/Roosterwijzigingen';
         $this->url = $urlBase.'/Roosterwijziging%20'.$this->dag.'_bestanden/sheet001.htm' ; 
      }
      return $this->url;
   }

}
*/

class dagRoosterIndex {
   private $url = "http://pcsintjoris.mwp.nl/Portals/0/sintjoris/Documenten/Roosters/Roosters/klas/menu.html";
   private $indexArray = array ( "ETag"         => "", 
                                 "lastModified" => 0, 
                                 "lastChecked"  => 0, 
                                 "childPages"   => array () );
   private $memCache = null;
   private $body = null;
   private $headers = array () ;
   private $info = array () ;

   function __construct( $memCache ) {
debug ('Nieuwe dagRoosterIndex');
      global $recheckInt;
      $this->memCache = $memCache; // create memCache object
      $indexPage = $this->memCache->get($this->getKey("Index"));
      if ( $indexPage ) {
debug('RoosterIndex-pagina gevonden in cache'); 
         $this->indexArray = $indexPage; 
         if ( time() - $this->indexArray["lastChecked"] > $recheckInt ) {
debug('RoosterIndex-pagina is meer dan 1 minuut geleden gechecked');
            $this->getPage();
         }
      } 
      else { debug('RoosterIndex niet gevonden'); $this->getPage(); }
   }

   public function getUrl() { 
      return $this->url;
   }

   public function getKey( $key ) {
debug ('cleanedKey: '.str_replace(array('"'),'',"R:".$key));
      return str_replace(array('"'),'',"R:".$key);
   }

   private function delCache() {
debug('dagRoosterIndex::delCache()');
      foreach ( $this->indexArray["childPages"] as $childPage ) {
         $this->memCache->delete( $this->getKey($childPage) );
      }
      array_splice($this->indexArray["childPages"],0); // Delete all childPage elements
      if ( $this->indexArray["ETag"] != "" ) {
         $this->memCache->delete( $this->getKey($this->klasArray["ETag"].':body') );
         $this->memCache->delete( $this->getKey($this->klasArray["ETag"].':headers') );
         $this->memCache->delete( $this->getKey($this->klasArray["ETag"].':info') );
      }
   }

   private function getPage() {
debug('dagRoosterIndex::getPage()');
		global $recheckInt;
      // Check een dagPagina en doe er slimme dingen mee :D
debug('Check of er een nieuwere roosterIndexpagina is');
      $this->klasArray["lastChecked"] = time();
      $response = http_get( $this->getUrl(),
                            array('timeout' => 1, 'etag' => $this->indexArray["ETag"]),
                            $this->info );
      $this->headers = http_parse_headers($response);
      if ( $this->headers["Response Code"] == 200 
           and $this->headers["Response Code"] != 304 ) {
         // Pagina is veranderd, updaten!
debug('Indexpagina is vernieuwd');
         $this->body = http_parse_message($response)->body;
debug($this->body);
         $this->delCache();
         $ETag = $this->headers["Etag"];
         // Update de lokale cache
         $this->memCache->add($this->getKey($ETag.':body'), $this->body,0,691200);
         $this->memCache->add($this->getKey($ETag.':headers'), $this->headers,0,691200);
         $this->memCache->add($this->getKey($ETag.':info'), $this->info,0,691200);
         // Verander de status van deze dag-pagina
         $this->indexArray["ETag"] = $ETag;
         $this->indexArray["lastModified"] = strtotime($this->headers["Last-Modified"]);
      }
      $this->updateArray();
   }

   public function getBody() {
debug('dagRoosterIndex::getBody()');
      if ( is_null( $this->body ) )	 { 
         $this->body = $this->memCache->get($this->getKey($this->indexArray["ETag"].':body'));
         if ( ! $this->body ) { $this->getPage(); }
      }
      return $this->body;
   }

   private function updateArray() {
      $this->memCache->set( $this->getKey("Index"), $this->indexArray,0,691200 );
   }

}

class dagRooster {
   // Bevat alle informatie en functies voor de bron-pagina van een
   // bepaalde dag
   private $dagLabel, $klas, $pageNo, $targetDay;
//   private $pageNo = null;
	private $urlBase = 'http://pcsintjoris.mwp.nl/Portals/0/sintjoris/Documenten/Roosters/Roosters/klas';
   private $url = null;
   private $klasArray = array ( "Page"         => "",
                                "ETag"         => "", 
                                "lastModified" => 0, 
                                "lastChecked"  => 0, 
                                "childPages"   => array () );
   private $memCache = null;
   private $body = null;
   private $headers = array () ;
   private $info = array () ;

   function __construct( $dagLabel, $klas, $pageNo, $targetDay ) {
debug ('Nieuwe dagRooster');
      global $recheckInt;
      $this->dagLabel = $dagLabel;
      $this->klas = $klas;
      $this->pageNo = $pageNo;
      $this->targetDay = $targetDay;
      // Initialiseer de cache connectie
      $this->memCache = new Memcache; // create memCache object
      $this->memCache->connect('http.bachfreund.nl') or die ("Could not connect"); // connect to memCache
      $klasPage = $this->memCache->get($this->getKey($this->klas));
      if ( $klasPage ) {
debug('Rooster-pagina gevonden in cache'); 
         $this->klasArray = $klasPage; 
         if ( time() - $this->klasArray["lastChecked"] > $recheckInt ) {
debug('Rooster-pagina is meer dan 1 minuut geleden gechecked');
            $this->getRoosterPage();
         }
      } 
      else { debug('Rooster niet gevonden'); $this->getRoosterPage(); }
   }

   function __destruct() {
      $this->memCache->close();
   }

   public function getETag() { return $this->klasArray["ETag"] ; }

   public function getUrl( $page ) { 
      if ( $this->url == "" ) { 
         $this->url = $this->urlBase.'/'.$page ; 
      }
      return $this->url;
   }

   public function getKey( $key ) {
debug ('cleanedKey: '.str_replace(array('"'),'',"R:".$key));
      return str_replace(array('"'),'',"R:".$key);
   }

   public function addChildPage () {
      array_push( $this->klasArray["childPages"], $this->klas.':'.$this->pageNo );
      array_unique ( $this->klasArray["childPages"] );
      $this->updateKlasArray();
   }

   private function delCache () {
      foreach ( $this->klasArray["childPages"] as $childPage ) {
         $this->memCache->delete( $this->getKey($childPage) );
      }
      array_splice($this->klasArray["childPages"],0); // Delete all childPage elements
      if ( $this->klasArray["ETag"] != "" ) {
         $this->memCache->delete( $this->getKey($this->klasArray["ETag"].':body') );
         $this->memCache->delete( $this->getKey($this->klasArray["ETag"].':headers') );
         $this->memCache->delete( $this->getKey($this->klasArray["ETag"].':info') );
      }
   }

   private function getRoosterLink() {
debug('getRoosterLink() Haal de juiste link op voor deze klas');
      $doc = new DOMDocument();
		$roosterIndex = new dagRoosterIndex($this->memCache);
      if (!$doc->loadHTML( $roosterIndex->getBody()) ) {
         foreach (libxml_get_errors() as $error) {
            echo $error->message;
         }
         libxml_clear_errors();
      }
      $xpath = new DOMXPath($doc);
      $query = '//table/tr/td/a[text()="'.$this->klas.'"]/@href';
      $klasRooster = $xpath->query($query);
      if ( $klasRooster->length >= 1 ) { 
      	$this->klasArray["Page"]=$klasRooster->item(0)->textContent;
		} else { 
			debug( "Roosterpagina voor klas niet gevonden!" );
			$this->klasArray["Page"]='index.htm';
		}
      // Rooster van de klas is nu $roosterBase."/".$klasRoosterPage
debug('Link voor roosterpagina voor klas '.$this->klas.' = '.$this->klasArray["Page"]);      
   }

   private function getRoosterPage() {
      // Check een dagPagina en doe er slimme dingen mee :D
debug('getRoosterPage() Haal roosterpagina op');
      $this->klasArray["lastChecked"] = time();
      if ( $this->klasArray["Page"] == "" ) { $this->getRoosterLink(); }
      $response = http_get( $this->getUrl($this->klasArray["Page"] ),
                            array('timeout' => 1, 'etag' => $this->klasArray["ETag"]),
                            $this->info );
      $this->headers = http_parse_headers($response);
      if ( $this->headers["Response Code"] == 200 
           and $this->headers["Response Code"] != 304 ) {
         // Pagina is veranderd, updaten!
debug('Roosterpagina is vernieuwd');
         $this->body = http_parse_message($response)->body;
         $this->delCache();
         $ETag = $this->headers["Etag"];
         // Update de lokale cache
         $this->memCache->add($this->getKey($ETag.':body'), $this->body,MEMCACHE_COMPRESSED,691200);
         $this->memCache->add($this->getKey($ETag.':headers'), $this->headers,0,691200);
         $this->memCache->add($this->getKey($ETag.':info'), $this->info,0,691200);
         // Verander de status van deze dag-pagina
         $this->klasArray["ETag"] = $ETag;
         $this->klasArray["lastModified"] = strtotime($this->headers["Last-Modified"]);
      }
      $this->memCache->set( $this->getKey($this->klas), $this->klasArray,0,691200 );
   }

   public function getRoosterBody() {
debug('getRoosterBody()'); 
      if ( $this->body == "" ) { 
         $this->body = $this->memCache->get($this->getKey($this->klasArray["ETag"].':body'));
debug('dagBody niet gevonden');
         if ( ! $this->body ) { $this->getRoosterPage(); }
      }
      return $this->body;
   }

   private function updateKlasArray() {
      $this->memCache->set( $this->getKey($this->klas), $this->klasArray,0,691200 );
   }

   private function buildDagKlas () {
debug('buildDagKlas() Bouw een nieuwe dagpagina');
      global $weekday, $uren;
      $klas = $this->klas;
      $pageNo = $this->pageNo;
      $targetDay = $this->targetDay;
      
      include './htmlTemplate.inc.php';
      $html = new SimpleXMLElement($htmlTemplate);
      
      $srcPage = new DOMDocument();
      if (!$srcPage->loadHTML($this->getRoosterBody()) ) {
         foreach (libxml_get_errors() as $error) { echo $error->message; }
         libxml_clear_errors();
      }
      $xpath = new DOMXPath($srcPage);

//echo $srcPage->saveXML();
      // Table
      // Row 1    Koptekst
      // Column 1 Uurlabel
      // Dag 0 -> Kolom 2   + 2
      // Uur 1 -> Rij 2     + 1
// Label van rooster, e.g. "Rooster proefwerkweek 14 t/m 18 jan. 2013" /html/body/text()[2]
      $sdoc = simplexml_import_dom($srcPage);

      $kolom=$targetDay; // Dinsdag
      $cnt=0;

      $html->body->div->table->tr->td[0]->div = $weekday[weekDag($targetDay-1)];
      $html->body->div->table->tr->td[0]->div->addAttribute('onclick','prev("/'.$klas.'/'.($pageNo==0?4:$pageNo-1).'/R")'); 

      $html->body->div->table->tr->td[2]->div = $weekday[weekDag($targetDay+1)] ; //'Volgende'
      $html->body->div->table->tr->td[2]->div->addAttribute('onclick','next("/'.$klas.'/'.($pageNo==4?0:$pageNo+1).'/R")');

      $html->body->div->table->tr->td[1]->div = "W";
      $html->body->div->table->tr->td[1]->div->addAttribute('onclick','prev("/'.$klas.'/'.$pageNo.'/W")'); 
      
      $html->head[0]->title[0] = $klas.' '.$weekday[$targetDay] ;
      $html->body->div->p = $klas.' '.$weekday[$targetDay] ;
      
      // Voeg de regels toe aan de tabel
      $mainTable = $html->body->div->div[0]->table;

		if ( $this->klasArray["Page"] != 'index.htm' ) {

	  		$roosterLabel = $xpath->query('/html/body/text()[2]');
			$roosterLabel = trim($roosterLabel->item(0)->textContent);
			$html->body->div->p->addChild('br');
			$html->body->div->p->addChild('span',$roosterLabel);
			$html->body->div->p->span->addAttribute('class','versie');

	      foreach ( $sdoc->body->table[1]->tr as $sourceRow ) {
	         if ( $uur = array_search($sourceRow->td[0], $uren) and $sourceRow->td[$kolom] != "\xC2\xA0" ) {
	            // Zonder lege regels en de tabel-kop
	            $newRow = $mainTable->addChild('tr');
	            $td = $newRow->addChild('td',"Uur&nbsp;".$uur);
	            // echo "<tr><td>Uur&nbsp;".$uur."</td>".str_replace($klas."<br />","",$row->td[$kolom]->asXML())."</tr>";
	            $sourceData=$sourceRow->td[$kolom];
	            if ( $sourceData->table->count() == 1 ) {
	               $start = TRUE;
	               foreach ($sourceData->table->tr as $innerRow ) {
	                  if ( $start ) { 
	                     $td->addAttribute('rowspan',$sourceData->table->tr->count()); 
	                     $start = FALSE; } 
	                  else {
	                     $newRow = $mainTable->addChild('tr');
	                  }
	                  $tdArray = preg_split('|<br />|',$innerRow->td->asXML());
	                  foreach ( $tdArray as $newTd ) {
	                     // strip html-tags
	                     $newTd = trim(preg_replace('|<.*>|','',$newTd));
	                     if ( substr_count($newTd,substr($klas,0,3)) >= 1 ) {
	                        // split at space
	                        $strings = preg_split('/ /',$newTd);
	                        $newTd = '';
	                        foreach ( $strings as $string ) {
	                           if ( substr_count($string,substr($klas,0,3)) >= 1 ) {
	                              $newTd = $newTd . preg_replace('/('.substr($klas,0,3).'[A-N.]|'.$klas.')/','',$string); 
	                           }
	                        }
	                     }
	                     $newRow->addChild( 'td', $newTd );
	                  }
	               }
	            } else {
	               // Geen inner table
	               $tdArray = preg_split('|<br />|',$sourceData->asXML());
	               foreach ( $tdArray as $newTd ) {
	                  // strip html-tags
	                  $newTd = trim(preg_replace('|<.*>|','',$newTd));
	                  $newRow->addChild( 'td',  preg_replace('/('.substr($klas,0,3).'[A-N.]|'.$klas.')/','',$newTd));
	               }
	            };
	         }
	      }
		}
      
      $html->body->div->div[1]->addChild('a',"Origineel");
      $html->body->div->div[1]->a->addAttribute('href',$this->getUrl($this->klasArray["Page"]));

      $importnode = dom_import_simplexml($html);
      $outdoc = new DOMDocument('1.0');
      $importnode = $outdoc->importNode($importnode, true);
      $importnode = $outdoc->appendChild($importnode);
      return "<!DOCTYPE html>\n".$outdoc->saveHTML();
     
   }

   public function getDagKlas() {
      $cacheResult = $this->memCache->get($this->getKey($this->klas.":".$this->pageNo));
      if ( $cacheResult ) {
         return $cacheResult; } 
      else {
         $page = $this->buildDagKlas();
         $this->addDagKlas( $page );
         return $page;
      }
   }

   public function addDagKlas( $page ) {
      $expire = mktime(16,0,0); // Vandaag 4 uur
      if ( date("G") > 16 ) {
         $expire = $expire + 86400; // Morgen 4 uur
      }
      $this->memCache->set( $this->getKey($this->klas.":".$this->pageNo),$page,MEMCACHE_COMPRESSED,$expire );
      $this->addChildPage();
   }

} // End class dagPagina

?>