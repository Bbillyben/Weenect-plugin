# Plugin Weenect
<p align="center">
  <img width="100" src="/plugin_info/weenect_icon.png">
</p>

This plugin for jeedom allows you to retrieve weenect tracker information from your weenect account.

The plugin tile represents a map with the position of the tracker and the security zones that you have defined for each tracker.

Only the position update request, ring and vibrate commands are available.
The plugin automatically retrieves information from your trackers and affiliated zones. Each tracker and zone is an individual piece of equipment, so you can find them quickly in the various jeedom selectors, using the classic nomenclature.

# Configuration
  
  1. Activate plugin
  2. enter the user name and password in the fields provided in the configuration.
  you can click on the "get token" button, which will fetch a connection token and verify it at the same time.

  When you save, the plugin will retrieve the information from your weenect account and automatically create the equipment.

## other configuration parameters

### general 
* Update frequency__: frequency at which the plugin fetches tracker positions from the weenect server.
> ::warning:: ! set to manual, you will have to call a "refresh" command from one of the devices to update the positions!

>You can use the jeedom cron generator available via the >'?' button on the side of the field.

### Area Settings
* __Add the tracker name to the Area name__ : the zone name is retrieved from the weenect server. If enabled, zones will be named [tracker name]-[zone name], so you can find them quickly in the jeedom selector. You can disable this, and go to each zone to rename them individually.

* Link the zone configuration to the tracker: if enabled, the parent objects of the zones will be synchronised with those of the tracker, as will the categories.

### Widget parameters
to manage tile/map settings 

* Colors: allows you to choose the colours for the tracker and for the zones.
* Show zone pin: whether or not to show a 'pin' on safety zones, in addition to the safety circle
* Map background light/dark theme: lets you choose the map backgrounds for the light and dark themes
* History duration: allows you to select the duration of the history displayed on the tiles. The display of the history is managed in the configuration of each tracker device.


# Equipment
equipment is created automatically when information is retrieved from the weenect server.
The information is retrieved when the plugin configuration is saved, and every day.

<p align="center">
  <img src="/docs/imgs/equipement.png">
</p>

## configuration 
* Show history: shows or hides the history on the tile

## information : 
* Tracker id: the tracker identifier 
* Creation date: date the tracker was activated in weenect
* Warranty date: date the warranty expires
* IMEI : imei identifiant of the tracker
* Type : type of the tracker

## Command available : 
### Information
* __Latitude__ : the last latitude of the tracker
* __Longitude__ : the last longitude of the tracker
* __Accuracy__ : the precision in metres of the tracker's GPS signal
* __Coordinates__ : the coordinates in the form : [latitude],[longitude] 
* __Id Current Area__ : the current area of the tracker.

* __Current Zone ID__ : weenect identifier of the current zone (0 if out of all zones)
* __Current Zone Name__ : name of the zone occupied by the tracker (0 if outside all zones)


* __Last date__ : the last date on which the tracker updated its position

* __Battery__: the tracker's battery percentage
* __GSM__ : GSM signal quality
* __satellites__ : satellites number seen by the tracker
* __Signal__: GPS signal quality 
* __Online__: whether the tracker is online

* __Date Expiration__ : subscription expiry date

* __type__ : type of notification sent : 
   * ALM-OFF: when the tracker has been switched off
   * ALM-H: the message on the 1st button has been sent
   * ALM-G: the message on the 2nd button has been sent
   * CMD-V3: the message on the SOS button has been sent
   * CMD-T: normal mode (position transmitted?)

* __Reason Off__: Reason for power-down 
   * ALM-OFF: Power off by the button
   * other to be found on the [jeedom community](https://community.jeedom.com/).
* __left call__ : time left in minutes for call
* __Valid Signal__ : a valid signal parameter returned by the tracker? 
* __Deepsleep__ : if the tracker is in extended sleep mode

### commands

* __Refresh__ : update data by querying the weenect server
* __Request update__ : sends the position update request command to the tracker
* __Vibration__ : sends the vibration request to the tracker
* __Ringing__ : sends the ringing request to the tracker

## Areas 
tabs showing the areas equipment attached to the tracker :

<p align="center">
  <img src="/docs/imgs/zone_tab.png">
</p>

the person icon turns green when the area is occupied.
if you click on one of the zones, you go to the area equipment: 

the area configuration contains the identifier of the attached tracker. 

the information commands available for each are are :

* __Num__ : the weenect identifier for the zone
* __Address__: the address entered (if available)
* __Latitude__ : the longitude 
* __Longitude__ : the latitude 
* __Distance__ : the radius of the safety circle around the latitude,longitude coordinate point
* __Coordinates__ : the coordinates in the form : [latitude],[longitude].
* __In Area__ : specifies whether the tracker is in the zone (1) or out of the zone (0).


# Widget 

The plugin tile for a piece of equipment displays a map of the area using the backgrounds and colours specified in the equipment configuration.

<p align="center">
  <img src="/docs/imgs/tile.png">
</p>

at the top left of the dashboard tile, you have the three commands: update request, ringtone and tracker vibration
around the map are displayed : 
* the date the position was updated
* the name of the zone occupied
* the number of satellites
* the battery percentage
* accuracy
* coordinates

the history is displayed according to the configuration of the equipment (Yes/No) and the general configuration of the plugin (duration, colour)
When the history is displayed, the button at the bottom centre can be used to toggle between the 'plot' view and the 'heat mat' view of the history:
<p align="center">
  <img src="/docs/imgs/tile_heat.png">
</p>



