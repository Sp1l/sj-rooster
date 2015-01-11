<?php

# Protect against web entry
if ( !defined( 'SINTJORIS' ) ) { exit; }

$htmlTemplate = <<<XML
<?xml version='1.0' standalone='yes'?>
<html>
 <head>
  <title></title>
  <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
  <link rel="stylesheet" type="text/css" href="/sj.css" />
  <script src="/sj.js" />
 </head>
 <body>
  <div id="all">
   <table id="nav" style="width: 100%"><tr>
    <td id="prev"><div class="button">Maandag</div></td>
    <td id="settings"><div class="button"><img src="/options.png" alt="opties"></img></div></td>
    <td id="next"><div class="button">Maandag</div></td>
   </tr></table>
   <p id="head"></p>
   <div id="table">
    <table>
    </table>
   </div>
   <div id="footer"></div>
  </div>
 </body>
</html>
XML;

?>
