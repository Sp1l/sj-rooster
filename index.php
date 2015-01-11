<?php

$startTime = microtime(TRUE); 

define( 'SINTJORIS', TRUE );

$debug=TRUE;
// $debug=FALSE;

ini_set('display_errors', 'On');
#ini_set('display_errors', 'Off');
libxml_use_internal_errors(TRUE);

require_once('./functions.inc.php'); 
debug ('debug= '.$debug );

//header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
//header("Expires: Thu, 01 Jan 1970 00:00:00 GMT"); // Date in the past

header("Cache-Control: must-revalidate"); // HTTP/1.1
setlocale(LC_TIME,'nl_NL.ISO8859-1');
date_default_timezone_set('CET');
$cookieDom = 'sj.bachfreund.nl';
$cookiePath = '/';
$recheckInt = 60; // seconds

$klas = null;
$pageNo = null;    // index 0-4 (vandaag tot 5 dagen vooruit)
$targetDay = null; // dag van de week 1-5 (1=Ma)
$pageType = null;  // W = Wijziging, R = Rooster, P = Proefwerkweek
$thisScript = $_SERVER['PHP_SELF'] ;
$page = array ('' => null);

// ##### Verwerk input variabelen #####
verwerkInvoer(); // levert $klas en $pageNo en $type

if ( is_null($klas) ) {
  // geen url en geen cookie
  settings() ;
  exit;
}

// ##### Bepaal huidige dag en weer te geven dag #####
bepaalDag(); // levert $pageNo en $targetDay
$dagLabel = $weekday[$targetDay];

debug('pageNo '.$pageNo);
debug('targetDay '.$weekday[$targetDay]);
debug('pageType '.$pageType);

// ##### Maak een pagina #####
// Als we een klas hebben, dan kunnen we een pagina weergeven

// Haal de pagina op van de Sint Joris webserver of uit de cache
switch ($pageType) {
   case "W":  
      require_once('./dagWijziging.class.php');
      $pagina = new dagWijziging($dagLabel, $klas, $pageNo, $targetDay);
      break;
   case "R": 
      require_once('./dagRooster.class.php');
      $pagina = new dagRooster($dagLabel, $klas, $pageNo, $targetDay);
      break;
   case "P": 
      $pagina = null; // dagProefwerk($memCache, $dagLabel, $klas, $pageNo);
      break;
}

// Maak de Entity-tag en kijk of die dezelfde is als de opgevraagde
$uniquePage = $pagina->getKey($pagina->getETag().':'.$klas.':'.$pageNo);
if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH'] && ! $debug ) == $uniquePage) { 
   header("HTTP/1.1 304 Not Modified");
   exit; 
}

header("ETag: $uniquePage");

echo $pagina->getDagKlas();

echo '<!-- Script execution took ' . (microtime(TRUE) - $startTime) . ' seconds -->';
?>
