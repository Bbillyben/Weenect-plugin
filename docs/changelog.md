# Changelog plugin Weenect

# 2024-04-16
* add default focus : let user choose between default focus on tracker, with default zoom, or tracker + security zones 
* update default zoom slider format

# 2024-04-13
* Add default zoom configuration in tracker equipment

# 2024-03-15
* update leaflet.js to 1.8.0
* add tooltip zone name display configuration 
* add dynamic coloration of occupied zone
* add "Superlive" to launch superlive for tracker (fast refresh for 5 minutes)
* add command info "update frequency" related to the frequecy of position updat eby the tracker
* add command action "set update frequency" to set the frequecy of position update eby the tracker (list)


# 2024-03-06
* Add 1 month history depth in configuration
* change API call for single tracker update -> give more info on tracker (eg. satelites)
* add satellites icon in template
* update mobile tile

__command__ :
* add "off reason" command
* add "left_call" : time in seconde left in call

__in config info__ :
* add IMEI
* add trakcer type

# 2024-03-04
* Ajout automatisme : si "afficher l'historique" est coché dans la configuration du widget, alors la commande 'Coordonées' sera configuré pour être historisée, avec comme rétension de 1 mois si non configuré auparavent
* ajout de la traduction en
* nettoyage du nom de la zone dans la commande 'nom zone en cours' pour enlever le nom du tracker

# 2024-02-24 : 
* première version fully fonctionnelle
