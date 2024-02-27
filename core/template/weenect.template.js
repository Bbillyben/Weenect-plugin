var weenectObjects;
if(!weenectObjects){
    weenectObjects = {
        maps: {},
        intervals: {},
        colors:{}, 
        history:{}
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
        cmd.empty().append('Précision : ' + _options.display_value + 'm');
    }else{
        cmd.empty();
    }
}

function weenectUpdateAddress(id, _options, eqId){
    
    var cmd = $('.cmd[data-cmd_id='+id+']');
    cmd.empty().append(_options.display_value);
    cmd.attr('title','Date : '+_options.collectDate);
}

function weenectDrawHistory(eqId,id, points){
    var map = weenectObjects.maps[id];
    if(!map)return;
    if(weenectObjects.history[eqId]){
        for(var k in  weenectObjects.history[eqId]){
            circle=weenectObjects.history[eqId][k]
            map.featureGroup.removeLayer(circle);
        }
    }
    weenectObjects.history[eqId]=[];
    var color = weenectObjects.colors[eqId]??'red';
    var last=[0,0];
    for(var k in points ){
        coord=points[k];
        if(coord.toString()==last)continue;
        last = coord.toString();
        var circle = L.circle(coord, {
                radius: 2,
                fillColor: color,
                fillOpacity: 0.5,
                    weight: 0
            }).addTo(map.featureGroup);
            weenectObjects.history[eqId].push(circle);
    }
}

function weenectUpdateMarker(eqId, coords, cmdId){
    for (const key in weenectObjects.maps){
        var map = weenectObjects.maps[key];
        if(map.markers[eqId]){
            map.markers[eqId].setLatLng(coords.split(','));
            if(map.circles[eqId]){
                map.circles[eqId].setLatLng(coords.split(','));
            }
          if(map.histories[eqId] && map.histories[eqId].feature && map.histories[eqId].hours){
          	  var date = new Date();
              var dateEnd = formatDate(date);
              var dateStart = formatDate(new Date(date.setHours(date.getHours()-map.histories[eqId].hours)));
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
                    weenectObjects.maps[this.context.map].histories[result.eqLogic.logicalId].feature.setLatLngs(values);
                    weenectDrawHistory(eqId, key, values);
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

function weenectCreateMap(eqId, attribution, zoom){
    var map = {markers:{}, circles:{}, histories:{}};
    map.layer = new L.TileLayer('/plugins/weenect/core/ajax/weenect.proxy.php?url='+weenectObjects.theme.url, weenectObjects.theme);
    map.featureGroup = L.featureGroup();
    map.map = L.map('map_' + eqId, {
        center: [51.5, -0.09],
        zoom: 15, 
        layers:[map.layer, map.featureGroup],
        attributionControl: attribution,
        zoomControl: zoom
    });
    weenectObjects.maps[eqId] = map;    
}

function weenectCreateMarker(eqId, point){
    var id =point.id;
    weenectObjects.colors[id] = point.color;
    if(point.type  && point.type == "weenect"){
        weenectCreateTracker(eqId, point, id);
    }else if(point.pin && point.pin == 1){
        weenectCreateZone(eqId, point, id);      
    }
  	if(!point.coord.value){
    	return;
    }
    weenectCreateCircle(eqId, point, id);
    weenectCreateCircle(eqId, {radius:{value:3},coord:point.coord, color:point.color}, id+"m", 1);   
  	if(point.history){
    	weenectCreateHistory(eqId, point, id);
    }
}
function weenectCreateTracker(eqId, point, id){
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
     }).addTo(weenectObjects.maps[eqId].featureGroup);
    weenectObjects.maps[eqId].markers[id] = marker;
}
function weenectCreateZone(eqId, point, id){
    var avatar =  weenectZoneAvatar(point.name.value, 36, point.color);//'plugins/weenect/3rdparty/images/blank.png';//'plugins/weenect/3rdparty/images/blank.png';//weenectZoneAvatar(point.name.value, 36, point.color);//plugins/weenect/3rdparty/images/pin.png';
   if(!point.coord.value){
    	return;
    }
    var marker = L.marker(point.coord.value.split(','), {icon:  L.icon({
        iconUrl: avatar,
        iconSize: [36, 36],
        iconAnchor: [18, 36],
        popupAnchor: [-3, -76],
          className: 'weenect-zone-avatar-'+id
    }),
          zIndexOffset:  1000
     }).addTo(weenectObjects.maps[eqId].featureGroup);
    weenectObjects.maps[eqId].markers[id] = marker;
}
function weenectCreateHistory(eqId, point, id){
        var history = L.polyline([], {
            color: point.color,
            fillColor: point.color,
            opacity: 0.5,
            weight: 3,
            linejoin:"round",
            lineCap: 'round',
            dashArray: '3, 10', 
            dashOffset: '0'
        }).addTo(weenectObjects.maps[eqId].featureGroup);
        weenectObjects.maps[eqId].histories[id] = {hours: point.history, feature: history};
}

function weenectCreateCircle(eqId, point, id, fillAlpha=0.2){
    if(point.radius && !isNaN(point.radius.value)){
        var circle = L.circle(point.coord.value.split(','), {
            radius: point.radius.value,
            color: point.color,
            fillColor: point.color,
            fillOpacity: fillAlpha,
              weight: 1
        }).addTo(weenectObjects.maps[eqId].featureGroup);
        weenectObjects.maps[eqId].circles[id] = circle;
    }
}

function weenectCreatePoint(eqId, point){
    var id = point.id;
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
    weenectObjects.maps[eqId].map.fitBounds(weenectObjects.maps[eqId].featureGroup.getBounds(), {padding: [30, 30]});
    if(weenectObjects.maps[eqId].customZoom){
        weenectObjects.maps[eqId].map.setZoom(weenectObjects.maps[eqId].customZoom);
    }
}

function weenectMapLoaded(eqId){
    setTimeout(function(){
        weenectObjects.maps[eqId].map.invalidateSize();
    },1);
}
