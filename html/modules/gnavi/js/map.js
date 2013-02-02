// ------------------------------------------------------------------------- //
//                      GNAVI - XOOPS area guide +                           //
//                        <http://xoops.iko-ze.net/>                         //
//                        main script for googleMap-v3                       //
//                        edit by tatsu                                      //
//                        <http://www.onwil.com/>                            //
// ------------------------------------------------------------------------- //

 	
	//Map types is 'ROADMAP','SATELLITE','HYBRID','TERRAIN
	//event.addListener  to 'idle' change from 'moveend' or 'zoomend'  This event is fired when the map becomes idle after panning or zooming.

/*-----Gnavi Global Params-----*/

	var gn_map=null;    
	var gn_geo=null;    
	var gn_mymk=null;   
	var gn_lg =null;    
	var gn_geoz=null;   
	var gn_mheight=null;
	var gn_url=null;    
	var gn_ulop=null;   
	var gn_mk=[];       
	var gn_desc=[];     
	var gn_l=0;         
	var gn_ic='';       
	var gn_mt=null;     
	var gn_kmls=null;   
	var gn_mykmls=null; 
	var gn_ep=0;
	var gn_drkm=0;
	var gn_it='';       
	var gn_ilt=0;       
	var gn_ilg=0;       
	var gn_iz=0;        
	var gn_pe=null;     
	var gn_pekey="";    
	var gn_infowindow=null;  
	var myLatlng=null;  
	var mypoint=null;  
	var mypoint2=null; 


/*-----Gnavi functions-----*/

function InitializeGmap(){
	
	if(!document.getElementById('map'))return false;
	

	// map original settings.  

	if(!gn_mt)gn_mt='ROADMAP';
	if(!gn_iz)gn_iz=12;
	gn_geoz=18;
	gn_infowindow = new google.maps.InfoWindow({disableAutoPan:true}); 
	myLatlng = new google.maps.LatLng(gn_ilt,gn_ilg);
	

	var myOptions = {
	     zoom: gn_iz,
	     center: myLatlng,
	     scaleControl :true,
	     mapTypeId:google.maps.MapTypeId[gn_mt]
		    };
	gn_map = new google.maps.Map(document.getElementById('map'), myOptions);


	return true;
}


//rss from item.php 
function gn_feedLoader(){

	var feed = new google.feeds.Feed(gn_feedlink);
	feed.setNumEntries(gn_feednum);   
      feed.load(function(result) {
        if (!result.error) {
			var container = document.getElementById("feed");
			var s="";
			s+="<ul>";
			for (var i = 0; i < result.feed.entries.length; i++) {
				var entry = result.feed.entries[i];
				s+="<li>"+df(entry.publishedDate)+" <a href='"+entry.link+"' target='_blank'>"+entry.title+"</a></li>";
			}
			s+="</ul>";
			s+="<div align='right'><img src='images/rss.gif' align='absmiddle'/> <a href='"+gn_feedlink+"' target='_blank'>"+result.feed.title+"</a>";
			var d = document.createElement("div");
			d.innerHTML=s;
			container.appendChild(d);
        }
      });

	function df(a){
		var d,y,m,d;
		d = new Date(a);
		y = d.getYear();
		m = d.getMonth() + 1;
		d = d.getDate();
		if (y < 2000) y += 1900;
		if (m < 10) m = "0" + m;
		if (d < 10) m = "0" + d;
		return y + "/" + m + "/" + d;
	}
}




function ShowItemGMap() {

	//show map on individual article.
        	if(gn_it) gn_mt = gn_it ;
		gn_iz = parseInt(gn_iz);
		if(!InitializeGmap())return;

		//setcenter
		var p = new Object();
		p.title = unescape(gn_lg['here']);


		if(gn_ic==''){
			gn_mymk = new google.maps.Marker({
        		position: myLatlng, 
        		map: gn_map,
       			title:p.title
    			}); 
		}else{
			var p = gn_ic.split(",");
			p.title = unescape(gn_lg['here']);
			var iconimage = new google.maps.MarkerImage(p[0],
			      new google.maps.Size(eval(p[1]), eval(p[2])),
			      new google.maps.Point(0,0),
			      new google.maps.Point(eval(p[6]), eval(p[7])));

			if(p[3]!=''){
			var iconshadow = new google.maps.MarkerImage(p[3],
			      new google.maps.Size(eval(p[4]), eval(p[5])),
			      new google.maps.Point(0,0),
			      new google.maps.Point(eval(p[6]), eval(p[7])));
				gn_mymk = new google.maps.Marker({
			        position:myLatlng, 
				//draggable : true, 
				icon: iconimage,
				shadow : iconshadow,
			        map: gn_map,
				title: p.title
				}); 
			}else{
			
			
			gn_mymk = new google.maps.Marker({
			        position:myLatlng, 
				//draggable : true, 
				icon: iconimage,
				//shadow : iconshadow,
			        map: gn_map,
				title: p.title
				}); 
			}

		}


}



function ShowGMap() {
// display many markers.

	// mashmap(gn_map);
		if(document.getElementById('mt').value){
			gn_mt = document.getElementById('mt').value;
		}else{
			gn_mt = 'ROADMAP';
		}
		gn_iz=parseInt(document.getElementById('z').value);
		gn_ilt=document.getElementById('lat').value;
		gn_ilg=document.getElementById('lng').value;
		
		if(!InitializeGmap())return;

		if(gn_drkm){
    		var k = gn_url+'/kml.php?'+gn_ulop;
			var g = new google.maps.KmlLayer(k,
			{ suppressInfoWindows: true,
			  map: gn_map});
			g.setMap(gn_map);
		}

		searchSales();

		//addListener
		google.maps.event.addListener(gn_map, 'idle', function() {
			var p = gn_map.getCenter();
			DrawLatLngTxt(p);
			var newZoomLevel = gn_map.getZoom();
	     		document.getElementById('z').value  =newZoomLevel;
	     		document.getElementById('sz').innerHTML  =newZoomLevel;
	    });
		

		//addListener
		google.maps.event.addListener(gn_map, 'maptypeid_changed', function() {
		document.getElementById('mt').value = gn_map.getMapTypeId().toUpperCase();
		});

		
		//addListener
		google.maps.event.addListener(gn_map, 'click', function(){
	         gn_infowindow.close(gn_map);
		 });

	 
		
		//setcenter
		var c = new google.maps.LatLng(document.getElementById('lat').value,document.getElementById('lng').value);
	  	gn_map.setCenter(c);
		gn_map.setZoom(parseInt(document.getElementById('z').value));
		if(gn_ep)right_click();


}


function right_click(){
	
	var r = "<div><input type='button' onclick='frmlatlng.submit()' style='padding:3px;font-size:13px;cursor:pointer;' value='"+unescape(gn_lg['additem'])+"'></div>";
	
	google.maps.event.addListener(gn_map, 'rightclick', function(point) {

	if(mypoint){mypoint.setMap(null);}


	var righticon = new google.maps.MarkerImage('http://www.google.com/mapfiles/gadget/arrowSmall80.png',
			      new google.maps.Size(31, 27),
			      new google.maps.Point(0,0),
			      new google.maps.Point(8, 27));
	var righticonsh = new google.maps.MarkerImage('http://www.google.com/mapfiles/gadget/arrowshadowSmall80.png',
			      new google.maps.Size(31, 27),
			      new google.maps.Point(0,0),
			      new google.maps.Point(8, 27));
	
	
	mypoint = new google.maps.Marker({
        position:point.latLng, 
	icon: righticon,
	shadow : righticonsh,
	map: gn_map
	});   

	  gn_infowindow.setContent(r);
 	  gn_infowindow.open(gn_map,mypoint);
	
	  DrawLatLngTxt(point.latLng);
	  
        
	google.maps.event.addListener(gn_map, 'click', function(){
          mypoint.setMap(null);
	});
	
	google.maps.event.addListener(mypoint, 'click', function(){
          gn_infowindow.open(gn_map,mypoint);
	});

	

        });

}


function searchSales(){
	
	//get markers by kml

    var k = gn_url+'/kml.php?mime=xml&'+gn_ulop;

    var opt = {
        method: 'GET',
        asynchronous: true,
        onComplete: func2
    };
    var conn = new Ajax.Request( k, opt );

}

function func2(req){

	//Initial setting markers.
	
	if(!gn_drkm){
		//create icons
	  	var nl = req.responseXML.getElementsByTagName( 'IconStyle' );
		var iconimage = []; 
		var iconshadow = [];
	  	for( var i = 0; i < nl.length; i++ ) {
		    var nli = nl[ i ];
		    var icd = eval(nli.getElementsByTagName( 'icd' )[0].firstChild.nodeValue);
		    var iimg = nli.getElementsByTagName( 'href' )[0].firstChild.nodeValue;
		    var shadow = nli.getElementsByTagName( 'shadow' )[0].firstChild.nodeValue;
		    var param = nli.getElementsByTagName( 'param' )[0].firstChild.nodeValue;
			var p = param.split(",");

		    iconimage[icd] = new google.maps.MarkerImage(iimg,
			      new google.maps.Size(eval(p[0]), eval(p[1])),
			      new google.maps.Point(0,0),
			      new google.maps.Point(eval(p[4]), eval(p[5])));
		    if(shadow!='x'){
		    iconshadow[icd] = new google.maps.MarkerImage(shadow,
			      new google.maps.Size(eval(p[2]), eval(p[3])),
			      new google.maps.Point(0,0),
			      new google.maps.Point(eval(p[4]), eval(p[5])));
			}
		
	  	}
	}

	

        var nl = req.responseXML.getElementsByTagName( 'Placemark' );
  	        
	var lst='';

	for( var i = 0; i < nl.length; i++ ) {
	    var nli = nl[ i ];
	    var lid = eval(nli.getElementsByTagName( 'lid' )[0].firstChild.nodeValue);
	    var name = nli.getElementsByTagName( 'name' )[0].firstChild.nodeValue;
	    var description = nli.getElementsByTagName( 'description' )[0].firstChild.nodeValue;
		
		//setup list
		lst += "<li><a href='javascript:void(0)' onclick='go("+lid+")'>"+name+"</a></li>";
		
		//setup infowindow
		var u='';
		if(gn_ulop) u = "&" + gn_ulop ; 
		gn_desc[lid]="<div style='width:250px;'><a href='"+gn_url+"/index.php?lid="+lid+u+"'>"+name+"</a><br />"+description+"</div>";
		
		}

	if(lst)
		lst = "<ul>" + lst + "</ul>";
	else
		lst = "<div>"+unescape(gn_lg['nodata'])+"</div>";

	document.getElementById("gn_mklist").innerHTML=lst;


	
	
  	for( var i = 0; i < nl.length; i++ ) {
	    var nli = nl[ i ];
	    var lid = eval(nli.getElementsByTagName( 'lid' )[0].firstChild.nodeValue);
	    var icd = eval(nli.getElementsByTagName( 'icd' )[0].firstChild.nodeValue);
	    var coordinates = nli.getElementsByTagName( 'coordinates' )[0].firstChild.nodeValue;
	    
		

		var p = coordinates.split(",");
		var ll=new google.maps.LatLng(eval(p[1]), eval(p[0]));


		//setup marker in map
		if(icd==0){
			gn_mk[lid] = new google.maps.Marker({
		        position: ll, 
			map: gn_map
			});
		}else{
			if(shadow!='x'){
			gn_mk[lid] = new google.maps.Marker({
		        position: ll, 
			icon: iconimage[icd],
			shadow : iconshadow[icd],
		        map: gn_map
			});
			}else{
			gn_mk[lid] = new google.maps.Marker({
		        position: ll, 
			icon: iconimage[icd],
			map: gn_map
			});
			}

		}   
		
		if(!gn_drkm){
			showInfo(lid);
			
		}

		
  	}


	
	
}



 // marker show Infowindow
function showInfo(lid){
        
	var hyoji = function()
	   {
		gn_infowindow.setContent(gn_desc[lid]);
		gn_infowindow.open(gn_map,gn_mk[lid]);
		gn_map.panTo(gn_mk[lid].getPosition());
		
           };
	
   	google.maps.event.addListener(gn_mk[lid], 'click', hyoji);
	
	if(gn_l>0){gn_infowindow.setContent(gn_desc[gn_l]);
		   gn_infowindow.open(gn_map,gn_mk[gn_l]);
		}

	

 }

function go(lid){
google.maps.event.trigger(gn_mk[lid], "click");

}


function InputGMap() {

		gn_mheight=document.getElementById("map").style.height;
		if(document.getElementById('mt').value){
			gn_mt = document.getElementById('mt').value;
		}else{
			gn_mt = 'ROADMAP';
		}

		//initialize
		if(!InitializeGmap())return;
		gn_geo = new google.maps.Geocoder();

		//setcenter
		var c = new google.maps.LatLng(document.getElementById('lat').value,document.getElementById('lng').value);
		gn_map.setCenter(c);
		gn_map.setZoom(parseInt(document.getElementById('z').value));

		//setmarker
		mypoint2 = new google.maps.Marker({
	        position:c, 
		draggable : true,
		map: gn_map,
		title : unescape(gn_lg['setpoint'])
		});

		
		//addListener
		google.maps.event.addListener(gn_map, 'click', function(point) {
		
			if (point) {
				mypoint2.setPosition(point.latLng);
				DrawLatLngTxt(point.latLng);
		   	}
	    	});

		google.maps.event.addListener(gn_map, 'idle', function(point) {
			var p = gn_map.getCenter(point);
			DrawLatLngTxt(p);
			var newZoomLevel = gn_map.getZoom();
	     		document.getElementById('z').value  =newZoomLevel;
	     		document.getElementById('sz').innerHTML  =newZoomLevel;
	    	});

		
	    	google.maps.event.addListener(mypoint2, 'dragend', function(point) {
	       	var p = mypoint2.getPosition(point);
			DrawLatLngTxt(p);
	   	});


		//addListener
		google.maps.event.addListener(gn_map, 'maptypeid_changed', function() {
			
			document.getElementById('mt').value = gn_map.getMapTypeId().toUpperCase();
		});

		ChangeMapArea(document.getElementById('set_latlng'));

	
}


function showAddress(address) {

//get latlng by address strings.
	if(address=='')return;
	if (gn_geo) {
	gn_geo.geocode( { 'address': address}, function(results, status) {
	      if (status == google.maps.GeocoderStatus.OK) {
		mypoint2.setPosition(results[0].geometry.location);
		gn_map.setCenter(results[0].geometry.location);
		if(gn_geoz<0){
					//gn_map.setCenter(point);
				}else{
					gn_map.setZoom(gn_geoz);
				}

	        gn_infowindow.setContent(document.createTextNode(address));
		gn_infowindow.open(gn_map,mypoint2);
		DrawLatLngTxt(results[0].geometry.location);
	      } else {
	        alert(address + unescape(gn_lg['notfound']));
	      }
	    });
	}

		//addListener
		google.maps.event.addListener(gn_map, 'click', function(){
		gn_infowindow.close(gn_map);
		 });

		google.maps.event.addListener(mypoint2, 'dragstart', function(point) {
	       	gn_infowindow.close(gn_map);
	   	});


}

function showAddress2(address) {

//get latlng by address strings.
	if(address=='')return;
	gn_geo = new google.maps.Geocoder();


	if (gn_geo) {
	gn_geo.geocode( { 'address': address}, function(results, status) {
	      if (status == google.maps.GeocoderStatus.OK) {

		gn_map.setCenter(results[0].geometry.location);
		if(gn_geoz<0){
					//gn_map.setCenter(point);
				}else{
					gn_map.setZoom(gn_geoz);
				}

	        gn_infowindow.setContent(document.createTextNode(address));
		gn_infowindow.setPosition(results[0].geometry.location);
		gn_infowindow.open(gn_map);
		DrawLatLngTxt(results[0].geometry.location);
		
	      } else {
	        //alert(address + unescape(gn_lg['notfound']));
	      }
	    });
	}

		//addListener
		google.maps.event.addListener(gn_map, 'click', function(){
	         gn_infowindow.close(gn_map);
		 });

}


function DrawLatLngTxt(p){
        
	document.getElementById('lat').value  = mround(p.lat());
	document.getElementById('slat').innerHTML  = mround(p.lat());
	document.getElementById('lng').value  = mround(p.lng());
	document.getElementById('slng').innerHTML  = mround(p.lng());

}




function ChangeMapArea(obj){

	if(obj.checked){
		// change for GMap V3 by nao-pon
		//document.getElementById("maparea").style.visibility = "hidden"; 
		//document.getElementById("map").style.height = "0px"; 
		document.getElementById("maparea").style.position = "absolute"; 
		document.getElementById("maparea").style.top = "-2000px"; 
		if(document.getElementById("geo"))document.getElementById("geo").style.visibility = "hidden"; 
	}else{
		// change for GMap V3 by nao-pon
		//document.getElementById("maparea").style.visibility = "visible"; 
		//document.getElementById("map").style.height = gn_mheight; 
		document.getElementById("maparea").style.position = "static"; 
		document.getElementById("maparea").style.top = ""; 
		if(document.getElementById("geo"))document.getElementById("geo").style.visibility = "visible"; 
	}

}



/*-----Ken's common func-----*/

function mround(value){
	return Math.round(parseFloat(value)*1000000)/1000000 ;
}

function include(u) {
	var h = document.getElementsByTagName( 'head' )[0];
	var s  = document.createElement( 'script' );
	s.charset = 'utf-8';
	s.type = 'text/javascript';
	s.src  = u;
	h.appendChild(s); 
}

function var_dumpj(mt,cnt,pre){
	var r="";
	for (i in mt){
		 r +=(pre+i+" = "+mt[i])+"<hr>";
		if(cnt>0 && typeof(mt[i])=="object"){
			r +=var_dumpj(mt[i],pre+"+----",cnt-1)
		}
	}
	return r;	
}
