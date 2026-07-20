<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>OpenLayers Street View</title>

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/ol@9.2.4/ol.css">

<script src="https://cdn.jsdelivr.net/npm/ol@9.2.4/dist/ol.js"></script>

<style>

html,
body{
margin:0;
padding:0;
height:100%;
overflow:hidden;
font-family:Arial;
}

#map{
width:100%;
height:100%;
}

#panel{

position:absolute;

top:15px;
left:15px;

background:white;

padding:15px;

border-radius:10px;

box-shadow:0 0 10px rgba(0,0,0,.3);

z-index:1000;

}

button{

padding:10px 20px;

cursor:pointer;

font-size:15px;

}

</style>

</head>

<body>

<div id="panel">

<button id="streetView">
Open Street View
</button>

<p>Click on map first.</p>

</div>

<div id="map"></div>

<script>

var lon=80.2707;
var lat=13.0827;

var markerSource=new ol.source.Vector();

var markerLayer=new ol.layer.Vector({

source:markerSource

});

var map=new ol.Map({

target:"map",

layers:[

new ol.layer.Tile({

source:new ol.source.OSM()

}),

markerLayer

],

view:new ol.View({

center:ol.proj.fromLonLat([lon,lat]),

zoom:17

})

});

addMarker(lon,lat);

function addMarker(lon,lat){

markerSource.clear();

var feature=new ol.Feature({

geometry:new ol.geom.Point(

ol.proj.fromLonLat([lon,lat])

)

});

markerSource.addFeature(feature);

}

map.on("click",function(evt){

var c=ol.proj.toLonLat(evt.coordinate);

lon=c[0];

lat=c[1];

addMarker(lon,lat);

});

document.getElementById("streetView").onclick=function(){

window.open(

"https://www.google.com/maps/@?api=1&map_action=pano&viewpoint="+lat+","+lon,

"_blank"

);

}

</script>

</body>
</html>
