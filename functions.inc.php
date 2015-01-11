<?php

# Protect against web entry
if ( !defined( 'SINTJORIS' ) ) { exit; }

$maandToNum = array ("januari"=>1, "februari"=>2, "maart"=>3, "april"=>4, "mei"=>5, "juni"=>6, "juli"=>7, "augustus"=>8, "september"=>9, "oktober"=>10, "november"=>11, "december"=>12);
$maanden = array (1=>"januari", "februari", "maart", "april", "mei", "juni", "juli", "augustus", "september", "oktober", "november", "december");
$weekday = array(1=>"Maandag", "Dinsdag", "Woensdag", "Donderdag", "Vrijdag");
$uren = array(1=>"u1","u2","u3","u4","u5","u6","u7","u8"); 
//$dagTrans = array("Mon"=>"Ma","Tue"=>"Di","Wed"=>"Wo","Thu"=>"Do","Fri"=>"Vr","Sat"=>"Za","Sun"=>"Zo");
//$maandTrans = array("Jan"=>"Jan","Feb"=>"Feb","Maa"=>"Mrt","May"=>"Mei","Apr"=>"Apr","Jun"=>"Jun","Jul"=>"Jul","Aug"=>"Aug","Sep"=>"Sep","Oct"=>"Okt","Nov"=>"Nov","Dec"=>"Dec");
$jaren = array(1=>"Brugklas","2","3","4","5","6");

// memcache objects
// Dag : key Maandag, Dinsdag etc
//       bevat dagArray

function debug($text) {
   global $debug;
   if ( $debug ) { echo '<!-- '.$text.' -->'."\n"; }
}

function verwerkInvoer() {
   // levert klas, pageNo en pageType
   global $_GET, $_COOKIE, $klas, $pageNo, $pageType;
   
   // Haal de klas en de dag op uit de URL en het Cookie
   array_key_exists("klas", $_GET) ? $getKlas = strtoupper($_GET["klas"]) : $getKlas=''; 
   array_key_exists("dag", $_GET) ? $getDag = $_GET["dag"] : $getDag=0 ;
   array_key_exists("page", $_GET) ? $getPage = $_GET["page"] : $getPage="W" ;
   array_key_exists("klas", $_COOKIE) ? $cookieKlas = $_COOKIE["klas"] : $cookieKlas='';
   
   // Kijk of het een valide dag is
   preg_match('/^[0-4]$/',$getDag,$matches);
   $getMatched = count($matches);
   ( $getMatched == 1 ) ? $pageNo = (integer)$getDag : $pageNo=0 ;
   
   // Kijk of het een valide pagina type is
   preg_match('/[WRP]/',$getPage,$matches);
   $getMatched = count($matches);
   ( $getMatched == 1 ) ? $pageType=$getPage : $pageType="W";
   
   // Kijk of het een valide klas is
   preg_match('/^J[ABHMV][1-6][A-N]$/',$getKlas,$matches);
   $getMatched = count($matches) ;
   preg_match('/^J[ABHMV][1-6][A-N]$/',$cookieKlas,$matches);
   $cookieMatched = count($matches) ;
   if ( $getMatched == 1 ) {
     // url gaat voor cookie
     cookieSet($getKlas);
     $klas = $getKlas;
   } elseif ( $cookieMatched == 1 ) {
     $klas = $cookieKlas;
   }
}

function weekDag($dagIdx) {
  $dagIdx = ( $dagIdx % 5 ); 
  if ( $dagIdx == 0 ) $dagIdx = 5 ;
  return $dagIdx;
}

function bepaalDag() {
   global $pageNo, $targetDay;

   // dagNum is dagnummer van huidige dag
   $dagNum = date('w'); // 0 = Zo, 6 = Za
   if ( $dagNum == 0 ) $dagNum = 7 ; // 1=Ma, 7=Zo
   ( $dagNum >= 6 ) ? $startDag = 1 : $startDag = $dagNum ; // Za en Zo is de startdag Ma
   
   // targetDay is dagnummer van weer te geven pagina
   $targetDay = $startDag+$pageNo;

   // Na 16:00 niet vandaag maar morgen weergeven (niet op Za en Zo)
   if ( date("G") > 16 and $dagNum <= 5 ) { $targetDay++ ; } ; 
   
   $targetDay = weekDag($targetDay);
}

function cookieDel() {
  global $cookiePath, $cookieDom;
  setcookie("klas",'',1, $cookiePath, $cookieDom);
}

function cookieSet($klas) {
  global $cookiePath, $cookieDom;
  //$year = date('Y');
  //$month = date('m');
  if ( date('m') >= 8 ) { $expire=mktime (2,0,0,8,1,date('Y')+1) ; }
  else                  { $expire=mktime (2,0,0,8,1,date('Y')  ) ; }
  // echo $year . '-' . $month;

  setcookie("klas", $klas, $expire, $cookiePath, $cookieDom);
}


function printAreaDatumConv ( $htmlDatum ) {
   global $maandToNum;
   // Dag van de maand: een 0,1,2,3 gevolgd door een 0 tot 9
   preg_match('/[0-3]?[0-9]/',$htmlDatum,$matches);
   $htmlDagNo = $matches[0];
   
   // Dag van de week (iets)dag iets = bv. dins
   preg_match('/[a-z]+dag/i',$htmlDatum,$matches);
   $htmlDagLabel = $matches[0];

   // Maand
   preg_match('/(januari|februari|maart|april|mei|juni|july|augustus|september|oktober|november|december)/',$htmlDatum,$matches);
   $htmlMaand = $matches[0];

   return mktime(0,0,0,$maandToNum[$htmlMaand],$htmlDagNo);
}

function cleanKey ( $string ) { 
   $string = str_replace(array('"'),'',$string);
debug ('cleanedKey: '.$string);
   return $string;
   //return hash ( 'sha1' , $string ); 
}

require_once('./settings.inc.php'); 

function oldsettings() {
global $thisScript;
echo '<!DOCTYPE html>
<html>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
<head>
   <title>Geef klas in</title>
   <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
</head>
<body>

<form action="'. $thisScript .'" method="get">
Klas: <input type="text" name="klas" value="J" style="text-transform: uppercase"/>
<input type="submit" value="Ga" />
</form>

</body>
</html>' ;
}


?>
