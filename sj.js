var minWidth = 240;
var maxWidth = 300;
var width=window.innerWidth
|| document.documentElement.clientWidth
|| document.body.clientWidth;

if ( width > maxWidth ) { width = maxWidth ; } ;

var adjustBodyWidth = function(nPixels) {
  var m, w = document.body.style.width || document.body.clientWidth;
  if (m=w.match(/^(\d+)px$/)) {
    document.body.style.width = (Number(m[1]) + nPixels) + 'px';
  }
};

//alert ("Total Width: " + screen.width);
//alert ("Doc Width: " + document.body.style.width);

//alert(width+"px");

// document.body.style.width=100px;
//document.body.style.width = '100px';

// document.getElementsByTagName("html")[0].style.width=width+"px";

function prev(newurl)
{
window.location.assign(newurl);
}

function next(newurl)
{
window.location.assign(newurl);
}

