var weenectObjects;
if(!weenectObjects){
    weenectObjects = {
        maps: {},
        intervals: {}
    };
}

// var weenect_MONTH_NAMES = [
//     'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
//     'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
// ];
// function weenectGetFormattedDate(date, prefomattedDate = false, hideYear = false) {
//     const day = date.getDate();
//     const month = weenect_MONTH_NAMES[date.getMonth()];
//     const year = date.getFullYear();
//     const hours = date.getHours();
//     let minutes = date.getMinutes();

//     if (minutes < 10) {
//         // Adding leading zero to minutes
//         minutes = `0${ minutes }`;
//     }

//     if (prefomattedDate) {
//         // Today at 10:20
//         // Yesterday at 10:20
//         return `${ prefomattedDate } à ${ hours }:${ minutes }`;
//     }

//     if (hideYear) {
//         // 10. January at 10:20
//         return `${ day }. ${ month } à ${ hours }:${ minutes }`;
//     }

//     // 10. January 2017. at 10:20
//     return `${ day }. ${ month } ${ year }. à ${ hours }:${ minutes }`;
// }
// function weenectTimeAgo(dateParam, id, eqId) {
//     if (!dateParam) {
//         return null;
//     }

//     const date = new Date(dateParam);
//     const DAY_IN_MS = 86400000; // 24 * 60 * 60 * 1000
//     const today = new Date();
//     const yesterday = new Date(today - DAY_IN_MS);
//     const seconds = Math.round((today - date) / 1000);
//     const minutes = Math.round(seconds / 60);
//     const isToday = today.toDateString() === date.toDateString();
//     const isYesterday = yesterday.toDateString() === date.toDateString();
//     const isThisYear = today.getFullYear() === date.getFullYear();
//     var result;
//     if (seconds < 60) {
//         result = 'maintenant';
//     }  else if (seconds < 90) {
//         result = 'il y a une minute';
//     } else if (minutes < 60) {
//         result = `il y a ${ minutes } minutes`;
//     } else if (isToday) {
//         result = weenectGetFormattedDate(date, 'Aujourd\'hui'); // Today at 10:20
//     } else if (isYesterday) {
//         result = weenectGetFormattedDate(date, 'Hier'); // Yesterday at 10:20
//     } else if (isThisYear) {
//         result = weenectGetFormattedDate(date, false, true); // 10. January at 10:20
//     }else{
//         result = weenectGetFormattedDate(date); // 10. January 2017. at 10:20
//     }

//     if(minutes<10){
//         $('.weenect-avatar-'+eqId).css('filter', 'grayscale(0)');
//     }else if(minutes>=10 && minutes<20){
//         $('.weenect-avatar-'+eqId).css('filter', 'grayscale(0.5)');
//     }else{
//         $('.weenect-avatar-'+eqId).css('filter', 'grayscale(1)');
//     }
//     cmd = $('.cmd.weenect-horodatage[data-cmd_id='+id+']');
//     cmd.empty().append(result);
//     if(weenectObjects.intervals[id]){
//         clearTimeout(weenectObjects.intervals[id]);
//     }
//     weenectObjects.intervals[id] = setTimeout(function(){weenectTimeAgo(dateParam, id)}, 60000);
// }

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
    var gradientHead = context.createRadialGradient(size/2-radius/2, 2*radius/3, radius/8, size/2, radius, radius);
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


function weenectUpdateAccuracy(id, _options){
    var cmd = $('.cmd[data-cmd_id='+id+']');
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
    if(point.type  && point.type == "weenect"){
        weenectCreateTracker(eqId, point, id);
    }else{
        weenectCreateZone(eqId, point, id);  
        //weenectCreateCircle(eqId, {radius:{value:5},coord:point.coord, color:point.color}, id, 1);      
    }

  	if(!point.coord.value){
    	return;
    }
    
    weenectCreateCircle(eqId, point, id);
    weenectCreateCircle(eqId, {radius:{value:3},coord:point.coord, color:point.color}, id, 1);   
  	// if(point.history){
    // 	weenectCreateHistory(eqId, point, id);
    // }
}
function weenectCreateTracker(eqId, point, id){
    var avatar = weenectLetterAvatar(point.name.value, 60, point.color);
    // var shadowUrl= 'plugins/weenect/3rdparty/images/avatar-pin-2x.png';
    if(!point.coord.value){
    	return;
    }
    var marker = L.marker(point.coord.value.split(','), {icon:  L.icon({
        iconUrl: avatar,
        iconSize: [40, 40],
        iconAnchor: [20, 40],
        // shadowUrl: shadowUrl,
        // shadowSize: [50, 55],
        // shadowAnchor: [25, 55],
        popupAnchor: [-3, -76],
          className: 'weenect-avatar-'+id
    }),
          zIndexOffset:  1000
     }).addTo(weenectObjects.maps[eqId].featureGroup);
    //marker._icon.style['background-color'] =  point.color;
    weenectObjects.maps[eqId].markers[id] = marker;
}
function weenectCreateZone(eqId, point, id){
    var avatar =  weenectZoneAvatar(point.name.value, 36, point.color);//'plugins/weenect/3rdparty/images/blank.png';//'plugins/weenect/3rdparty/images/blank.png';//weenectZoneAvatar(point.name.value, 36, point.color);//plugins/weenect/3rdparty/images/pin.png';
    // var shadowUrl = weenectZoneAvatar(point.name.value, 36, point.color);//'plugins/weenect/3rdparty/images/pin.png';//'plugins/weenect/3rdparty/images/avatar-pin-2x.png';
    if(!point.coord.value){
    	return;
    }
    var marker = L.marker(point.coord.value.split(','), {icon:  L.icon({
        iconUrl: avatar,
        iconSize: [36, 36],
        iconAnchor: [18, 36],
        // shadowUrl: shadowUrl,
        // shadowSize: [36, 36],
        // shadowAnchor: [18, 36],
        popupAnchor: [-3, -76],
          className: 'weenect-zone-avatar-'+id
    }),
          zIndexOffset:  1000
     }).addTo(weenectObjects.maps[eqId].featureGroup);
    //marker._icon.style['background-color'] =  point.color;
    weenectObjects.maps[eqId].markers[id] = marker;
}
// function weenectCreateHistory(eqId, point, id){
//         var history = L.polyline([], {
//             color: point.color,
//             fillColor: point.color,
//             fillOpacity: 0.1,
//             weight: 1.5
//         }).addTo(weenectObjects.maps[eqId].featureGroup);
//         weenectObjects.maps[eqId].histories[id] = {hours: point.history, feature: history};
// }

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
                    weenectObjects.maps[this.context.map].histories[result.eqLogic.id].feature.setLatLngs(values);
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

function weenectCreatePoint(eqId, point){
    // console.log('  ------------------ weenectCreatePoint  ----------------- ');
    // console.log(JSON.stringify(point));
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

    // if(point.radius){
    //     jeedom.cmd.update[point.radius.id] = function(_options) {
    //         weenectUpdateAccuracy(point.radius.id, _options);
    //         weenectUpdateCircleRadius(id, _options.display_value);
    //     }
    //     jeedom.cmd.update[point.radius.id]({display_value:point.radius.value});
    // }

    if(point.coord && point.type=="weenect"){
        jeedom.cmd.update[point.coord.id] = function(_options) {
            weenectUpdateAddress(point.coord.id, _options, id);
        }
        jeedom.cmd.update[point.coord.id]({display_value:point.coord.value, collectDate:point.coord.collectDate});
    }

    if(point.coord){
        jeedom.cmd.update[point.coord.id] = function(_options) {
            weenectUpdateMarker(id, _options.display_value, point.coord.id);
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
