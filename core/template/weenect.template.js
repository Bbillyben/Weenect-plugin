var weenectObjects;
if(!weenectObjects){
    weenectObjects = {
        maps: {},
        intervals: {},
        colors:{}, 
    };
}


function formatDate(date) {
    var d = new Date(date),
        month = '' + (d.getMonth() + 1),
        day = '' + d.getDate(),
        year = d.getFullYear(),
        hour = '' + d.getHours(),
        minute = '' + d.getMinutes(),
        sec = '' + d.getSeconds();

    if (month.length < 2) 
        month = '0' + month;
    if (day.length < 2) 
        day = '0' + day;
    if (hour.length < 2) 
        hour = '0' + hour;
    if (minute.length < 2) 
        minute = '0' + minute;
    if (sec.length < 2) 
        sec = '0' + sec;

    return [year, month, day].join('-') + ' ' + [hour, minute, sec].join(':');
}

function weenectLetterAvatar (name, size, color) {
    name  = name || '';
    size  = size || 60;

    var nameSplit = String(name).toUpperCase().split(' '),
        initials, canvas, context, dataURI;


    if (nameSplit.length == 1) {
        initials = nameSplit[0] ? nameSplit[0].charAt(0):'?';
    } else {
        initials = nameSplit[0].charAt(0) + nameSplit[1].charAt(0);
    }

    if (window.devicePixelRatio) {
        size = (size * window.devicePixelRatio);
    }
    
    canvas        = document.createElement('canvas');
    canvas.width  = size;
    canvas.height = size;
    context       = canvas.getContext("2d");


    // Calculer le rayon du cercle initial
    var radius = size / 3;
    var distRad = (size - 2*radius)/2;

    context.beginPath();
    context.moveTo(size/2,size); // Point de départ
    context.bezierCurveTo(size/3, size/2, distRad, 2*size/3, distRad, radius);
    context.arc(size/2, radius, radius, -Math.PI,  0);
    context.bezierCurveTo(size-distRad, 2*size/3, size-size/3, size/2, size/2,size);
    context.fillStyle = color;
    context.fill();
    // contour
    context.strokeStyle="#FFF";
    context.lineWidth=4;
    context.stroke();
    // texte
    context.font = Math.round(canvas.width/2)+"px Arial";
    context.textAlign = "center";
    context.fillStyle = "#FFF";
    context.fillText(initials, size / 2, size / 2);
    
    dataURI = canvas.toDataURL();
    canvas  = null;

    return dataURI;
}
function weenectZoneNameAvatar(name, size, color){

    // Mesurer la largeur et la hauteur du texte
    var textMeasureCanvas = document.createElement('canvas');
    var textMeasureContext = textMeasureCanvas.getContext("2d");
    textMeasureContext.font = "bold "+Math.round(size) + "px Arial";
    var textWidth = textMeasureContext.measureText(name).width;
    var textHeight = Math.round(size)
    var padding = 5;


    canvas        = document.createElement('canvas');
    canvas.width  = textWidth;
    canvas.height = textHeight;
    context       = canvas.getContext("2d");
    canvas.style.overflow = "visible";
    
    
    context.fillStyle = color;
    context.beginPath();
    context.roundRect (canvas.width/4-padding, 0, canvas.width/2+2*padding, canvas.height, size/3);
    context.fill();
    // texte
    context.font = "bold "+Math.round(size/2)+"px Arial";
    context.textAlign = "center";
    context.textBaseline  = "middle";
    context.fillStyle = "white";
    context.fillText(name, textWidth/2,textHeight/2);

    dataURI = {'canvas': canvas.toDataURL(), 'height': textHeight, 'width':textWidth};
    canvas  = null;

    return dataURI;


}
function weenectZoneAvatar(name, size, color){
    name  = name || '';
    size  = size || 60;
    thick = 4;
    radius = 8;
    if (window.devicePixelRatio) {
        size = (size * window.devicePixelRatio);
    }
    
    canvas        = document.createElement('canvas');
    canvas.width  = size;
    canvas.height = size;
    context       = canvas.getContext("2d");
    canvas.style.overflow = "visible";
    
    // Créer un dégradé de gris pour le corps de l'épingle
    var gradientBody = context.createLinearGradient((size+thick)/2,0 , (size-thick)/2, 0);
    gradientBody.addColorStop(0, '#808080'); // Gris foncé
    gradientBody.addColorStop(1, '#d3d3d3'); // Gris clair

    // Dessiner le corps de l'épingle de couture
    context.beginPath();
    context.moveTo((size-thick)/2, 0); // Point de départ
    context.lineTo((size-thick)/2, size); // Première extrémité
    context.lineTo((size+thick)/2, size); // Pointe de l'aiguille
    context.lineTo((size+thick)/2, 0); // Deuxième extrémité
    context.closePath();
    // Remplir avec le dégradé de gris
    context.fillStyle = gradientBody;
    context.fill();

    // Créer un dégradé de gris pour la tête de l'épingle
    var gradientHead = context.createRadialGradient(size/2-radius/2, 2*radius/3, radius/8, size/2-radius/2, 2*radius/3, radius);
    gradientHead.addColorStop(0, '#FFFFFF'); 
    gradientHead.addColorStop(1, color); // Gris clair

    // Dessiner la tête de l'épingle
    context.beginPath();
    
    context.arc(size/2, radius, radius, 0, 2 * Math.PI);
    // context.arc(size, size/2, size, 0, 2 * Math.PI);
    context.fillStyle = gradientHead;
    context.fill();

    dataURI = canvas.toDataURL();
    canvas  = null;

    return dataURI;
}
function weenectUpdateBattery(id, _options){
    
    var cmd = $('.cmd.weenect-battery[data-cmd_id='+id+']');
    cmd.empty().append(_options.display_value);
    var icon = 'fa-battery-empty text-danger';
    if(_options.display_value > 80){
        icon = 'fa-battery-full text-success';
    }else if(_options.display_value > 60){
        icon = 'fa-battery-three-quarters text-info';
    }else if(_options.display_value > 40){
        icon = 'fa-battery-half text-warning';
    }else if(_options.display_value > 20){
        icon = 'fa-battery-quarter text-danger';
    }
    cmd = $('.cmd.weenect-battery-icon[data-cmd_id='+id+']');
    cmd.find('i').attr('class', 'fa ' + icon);
}

function weenectUpdateSattelites(id, _options){
    var cmd = $('.cmd.weenect-satellites[data-cmd_id='+id+']');
    cmd.empty().append(_options.display_value);

    var icon = 'fa-satellite-dish';
    if(_options.display_value > 8){
        icon += ' text-success';
    }else if(_options.display_value > 5){
        icon += ' text-info';
    }else if(_options.display_value > 2){
        icon += ' text-warning';
    }else{
        icon += ' text-danger';
    }
    cmd = $('.cmd.weenect-satellites-icon[data-cmd_id='+id+']');
    cmd.find('i').attr('class', 'fa ' + icon);
}

function weenectUpdateLastSeen(id, _options){
    var cmd = $('.cmd.weenect-horodatage[data-cmd_id='+id+']');
    cmd.html(formatDate(_options.display_value));
}
function weenectUpdateCurrentZone(id, _options){
    var cmd = $('.cmd.weenect-current[data-cmd_id='+id+']'); 
    var zN = _options.display_value.replace(trackerName+'-',"");
    if(zN == 0 )zN='-';
    cmd.html(zN);
}

function weenectUpdateAccuracy(id, _options){
    var cmd = $('.cmd.weenect-precision[data-cmd_id='+id+']');
    if(_options.display_value){
        cmd.empty().append(_options.display_value);
    }else{
        cmd.empty();
    }
}

function weenectUpdateAddress(id, _options, eqId){
    
    var cmd = $('.cmd[data-cmd_id='+id+']');
    cmd.empty().append(_options.display_value);
    cmd.attr('title','Date : '+_options.collectDate);
}

function weenectUpdateHistory(eqId, points){
    //console.log(" weenectDrawHistory :"+eqId);
    var map = weenectObjects.maps[eqId];
    if(!map)return;
    map.histories["point"]=points;

    if(map.histories.feature._map)map.histories.feature.setLatLngs(points);
    heat_point = points.map(subArray => [...subArray, 1]);
    if(map.histories.heatFeature._map)map.histories.heatFeature.setLatLngs(heat_point);


    map.histories.dot_groupe.clearLayers();
    var color = weenectObjects.colors[eqId]??'red';
    var last=[0,0];
    for(var k in points ){
        coord=points[k];
        if(coord.toString()==last)continue;
        last = coord.toString();
        var circle = L.circleMarker(coord, {
            radius : 5,
            fillColor  : color,
            fillOpacity: 0.65,
            weight: 0
        }).addTo(map.histories.dot_groupe);
    }
}
function weenectUpdateHistoryDraw(eqId, heat=false){
    var map = weenectObjects.maps[eqId];
    map.histories['heat']=heat;
    if(heat == 1){
        map.histories.line_groupe.removeFrom(map.map);
        map.histories.dot_groupe.removeFrom(map.map);
        map.histories.heat_groupe.addTo(map.map);
    }else{
        map.histories.line_groupe.addTo(map.map);
        map.histories.dot_groupe.addTo(map.map);
        map.histories.heat_groupe.removeFrom(map.map);
    }
    weenectUpdateHistory(eqId,  weenectObjects.maps[eqId].histories.point);
    ////console.log(" -- weenectUpdateHistoryDraw :"+eqId+" / "+weenectObjects.heat[eqId]);

}
function weenectUpdateMarker(eqId, coords, cmdId){
    //console.log("weenectUpdateMarker :"+eqId+" / "+cmdId)
    for (const key in weenectObjects.maps){
        var map = weenectObjects.maps[key];
        if(map.markers[eqId]){
            map.markers[eqId].setLatLng(coords.split(','));
            if(map.circles[eqId]){
                map.circles[eqId].setLatLng(coords.split(','));
            }
            if(map.circles[eqId+"m"]){
                map.circles[eqId+"m"].setLatLng(coords.split(','));
            }
          if(map.histories && map.histories.feature && map.histories.hours){
          	  var date = new Date();
              var dateEnd = formatDate(date);
              var dateStart = formatDate(new Date(date.setHours(date.getHours()-map.histories.hours)));
              jeedom.history.get({
                  global: false,
                  cmd_id: cmdId,
                  dateStart: dateStart,
                  dateEnd: dateEnd,
                  context: {map: key, eqId: eqId},
                  success: function(result) {
                    if (result.data.length == 0) return false
                    var values = result.data.map(function(elt) {
                      return elt[1].split(',').map(function(coord) { return parseFloat(coord)}) });
                    weenectUpdateHistory(eqId, values);
                  }
              });
            }
        }
        weenectFocusFeatureGroup(key);
    }
}

function weenectUpdateCircleRadius(id, radius){
    for (const key in weenectObjects.maps){
        var map = weenectObjects.maps[key];
        if(map.circles[id]){
            map.circles[id].setRadius(radius);
        }
        weenectFocusFeatureGroup(key);
    }
}

function weenectSetTheme(light, dark){
    
    if (!weenectObjects.theme) {
        weenectObjects.theme = light;
        if($('body')[0].hasAttribute('data-theme')){
            var currentTheme = $('body').attr('data-theme')
            if (currentTheme.endsWith('Dark')) {
                weenectObjects.theme = dark;
            }
        }
        $('body').on('changeThemeEvent', function(event,data){
            if(data == 'Dark'){
                weenectObjects.theme = dark;
            }else{
                weenectObjects.theme = light;
            }
            for (const key in weenectObjects.maps){
                var map = weenectObjects.maps[key];
                map.map.removeLayer(map.layer);
                map.layer = new L.TileLayer(weenectObjects.theme.url, weenectObjects.theme);
                map.map.addLayer(map.layer);
            }
        });
    }    
}

function weenectCreateMap(eqId, attribution, zoom, eqOptions={}){
    console.log("weenectCreateMap :"+JSON.stringify(eqOptions))
    var map = {markers:{}, circles:{}, histories:{}, options:{}};
    map.options=eqOptions;
    map.layer = new L.TileLayer('/plugins/weenect/core/ajax/weenect.proxy.php?url='+weenectObjects.theme.url, weenectObjects.theme);
    map.trackerGroup = L.featureGroup();
    map.zoneGroup = L.featureGroup();
    map.histories["line_groupe"] = L.featureGroup();
    map.histories["dot_groupe"] = L.featureGroup();
    map.histories["heat_groupe"] = L.featureGroup();
    map.map = L.map('map_' + eqId, {
        center: [51.5, -0.09],
        zoom: 15, 
        layers:[map.layer, map.zoneGroup,map.histories.line_groupe,map.histories.dot_groupe,  map.trackerGroup],
        attributionControl: attribution,
        zoomControl: zoom
    });
    weenectObjects.maps[eqId] = map;    

    // // control
    // var histMap = {
    //     "line": L.featureGroup([map.histories["dot_groupe"],  map.histories["line_groupe"]]),
    //     "HeatMap":map.histories["heat_groupe"],  
    //     "None":L.featureGroup(),
    // }
    // var layerControl = L.control.layers(histMap).addTo(map.map);
    // $('#map_'+eqId+' .leaflet-control-layers-base').on('change', function(e){
    //     weenectUpdateHistory(eqId,  weenectObjects.maps[eqId].histories.point);
    // });
}

function weenectCreateMarker(eqId, point){
    var id =point.id;
    //console.log("weenectCreateMarker :"+eqId+" / "+id)
    weenectObjects.colors[id] = point.color;
    var map = weenectObjects.maps[eqId];
    if(point.type  && point.type == "weenect"){
        weenectCreateTracker(eqId, point, id);
    }else if(map.options.pin && map.options.pin == 1){
        weenectCreateZone(eqId, point, id);      
    }
  	if(!point.coord.value){
    	return;
    }
    weenectCreateCircle(eqId, point, id);
    circle = weenectCreateCircle(eqId, {radius:{value:3},coord:point.coord, color:point.color}, id+"m", 1);   
  	if(point.history){
    	weenectCreateHistory(eqId, point, id);
    }
    if(point.type  && point.type == "weenect_zone" && map.options.zone_name== true){
        createZoneName(eqId, point, id+"zn");
    }
}

function createZoneName(eqId, point, id){
    var icSize = 22;
    var avatar = weenectZoneNameAvatar(point.name.value, icSize, point.color);
    if(!point.coord.value){
    	return;
    }
    var latlng = point.coord.value.split(',')
    var marker = L.marker(latlng, {icon:  L.icon({
        iconUrl: avatar.canvas,
        iconSize: [avatar.width, avatar.height],
        iconAnchor: [avatar.width/2, -avatar.height/2],
        // tooltipAnchor: [0,10],
        className: 'weenect-zone-name-'+id
    }),
          zIndexOffset:  1000
     })
    .addTo(weenectObjects.maps[eqId].zoneGroup);
    weenectObjects.maps[eqId].markers[id] = marker;

}
function weenectCreateTracker(eqId, point, id){
    //console.log("weenectCreateTracker :"+eqId+" / "+id)
    var avatar = weenectLetterAvatar(point.name.value, 60, point.color);
    if(!point.coord.value){
    	return;
    }
    var marker = L.marker(point.coord.value.split(','), {icon:  L.icon({
        iconUrl: avatar,
        iconSize: [40, 40],
        iconAnchor: [20, 40],
        popupAnchor: [-3, -76],
          className: 'weenect-avatar-'+id
    }),
          zIndexOffset:  1000
     }).addTo(weenectObjects.maps[eqId].trackerGroup);
    weenectObjects.maps[eqId].markers[id] = marker;
}
function weenectCreateZone(eqId, point, id){
    // console.log("weenectCreateZone :"+JSON.stringify(point))
    var avatar =  weenectZoneAvatar(point.name.value, 36, point.color);//'plugins/weenect/3rdparty/images/blank.png';//'plugins/weenect/3rdparty/images/blank.png';//weenectZoneAvatar(point.name.value, 36, point.color);//plugins/weenect/3rdparty/images/pin.png';
   if(!point.coord.value){
    	return;
    }
    var latlng = point.coord.value.split(',')
    var marker = L.marker(latlng, {icon:  L.icon({
        iconUrl: avatar,
        iconSize: [36, 36],
        iconAnchor: [18, 36],
        tooltipAnchor: [0,10],
        className: 'weenect-zone-avatar-'+id
    }),
          zIndexOffset:  1000
     })
    .addTo(weenectObjects.maps[eqId].zoneGroup);
    weenectObjects.maps[eqId].markers[id] = marker;
   
}
function weenectCreateHistory(eqId, point, id){
    //console.log("weenectCreateHistory :"+eqId+" / "+id)
        var history = L.polyline([], {
            color: point.color,
            fillColor: point.color,
            opacity: 0.5,
            weight: 3,
            linejoin:"round",
            lineCap: 'round',
            dashArray: '3, 10', 
            dashOffset: '0'
        }).addTo(weenectObjects.maps[eqId].histories.line_groupe);
       
        // for heat map 
        var heat = L.heatLayer([], 
            {
                radius: 25, 
                max :3
            }
            ).addTo(weenectObjects.maps[eqId].histories.heat_groupe);

            weenectObjects.maps[eqId].histories = {...weenectObjects.maps[eqId].histories, hours: point.history, feature: history, heatFeature: heat};
}

function weenectCreateCircle(eqId, point, id, fillAlpha=0.2){
    // console.log("weenectCreateCircle :"+JSON.stringify(point))
    if(point.type =="weenect"){
        var addMap = weenectObjects.maps[eqId].trackerGroup;
    }else{
        var addMap = weenectObjects.maps[eqId].zoneGroup;
    }
    var color = weenectObjects.maps[eqId].options.dynamic_color == 1 && point.is_in && point.is_in.value ==1 ? 'green' : point.color;
    if(point.radius && !isNaN(point.radius.value)){
        var circle = L.circle(point.coord.value.split(','), {
            radius: point.radius.value,
            color: color,
            fillColor: color,
            fillOpacity: fillAlpha,
              weight: 1
        }).addTo(addMap);
        weenectObjects.maps[eqId].circles[id] = circle;
    }
    return circle;
}

function weenectCreatePoint(eqId, point){
    var id = point.id;
    //console.log("weenectCreatePoint :"+eqId+" / "+id)
    if(point.battery){
        jeedom.cmd.update[point.battery.id] = function(_options) {
            weenectUpdateBattery(point.battery.id, _options);
        }
        jeedom.cmd.update[point.battery.id]({display_value:point.battery.value});
    }
    if(point.last_seen){
        jeedom.cmd.update[point.last_seen.id] = function(_options) {
            weenectUpdateLastSeen(point.last_seen.id, _options);
        }
        jeedom.cmd.update[point.last_seen.id]({display_value:point.last_seen.value});
    }
    if(point.current_zone){
        jeedom.cmd.update[point.current_zone.id] = function(_options) {
            weenectUpdateCurrentZone(point.current_zone.id, _options);
        }
        jeedom.cmd.update[point.current_zone.id]({display_value:point.current_zone.value});
    }

    if(point.radius){
        jeedom.cmd.update[point.radius.id] = function(_options) {
            weenectUpdateAccuracy(point.radius.id, _options);
            weenectUpdateCircleRadius(id, _options.display_value);
        }
        jeedom.cmd.update[point.radius.id]({display_value:point.radius.value});
    }
    if(point.satellites){
        jeedom.cmd.update[point.satellites.id] = function(_options) {
            weenectUpdateSattelites(point.satellites.id, _options);
        }
        jeedom.cmd.update[point.satellites.id]({display_value:point.satellites.value});
    }

    if(point.coord){
        jeedom.cmd.update[point.coord.id] = function(_options) {
            weenectUpdateMarker(id, _options.display_value, point.coord.id);
            weenectUpdateAddress(point.coord.id, _options, id);
        }
        jeedom.cmd.update[point.coord.id]({display_value:point.coord.value});
    }
}

function weenectFocusFeatureGroup(eqId){
    if(!Object.keys(weenectObjects.maps[eqId].markers).length){
  	    return;
    }
    weenectObjects.maps[eqId].map.fitBounds(weenectObjects.maps[eqId].trackerGroup.getBounds(), {padding: [230, 230]});
    if(weenectObjects.maps[eqId].customZoom){
        weenectObjects.maps[eqId].map.setZoom(weenectObjects.maps[eqId].customZoom);
    }
}

function weenectMapLoaded(eqId){
    setTimeout(function(){
        weenectObjects.maps[eqId].map.invalidateSize();
    },1);
}
