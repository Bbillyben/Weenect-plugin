var weenectObjects;
if(!weenectObjects){
    weenectObjects = {
        maps: {},
        intervals: {}
    };
}

var weenect_MONTH_NAMES = [
    'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
    'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
];
function weenectGetFormattedDate(date, prefomattedDate = false, hideYear = false) {
    const day = date.getDate();
    const month = weenect_MONTH_NAMES[date.getMonth()];
    const year = date.getFullYear();
    const hours = date.getHours();
    let minutes = date.getMinutes();

    if (minutes < 10) {
        // Adding leading zero to minutes
        minutes = `0${ minutes }`;
    }

    if (prefomattedDate) {
        // Today at 10:20
        // Yesterday at 10:20
        return `${ prefomattedDate } à ${ hours }:${ minutes }`;
    }

    if (hideYear) {
        // 10. January at 10:20
        return `${ day }. ${ month } à ${ hours }:${ minutes }`;
    }

    // 10. January 2017. at 10:20
    return `${ day }. ${ month } ${ year }. à ${ hours }:${ minutes }`;
}
function weenectTimeAgo(dateParam, id, eqId) {
    if (!dateParam) {
        return null;
    }

    const date = new Date(dateParam);
    const DAY_IN_MS = 86400000; // 24 * 60 * 60 * 1000
    const today = new Date();
    const yesterday = new Date(today - DAY_IN_MS);
    const seconds = Math.round((today - date) / 1000);
    const minutes = Math.round(seconds / 60);
    const isToday = today.toDateString() === date.toDateString();
    const isYesterday = yesterday.toDateString() === date.toDateString();
    const isThisYear = today.getFullYear() === date.getFullYear();
    var result;
    if (seconds < 60) {
        result = 'maintenant';
    }  else if (seconds < 90) {
        result = 'il y a une minute';
    } else if (minutes < 60) {
        result = `il y a ${ minutes } minutes`;
    } else if (isToday) {
        result = weenectGetFormattedDate(date, 'Aujourd\'hui'); // Today at 10:20
    } else if (isYesterday) {
        result = weenectGetFormattedDate(date, 'Hier'); // Yesterday at 10:20
    } else if (isThisYear) {
        result = weenectGetFormattedDate(date, false, true); // 10. January at 10:20
    }else{
        result = weenectGetFormattedDate(date); // 10. January 2017. at 10:20
    }

    if(minutes<10){
        $('.weenect-avatar-'+eqId).css('filter', 'grayscale(0)');
    }else if(minutes>=10 && minutes<20){
        $('.weenect-avatar-'+eqId).css('filter', 'grayscale(0.5)');
    }else{
        $('.weenect-avatar-'+eqId).css('filter', 'grayscale(1)');
    }
    cmd = $('.cmd.weenect-horodatage[data-cmd_id='+id+']');
    cmd.empty().append(result);
    if(weenectObjects.intervals[id]){
        clearTimeout(weenectObjects.intervals[id]);
    }
    weenectObjects.intervals[id] = setTimeout(function(){weenectTimeAgo(dateParam, id)}, 60000);
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
     
    context.fillStyle = color;
    context.fillRect (0, 0, canvas.width, canvas.height);
    context.font = Math.round(canvas.width/2)+"px Arial";
    context.textAlign = "center";
    context.fillStyle = "#FFF";
    context.fillText(initials, size / 2, size / 1.5);

    dataURI = canvas.toDataURL();
    canvas  = null;

    return dataURI;
}

function weenectUpdateBattery(id, _options){
    var cmd = $('.cmd.weenect-battery[data-cmd_id='+id+']');
    cmd.empty().append(_options.display_value);
    var icon = 'fa-battery-empty';
    if(_options.display_value > 80){
        icon = 'fa-battery-full';
    }else if(_options.display_value > 60){
        icon = 'fa-battery-three-quarters';
    }else if(_options.display_value > 40){
        icon = 'fa-battery-half';
    }else if(_options.display_value > 20){
        icon = 'fa-battery-quarter';
    }
    cmd = $('.cmd.weenect-battery-icon[data-cmd_id='+id+']');
    cmd.find('i').attr('class', 'fa ' + icon);
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

    weenectTimeAgo(_options.collectDate, id, eqId);
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
    var avatar = (point.image && point.image.value ? ('/plugins/weenect/core/ajax/weenect.proxy.php?url='+point.image.value) : weenectLetterAvatar(point.name.value, 36, point.color));
    $('.weenect-address img.weenect-avatar-'+id).attr('src', avatar);
  	if(!point.coord){
    	return;
    }
    
    var marker = L.marker(point.coord.split(','), {icon:  L.icon({
            iconUrl: avatar,
            shadowUrl: 'plugins/weenect/3rdparty/images/avatar-pin-2x.png',
            iconSize: [36, 36],
            shadowSize: [50, 55],
            iconAnchor: [18, 47],
            shadowAnchor: [25, 55],
            popupAnchor: [-3, -76],
      		className: 'weenect-avatar-'+id
        }),
      		zIndexOffset:  1000
         }).addTo(weenectObjects.maps[eqId].featureGroup);
    marker._icon.style['background-color'] =  point.color;
    weenectObjects.maps[eqId].markers[id] = marker;
    weenectCreateCircle(eqId, point, id);
  	// if(point.history){
    // 	weenectCreateHistory(eqId, point, id);
    // }
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

function weenectCreateCircle(eqId, point, id){
    if(point.radius && !isNaN(point.radius)){
        var circle = L.circle(point.coord.split(','), {
            radius: point.radius,
            color: point.color,
            fillColor: point.color,
            fillOpacity: 0.1,
              weight: 1
        }).addTo(weenectObjects.maps[eqId].featureGroup);
        weenectObjects.maps[eqId].circles[id] = circle;
    }
}

// function weenectUpdateMarker(eqId, coords, cmdId){
//     for (const key in weenectObjects.maps){
//         var map = weenectObjects.maps[key];
//         if(map.markers[eqId]){
//             map.markers[eqId].setLatLng(coords.split(','));
//             if(map.circles[eqId]){
//                 map.circles[eqId].setLatLng(coords.split(','));
//             }
//           if(map.histories[eqId] && map.histories[eqId].feature && map.histories[eqId].hours){
//           	  var date = new Date();
//               var dateEnd = formatDate(date);
//               var dateStart = formatDate(new Date(date.setHours(date.getHours()-map.histories[eqId].hours)));
//               jeedom.history.get({
//                   global: false,
//                   cmd_id: cmdId,
//                   dateStart: dateStart,
//                   dateEnd: dateEnd,
//                   context: {map: key, eqId: eqId},
//                   success: function(result) {
//                     if (result.data.length == 0) return false
//                     var values = result.data.map(function(elt) {
//                       return elt[1].split(',').map(function(coord) { return parseFloat(coord)}) });
//                     weenectObjects.maps[this.context.map].histories[result.eqLogic.id].feature.setLatLngs(values);
//                   }
//               });
//             }
//         }
//         weenectFocusFeatureGroup(key);
//     }
// }

function weenectUpdateCircleRadius(id, radius){
    for (const key in weenectObjects.maps){
        var map = weenectObjects.maps[key];
        if(map.circles[id]){
            map.circles[id].setRadius(radius);
        }
        weenectFocusFeatureGroup(key);
    }
}

function weenectCreatePoint(eqId, point, id){
    if(point.battery){
        jeedom.cmd.update[point.battery.id] = function(_options) {
            weenectUpdateBattery(point.battery.id, _options);
        }
        jeedom.cmd.update[point.battery.id]({display_value:point.battery.value});
    }

    if(point.accuracy){
        jeedom.cmd.update[point.accuracy.id] = function(_options) {
            weenectUpdateAccuracy(point.accuracy.id, _options);
            weenectUpdateCircleRadius(id, _options.display_value);
        }
        jeedom.cmd.update[point.accuracy.id]({display_value:point.accuracy.value});
    }

    if(point.address){
        jeedom.cmd.update[point.address.id] = function(_options) {
            weenectUpdateAddress(point.address.id, _options, id);
        }
        jeedom.cmd.update[point.address.id]({display_value:point.address.value, collectDate:point.address.collectDate});
    }

    if(point.coordinated){
        jeedom.cmd.update[point.coordinated.id] = function(_options) {
            weenectUpdateMarker(id, _options.display_value, point.coordinated.id);
        }
        jeedom.cmd.update[point.coordinated.id]({display_value:point.coordinated.value});
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
