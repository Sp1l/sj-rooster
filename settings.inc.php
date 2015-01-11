<?php

# Protect against web entry
if ( !defined( 'SINTJORIS' ) ) { exit; }

function jsArray($inArr) {
$str = '[';
foreach ($inArr as $elem) $str = $str . '"' . $elem . '",';
$str = substr($str,0,-1) . ']';
return $str;
}

function settings() {
global $thisScript;
$jaarLst = array();
$sectieLst = array();
$klasLst = array();

require_once('./dagRooster.class.php');
$memCache = new Memcache; // create memCache object
$memCache->connect('http.bachfreund.nl') or die ("Could not connect"); // connect to memCache
$pagina = new dagRoosterIndex($memCache);

$srcPage = new DOMDocument();
if (!$srcPage->loadHTML($pagina->getBody()) ) {
         foreach (libxml_get_errors() as $error) { echo $error->message; }
         libxml_clear_errors();
      }
$sdoc = simplexml_import_dom($srcPage);
foreach ( $sdoc->body->table->tr as $sourceRow ) {
  $klas = $sourceRow->td[2]->a->__toString();
#  $school = $klas[0];
#  $jaar = $klas[2];
#  $sectie = $klas[1];
#  $klasNum = $klas[3];
#  debug ('Joehoe |'.$klas.'|');
#  $klasLst[$klas[2]][$klas[1]] = $klas[3];
# debug ( $klas . " " . $klas[2] . $klas[1] . $klas[3] . " " . $klasLst);
$jaarLst[] = $klas[2];
$sectieLst[] = $klas[1];
$jaarSectieLst[] = $klas[2].$klas[1];
$klasLst[] = $klas[2] . $klas[1] . $klas[3];
}

$jaarLst = jsArray(array_unique($jaarLst));
$sectieLst = jsArray(array_unique($sectieLst));
$jaarSectieLst = jsArray(array_unique($jaarSectieLst));
$klasLst = jsArray($klasLst);

#document.getElementById("klas").value = curKlas + "A";
#document.getElementById("buttons").innerHTML = "Test";

echo '<!DOCTYPE html> 
<html>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
<head>
   <title>Geef klas in</title>
   <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
   <link rel="stylesheet" type="text/css" href="/sj.css" />
   <script src="typeof.js"></script>

<script>
var klassen = ' . $klasLst . ';
var jaren = ' . $jaarLst . ';
var secties = ' . $sectieLst . ';
var jaarSecties = ' . $jaarSectieLst . ';
var jaar = 0;
var sectie;

function displayButtons(level, label) {
var container = document.getElementById("buttons");
container.appendChild(document.createTextNode(""));
alert(jaar.toString);
container.innerHTML = label;

switch(label) {
  case "Schooljaar":
    call = "setJaar";
    tstArr = jaren;
    break;
  case "Afdeling":
    call = "setKlas";
    tstArr = jaarSecties;
    break;
  default: 
    break;
}

for (i=0 ; i < level.length ; i++) {
if ( label != "Schooljaar" ) if ( tstArr.indexOf(jaar + level[i][0]) == -1 ) continue;
var button = document.createElement("DIV");
button.setAttribute("class", "button");
button.setAttribute("onclick", "setJaar(\'"+level[i][0]+"\')");
button.appendChild(document.createTextNode(level[i][0]));
container.appendChild(button);
}
}

function setJaar(arg1) {
displayButtons(secties, "Afdeling")
}

function setInit() {
displayButtons(jaren, "Schooljaar");
}

</script>

</head>
<body onload="setInit()">

<form action="'. $thisScript .'" method="get">
Klas: <input id="klas" type="text" name="klas" value="J" style="text-transform: uppercase"/>
<input type="submit" value="Ga" />
</form>
<p id="buttons"></p>

</body>
</html>' ;
}

?>
