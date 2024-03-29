# Plugin Weenect
<p align="center">
  <img width="100" src="/plugin_info/weenect_icon.png">
</p>

Ce pluglin pour jeedom permet de récupérer les information des traceur weenect de votre compte weenect.
La tuile du plugin représente un carte avec la position du tracker et des zones de sécurité que vous avez défini pour chaque traçeur.

Seules les commande de demande de mise à jour de la position, faire sonner et faire vibrer sont disponible.

Le plugin récupère automatiquement les informations de vos trackers et zones affiliées. Chaque tracker et zones sont des équipements individuels, ce qui vous permettra de les retrouver rapidement dans les différents selecteurs de jeedom, selon la nomenclature classique.

# Configuration
  
  1. activer le plugin
  2. renseignez le nom d'utilisateur  ainsi que le mot de passe dans les champs prévus dans la configuration.
  vous pouvez cliquer sur le boutons "get token" qui ira chercher un token de connexion, et donc vérifiera celle-ci par la même.

  A la sauvegarde, le plugin récupèrera les informations de votre compte weenect et créera automatiquement les équipements.

## autre paramètres de configuration

### général 
* __fréquence de mise à jour__ : fréquence à laquelle le plugin va chercher les positions des trackers sur le serveur de weenect.
> ::warning:: ! définir sur manuel, vous devrez appeler une commande "refresh" d'un des équipements pour mettre à jour les positions !

>sur custom, vous pouvez définir une fréquence par une syntaxe cron. vous pouvez vous servir du générateur cron de jeedom disponible via le bouton >'?' sur le coté du champs.

### Paramètres Zones
* __Ajouter le nom du tracker à celui des zones__ : les noms des zones sont récupérés sur le serveur weenect. Si activé, les zones seront alors nommées [nom du tracker]-[nom de la zone], pour vous permettre de les retrouver rapidement dans les selecteurs jeedom. vous pouvez le désactiver, et aller dans chaque zone pour les renommer individuellement.

* Lier la configuration des zones au tracker  : si activé, les équipements zones seront synchronisés avec celui du tracker, ainsi que les catégories.

### Paramètre du Widget
pour gérer les paramètres liés aux tuiles/carte 

* Couleurs : permet de choisir les couleurs pour le tracker, et pour les zones
* Couleur de Zone dynamique : permet de coloré en vert lorsqu'une zone est occupée.
* Afficher l'épingle de zone : pour afficher ou non une 'épingle' sur les zones de sécurités, en plus du cercle de sécurité
* Fond cartographique thème light/dark : vous permet de choisir les fonds de cartes pour les thèmes light et dark
* Durée de l'historique : permet de selectionner la durée de l'historique affiché sur les tuiles. l'affichage de l'historique est géré dans les configurations de chaque équipement tracker.

# Equipement
les équipements sont créés automatiquement à la récupération des informations sur le serveur weenect.
Les informations sont récupérées à l'enregistremenet de la configuration du plugin, et tous les jours.

<p align="center">
  <img src="/docs/imgs/equipement.png">
</p>

## configuration 
* Afficher l'historique : permet d'afficher ou masquer l'historique sur la tuile

## informations : 
* Tracker id : l'identifiant du tracker 
* Date Creation : date ou le tracker a été activé dans weenect
* Date de Garantie : date de fin de garantie
* IMEI : identifiant imei du tracker
* Type : type du tracker 

## commandes disponibles : 
### Informations
* __Latitude__ : la dernière latitude du tracker
* __Longitude__ :la dernière longitude  du tracker
* __Precision__ : la précision en mètres du signal GPS du tracker
* __Coordonnées__ : les coordonnées sous forme : [latitude],[longitude]

* __Id Zone Courante__ : identifiant weenect de la zone en cours (0 si hors toutes zones)
* __Nom Zone Courante__ : nom de la zone occupé par le tracker (0 si hors toutes zones)


* __dernière date__ : la dernière date ou le tracker à mis à jour sa position

* __Battery__ : le pourcentage de batterie du tracker
* __GSM__ : la qualité du signal GSM
* __satellites__ : le nombre de satellites captés par le tracker
* __Signal__ : la qualité du signal GPS 
* __Online__ : si le tracker est en ligne

* __Date Expiration__ : date d'éxpiration de l'abonnement

* __type__ : type de notification envoyées : 
   * ALM-OFF : quand le tracker a été mis hors tension
   * ALM-OFF-BAT : hors tension car batterie HS
   * ALM-H : le message sur le 1er bouton a été envoyé
   * ALM-G : le message sur le 2nd bouton a été envoyé
   * CMD-V3 : le message sur le bouton SOS a été envoyé
   * CMD-T : mode normal (possition transmise?)
   * CMD-INV1S : le traceur s'est allumé (?)

* __Raison Hors Tension__: Raison de la mise hors tension 
   * ALM-OFF : mise hors tension par le bouton
   * ALM-OFF-BAT : hors tension car batterie HS
   * autre à trouver remonter sur le [community de jeedom](https://community.jeedom.com/).

* __temps d'appel restant__ : le nombre de minutes d'appel restant
* __Valid Signal__ : un paramètre signal valide renvoyé par le tracker? 
* __Deepsleep__ : si le tracker est en veille prolongée
* __Frequence Mise à Jour__ : la fréquence de mise à jour du tracker (texte : 30S, 1M, 2M, 3M, 5M, 10M)

### commandes

* __Refresh__ : mise à jour des données par l'interrogation du serveur weenect
* __Demande mise à jour__ : envoi la commande de demande de mise à jour des positions vers le tracker
* __Vibration__ : envoi la demande de vibration vers le tracker
* __Sonnerie__ : envoi la demande de sonnerie vers le tracker
* __Set Frequence Mise à Jour__ : Une commande de type liste pour mettre à jour la fréquence de rafraichissement du tracker (30 sec, 1 min, 2 min, 3 min, 5 min, 10 min) 

## Zones 
onglets présentant les equipements zones attachés au tracker : 
<p align="center">
  <img src="/docs/imgs/zone_tab.png">
</p>
l'icone "personne" passe au vert quand la zone est occupée.
si vous cliquez sur l'une des zones vous arriverez sur l'équipement de la zone : 

la configuration de zone contient l'identifiant du tracker attaché. 

les commandes informations disponibles pour chaque zone sont : 

* __Num__ : l'identifiant weenect de la zone
* __Adresse__ : l'adresse renseignée (si disponible)
* __Latitude__ : la longitude 
* __Longitude__ :la latitude 
* __Distance__ : le rayon du cercle de sécurité autour du point de coordonnées latitude,Longitude
* __Coordonnées__ : les coordonnées sous forme : [latitude],[longitude]
* __Dans la Zone__ : sépcifie si le tracker est dans la zone (1) ou hors zone (0)


# Tuile 

La tuile du plugin pour un équipement affiche une carte de la zone selon les fonds de cartes et couleurs spécifiés dans la configuration de l'équipement.

<p align="center">
  <img src="/docs/imgs/tile.png">
</p>

en haut à gauche de la tuile dashboard, vous avez les trois commande : demande de mise à jour, sonnerie et vibration du tracker
autour de la carte sont affichés : 
* la date de mise à jour de la position
* le nom de la zone occupée
* le nombre de satellites
* le pourcentage de batterie
* la précision
* les coordonnées

l'historique est affiché selon la configuration de l'équipement (Oui/non) et la configuration générale du plugin (durée, couleur)
Le bouton au centre en bas permet de basculer, quand l'historique est affiché, entre la vue 'tracé' et la vue 'heat mat' de celui-ci :
<p align="center">
  <img src="/docs/imgs/tile_heat.png">
</p>



