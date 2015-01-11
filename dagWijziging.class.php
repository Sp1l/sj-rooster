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

class dagWijziging {
   // Bevat alle informatie en functies voor de bron-pagina van een
   // bepaalde dag
   private $dag, $klas, $pageNo, $targetDay;
//   private  = null;
//   private $pageNo = null;
   private $url = null;
   private $dagArray = array ( "ETag"         => "", 
                               "lastModified" => 0, 
                               "lastChecked"  => 0, 
                               "childPages"   => array () );
   private $memCache = null;
   private $body = null;
   private $headers = array () ;
   private $info = array () ;

   function __construct( $dag, $klas, $pageNo, $targetDay ) {
debug ('Nieuwe dagWijziging');
      global $recheckInt;
      $this->dag = $dag;
      $this->klas = $klas;
      $this->pageNo = $pageNo;
      $this->targetDay = $targetDay;
      // Initialiseer de cache connectie
      $this->memCache = new Memcache; // create memCache object
      $this->memCache->connect('http.bachfreund.nl') or die ("Could not connect"); // connect to memCache
      $Dag = $this->memCache->get($this->getKey($this->dag));
      if ( $Dag ) {
debug('Dag-pagina gevonden in cache'); 
         $this->dagArray = $Dag; 
         if ( time() - $this->dagArray["lastChecked"] > $recheckInt ) {
debug('Dag-pagina is meer dan 1 minuut geleden gechecked');
            $this->getDagPage();
         }
      } 
      else { debug('Dag niet gevonden'); $this->getDagPage(); }
   }

   function __destruct() {
      $this->memCache->close();
   }

   public function getETag() { return $this->dagArray["ETag"] ; }

   private function getUrl() { 
      if ( $this->url == "" ) { 
         $urlBase = 'http://pcsintjoris.mwp.nl/Portals/0/sintjoris/Documenten/Roosters/Roosterwijzigingen';
         $this->url = $urlBase.'/Roosterwijziging%20'.$this->dag.'_bestanden/sheet001.htm' ; 
      }
      return $this->url;
   }

   public function getKey( $key ) {
      return str_replace(array('"'),'',"W:".$key);
debug ('cleanedKey: '.$key);
   }

   private function addChildPage () {
      array_push( $this->dagArray["childPages"], $this->klas.':'.$this->pageNo );
      array_unique ( $this->dagArray["childPages"] );
      $this->updateDagArray();
   }

   private function delCache () {
      foreach ( $this->dagArray["childPages"] as $childPage ) {
         $this->memCache->delete( $this->getKey($childPage) );
      }
      array_splice($this->dagArray["childPages"],0); // Delete all childPage elements
      if ( $this->dagArray["ETag"] != "" ) {
         $this->memCache->delete( $this->getKey($this->dagArray["ETag"].':body') );
         $this->memCache->delete( $this->getKey($this->dagArray["ETag"].':headers') );
         $this->memCache->delete( $this->getKey($this->dagArray["ETag"].':info') );
      }
   }

   private function getDagPage() {
      // Check een dagPagina en doe er slimme dingen mee :D
debug('Haal dagpagina op');
      $this->dagArray["lastChecked"] = time();
      $response = http_get( $this->getUrl(),
                            array('timeout' => 1, 'etag' => $this->dagArray["ETag"]),
                            $this->info );
      $this->headers = http_parse_headers($response);
      if ( $this->headers["Response Code"] == 200 
           and $this->headers["Response Code"] != 304 ) {
         // Pagina is veranderd, updaten!
debug('Dagpagina is vernieuwd');
         $this->body = http_parse_message($response)->body;
         $this->delCache();
         $ETag = $this->headers["Etag"];
         // Update de lokale cache
         $this->memCache->add($this->getKey($ETag.':body'), $this->body,MEMCACHE_COMPRESSED,691200);
         $this->memCache->add($this->getKey($ETag.':headers'), $this->headers,0,691200);
         $this->memCache->add($this->getKey($ETag.':info'), $this->info,0,691200);
         // Verander de status van deze dag-pagina
         $this->dagArray["ETag"] = $ETag;
         $this->dagArray["lastModified"] = strtotime($this->headers["Last-Modified"]);
      }
//print_r($this->dagArray);
      $this->updateDagArray(); 
   }

   private function getDagBody() {
      if ( $this->body == "" ) { 
         $this->body = $this->memCache->get($this->getKey($this->dagArray["ETag"].':body'));
//debug('dagBody niet gevonden');
         if ( ! $this->body ) { $this->getDagPage(); }
      }
      return $this->body;
   }

   private function updateDagArray() {
      $this->memCache->set( $this->getKey($this->dag), $this->dagArray,0,691200 );
   }

   private function buildDagKlas () {
      global $weekday;
      $klas = $this->klas;
      $pageNo = $this->pageNo;
      $targetDay = $this->targetDay;
      
      include './htmlTemplate.inc.php';
      $html = new SimpleXMLElement($htmlTemplate);
      
      $srcPage = new DOMDocument();
      if (!$srcPage->loadHTML($this->getDagBody()) ) {
         foreach (libxml_get_errors() as $error) { /* echo $error->message; */ }
         libxml_clear_errors();
      }
      
      $xpath = new DOMXPath($srcPage);
      
      // Haal de 'kop' uit de pagina en zet om naar een date
      $query = '//table/tr/td/a[@name="Print_Area"]';
      $htmlTitle = $xpath->query($query);
      $htmlDatum = printAreaDatumConv( $htmlTitle->item(0)->nodeValue );
      debug('htmlDatum = '.$htmlDatum);
      
      if ( $pageNo > 0 ) {
         $html->body->div->table->tr->td[0]->div = $weekday[weekDag($targetDay-1)];
         $html->body->div->table->tr->td[0]->div->addAttribute('onclick','prev("/'.$klas.'/'.($pageNo-1).'")'); 
      } else {
         $html->body->div->table->tr->td[0]->div->addAttribute('style','visibility: hidden');
      }
      
      if ( $pageNo < 4 ) {
         $html->body->div->table->tr->td[2]->div = $weekday[weekDag($targetDay+1)] ; //'Volgende'
         $html->body->div->table->tr->td[2]->div->addAttribute('onclick','next("/'.$klas.'/'.($pageNo+1).'")');
      } else {
         $html->body->div->table->tr->td[2]->div->addAttribute('style','visibility: hidden');
      }

      $html->body->div->table->tr->td[1]->div = "R";
      $html->body->div->table->tr->td[1]->div->addAttribute('onclick','prev("/'.$klas.'/'.$pageNo.'/R")'); 
      
      $prettyDate = ucwords( strftime('%a %e %b',$htmlDatum) );
      $html->head[0]->title[0] = $klas.' '.$prettyDate ;
      $html->body->div->p = $klas.' '.$prettyDate ;
      
      // Voeg de regels toe aan de tabel
      $table = $html->body->div->div[0]->table;
      if ( $htmlDatum >= strtotime(date('o-m-d')) ) {
      
        // Haal de tabel-brede regels uit het bron-document
        // Bv. 'Verkorte lessen (lessen van 40 minuten)'
        $query = '//table/tr/td[@colspan=6 and not(contains(.,"'.$htmlTitle->item(0)->nodeValue.'"))]';
        $spannedCols = $xpath->query($query);
        // if ( $spannedCols->length >= 1 ) { print $spannedCols->item(0)->nodeValue; }
        foreach ( $spannedCols as $col ) {
          $newrow = $table->addChild('tr');
          $td = $newrow->addChild('td', $col->nodeValue);
          $td->addAttribute('colspan','6');
          $td->addAttribute('class','em');
        }
      
         // Haal de regels uit de pagina die voor de klas zijn
         // WORKS!! $query = '//table/tr[./td[contains(.,"' . substr($klas,0,-1) . '")]]';
         // WORKS!! $query = '//table/tr[td="' . $klas . '"]';
         $query = '//table/tr[./td[contains(.,"' . $klas . '") or contains(.,"' . substr($klas,0,-1) . '") and string-length(.) > 4]]';
         $rows = $xpath->query($query);
      
        foreach ($rows as $row) {
        //  $newrow = $html->body->table->addChild('tr');
          $newrow = $table->addChild('tr');
          $cols = $row->childNodes;
          foreach ($cols as $col) {
            $nodeName = $col->nodeName;
            $nodeValue = $col->nodeValue;
            if ($nodeName = 'td' and trim($nodeValue) != "" ) {
              $td = $newrow->addChild('td',$nodeValue);
            }
          }
        } 
      } else {
        $newrow = $table->addChild('tr');
        $td = $newrow->addChild('td','Nog niet beschikbaar');
      }
      
      $html->body->div->div[1]->addChild('a',"Gewijzigd: ".ucwords(strftime('%a %e %b %H:%M',$this->dagArray["lastModified"] )));
      $html->body->div->div[1]->a->addAttribute('href',$this->getUrl() );
      
      // Convert SimpleXML to DOM and output HTML
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

   private function addDagKlas( $page ) {
      $expire = mktime(16,0,0); // Vandaag 4 uur
      if ( date("G") > 16 ) {
         $expire = $expire + 86400; // Morgen 4 uur
      }
      $this->memCache->set( $this->getKey($this->klas.":".$this->pageNo),$page,MEMCACHE_COMPRESSED,$expire );
      $this->addChildPage();
   }

} // End class dagPagina

?>
