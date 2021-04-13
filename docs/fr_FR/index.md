# Plugin Météo

Le plugin Weather permet de récupérer les données météorologiques pour une ou plusieurs villes. Il permet entre autre d’avoir les informations de lever et coucher du soleil, de température, de prévision, de vent, etc. Les informations récupérées viennent du site internet openweathermap.

# Configuration des équipements

Vous retrouvez ici toute la configuration de votre équipement :

-   **Nom de l’équipement météo** : nom de votre équipement météo
-   **Activer** : permet de rendre votre équipement actif
-   **Visible** : rend votre équipement visible sur le dashboard
-   **Objet parent** : indique l’objet parent auquel appartient l’équipement
-   **Latitude** : Latitude de l'endroit ou vous voulez la méteo (sous la forme XX.XXXXXXX)
-   **Longitude** : Longitude de l'endroit ou vous voulez la méteo (sous la forme XX.XXXXXXX)
-   **Affichage complet en mobile** : permet d’afficher toutes les informations météo ou non en mobile

Vous retrouvez en dessous toutes les commandes disponibles ainsi que la possibilité d’historiser ou non les valeurs numériques. Le code (numéro) en fonction des conditions est disponible [ici](https://openweathermap.org/weather-conditions)

Le rafraîchissement des données météo s’effectue toutes les 30 minutes.

>**IMPORTANT**
>OpenWeather fournit une liste d'informations sur les 120 heures à venir ; de ce fait, en fonction de l’heure actuelle, nous ne connaissons qu’une partie des informations à J+4. Ainsi, cette prédiction à J+4 s'affine pour devenir plus précise au fur et à mesure de la journée courante. Pour cette raison, certaines informations, comme la température MAX atteinte à J+4 ne pourront faire sens qu'en fin de journée.
