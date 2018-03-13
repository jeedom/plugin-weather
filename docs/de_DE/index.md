Configuration 
=============

Le plugin Weather permet de récupérer les données météorologiques pour
une ou plusieurs villes. Il permet entre autre d’avoir les informations
de lever et coucher du soleil, de température, de prévision, de vent,
etc. Les informations récupérées viennent du site internet
openweathermap.

Configuration du plugin 
-----------------------

Après avoir installé le plugin, il faut l’activer puis renseigner votre
clef api/

Pour obtenir votre clef api il faut aller
[ici](https://home.openweathermap.org), créer un compte et ensuite il
faut copier votre clef api dans la zone prévue sur la page de
configuration du Plugin.

> **Important**
>
> Il faut attendre quelques heures ensuite le temps que la clef soit
> active avant de pouvoir récupérer des informations

Configuration des équipements 
-----------------------------

Hier finden sie die ganze Konfiguration Ihrer Geräte:

-   **Nom de l’équipement météo** : nom de votre équipement météo

-   **Aktivieren**: auf Ihre aktiven Geräte machen

-   **Visible** : rend votre équipement visible sur le dashboard

-   **Übergeordnete Objekt** zeigt das übergeordnete Objekt gehört
    Ausrüstung

-   **Ville** : Il faut mettre le nom de votre ville suivi du code pays,
    ex : Paris,fr

-   **Affichage complet en mobile** : permet d’afficher toutes les
    informations météo ou non en mobile

Vous retrouvez en dessous toutes les commandes disponibles ainsi que la
possibilité d’historiser ou non les valeurs numériques. Le code (numéro)
en fonction des conditions est disponible
[ici](https://openweathermap.org/weather-conditions)

Le rafraîchissement des données météo s’effectue toutes les 30 minutes.

> **Tip**
>
> Nous vous conseillons de vous rendre
> [ici](https://openweathermap.org/find?) afin de vérifier si votre
> ville, village est connu ou pas. Auquel cas il faudra trouver la ville
> la plus proche connue et saisir cette dernière dans la configuration
> de votre équipement pour pouvoir récupérer les informations.

> **Tip**
>
> Une fois la recherche de votre ville réussie le site openweathermap
> vous montre les informations disponibles et vous devriez avoir dans
> votre navigateur une url du type
> <https://openweathermap.org/city/2988507>. Ce numéro à la fin de l’url
> peut également être saisi dans l’équipement Jeedom en lieu et place de
> Paris,fr par exemple

Changelog détaillé :
<https://github.com/jeedom/plugin-weather/commits/stable>

-   Correction d’un bug sur les fuseaux horaires

-   Correction d’un bug sur le non déclenchement au lever/coucher du
    soleil


