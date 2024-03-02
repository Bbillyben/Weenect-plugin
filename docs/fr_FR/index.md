# Plugin Weenect
<p align="center">
  <img width="100" src="/plugin_info/weenect_icon.png">
</p>

Ce pluglin pour jeedom permet de récupérer les information des traceur weenect de votre compte weenect.
La tuile du plugin représente un carte avec la position du tracker et des zones de sécurité que vous avez défini pour chaque traçeur.

Seules les commande de demande de mise à jour de la position, faire sonner et faire vibrer sont disponible.

Le plugin récupère automatiquement les information de vos tracker et zones affiliées. Chaque tracker et zones sont des équipements individuels, ce qui vous permettra de les retrouver rapidement dans les différents selecteur de jeedom, selon la nomenclature classique.

# Configuration
  
  1. activer le plugin
  2. renseigner le nom d'utilisateur  ainsi que le mot de passe dans les champs prévu dans la configuration.
  vous pouvez cliquer sur le boutons "get token" qui ira chercher un token de connexion, et donc vérifiera celle-ci par la même.

  A la sauvegarde, le plugin récupèrera les information de votre compte weenect et créera automatiquement les équipements.

## autre paramètres de configuration

### général 
* fréquence de mise à jour : fréquence à laquelle le plugin va chercher les positions des trackers sur le serveur de weenect.
> warning: ! définir sur manuel, vous devrez appeler une commande "refresh" d'un des équipement pour mettre à jour les positions !
sur custom, vous pouvez définir une fréquence par une syntaxe cron. vous pouvez vous servir du générateur cron de jeedom disponible via le bouton '?' sur le coté du champs.

### Paramètres Zones
* Ajouter le nom du tracker à celui des zones : le nom des zones est récupérer sur le serveur weenect. Si activé, les zones seront alors nommées [nom du tracker]-[nom de la zone], pour vous permettre de les retrouver rapidement dans les selecteur jeedom. vous pouvez le désactiver, et aller dans chaque zone pour les renommer individuellement.

* Lier la configuration des zones au tracker  : si activé, les objets parents des zones seront synchronisé avec celui du tracker, ainsi que les catégories.

### Paramètre du Widget
pour gérer les paramètres liés aux tuiles/carte 

* Couleurs : permet de choisir les couleurs pour le tracker, et pour les zones
* Afficher l'épingle de zone : pour afficher ou non une 'épingle' sur les zones de sécurités, en plus du cercle de sécurité
* Fond cartographique thème light/dark : vous permet de choisir les fonds de cartes pour les thèmes light et dark
* Durée de l'historique : permet de selectionné la durée de l'historique affiché sur les tuiles. l'affichage de l'historique est géré dans les configurations de chaque équipement tracker.

# Equipement
les équipements sont créés automatiquement à la récupération des information sur le serveur weenect.
Les information sont récupérées à l'enregistremenet de la configuration du plugin, et tous les jours.

<p align="center">
  <img width="100" src="/docs/imgs/equipement.png">
</p>

## configuration 
* Afficher l'historique : permet d'afficher ou masquer l'historique sur la tuile

## informations : 
* Tracker id : l'identifiant du tracker 
* Date Creation : date ou le tracker a été activé dans weenect
* Date de Garantie : date de fin de garantie

## commandes disponibles : 
### Informations
* __Latitude__ : la dernière latitude du tracker
* __Longitude__ :la dernière longitude  du tracker
* __Precision__ : la précision en mètre du signal GPS du tracker
* __Coordonnées__ : les coordonnées sou forme : [latitude],[longitude]

* __Id Zone Courante__ : identifiant weenect de la zone en cours (0 si hors toutes zones)
* __Nom Zone Courante__ : nom de la zone occupé par le tracker (0 si hors toutes zones)


* __dernière date__ : la dernière date ou le tracker à mis à jour sa position

* __Battery__ : le pourcentage de batterie du tracker
* __GSM__ : la qualité du signal GSM
* __Signal__ : la qualité du signal GPS 
* __Online__ : si le tracker est en ligne

* __Date Expiration__ : date d'éxpiration de l'abonnement

* __type__ : type du tracker


* __Valid Signal__ : un paramètre signal valide renvoyé par le tracker? 
* __Deepsleep__ : si le tracker est en veille prolongée

### commande

* __Refresh__ : mise à jour des données par l'interrogation du serveur weenect
* __Demande mise à jour__ : envoi la commande de demande de mise à jour des position vers le tracker
* __Vibration__ : envoi la demande de vibration vers le tracker
* __Sonnerie__ : envoi la demande de sonnerie vers le tracker

## Zones 
onglets présentant les equipement zones attachés au tracker : 
<p align="center">
  <img width="100" src="/docs/imgs/zone_tab.png">
</p>
l'icone personne passe au vert quand la zone est occupée.
si vous cliquer sur l'une des zones vous arrivez sur l'équipement de la zone : 

la configuration de zone contient l'identifiant du tracker attaché. 

les commandes informations disponible pour chaque zones sont : 

* __Num__ : l'identifiant weenect de la zone
* __Adresse__ : l'adresse renseigné (si disponible)
* __Latitude__ : la longitude 
* __Longitude__ :la latitude 
* __Distance__ : le rayon du cercle de sécurité autour du point de coordonnées latitude,Longitude
* __Coordonnées__ : les coordonnées sou forme : [latitude],[longitude]
* __Dans la Zone__ : sépcifie si le tracker est dans la zone (1) ou hors zone (0)


# Tuile 

La tuile du plugin pour un équipement affiche une carte de la zone selon les fonds de cartes et couleurs spécifiés dans la configuration de l'équipement.

<p align="center">
  <img width="100" src="/docs/imgs/tile.png">
</p>
*avec l'historique d'activé*

en haut à gauche de la tuile dashboard, vous avez les trois commande : demande de mise à jour, sonnerie et vibration du tracker
autour de la carte sont affichés : 
* la date de mise à jour de la position
* le nom de la zone occupée
* le pourcentage de batterie
* la précision
* les coordonnées

l'historique est affiché selon la configuration de l'équipement (Oui/non) et la configuration générale du plugin (durée, couleur)
Le bouton au centre en bas permet de basculer, quand l'historique est affiché, entre la vue 'tracé' et la vue 'heat mat' de l'historique :
<p align="center">
  <img width="100" src="/docs/imgs/tile_heat.png">
</p>



