# Wetter Plugin

Das Plugin **Wetter** Ermöglicht das Abrufen von Wetterdaten aus einer oder mehreren Städten. Es bietet unter anderem Zugriff auf Wettervorhersagen, Informationen zu Sonnenaufgang und Sonnenuntergang, Temperatur, Luftfeuchtigkeit, Wind usw. Die Informationen stammen von der Website **openweathermap**.

# Gerätekonfiguration

Hier finden Sie die Konfiguration Ihrer Geräte :

-   **Name der Wetterausrüstung** : Name Ihrer Wetterausrüstung
-   **Aktivieren** : macht Ihre Ausrüstung aktiv
-   **Sichtbar** : macht Ihre Ausrüstung auf dem Armaturenbrett sichtbar
-   **Übergeordnetes Objekt** : Gibt das übergeordnete Objekt an, zu dem das Gerät gehört
-   **Breite** : Breite des Ortes, an dem Sie das Wetter wollen (in der Form XX.XXXXXXX)
-   **Längengrad** : Länge des Ortes, an dem Sie das Wetter wollen (in der Form XX.XXXXXXX)
-   **Mobil Vollansicht** : Zeigt alle Wetterinformationen an oder nicht auf dem Handy
-   **Bild Modus** : um Bilder anstelle von Symbolen im Widget anzuzeigen


Durch Klicken auf die Registerkarte **Befehle**, Sie finden alle verfügbaren Befehle sowie die Möglichkeit, die numerischen Werte zu protokollieren oder nicht. Der Code (Nummer) gemäß den Bedingungen kann eingesehen werden [an dieser Adresse](https://openweathermap.org/weather-conditions)

Die Wetterdaten werden alle 30 Minuten aktualisiert.

>**Wichtig**
>OpenWeather bietet eine Liste mit Informationen für die nächsten 120 Stunden. Daher kennen wir basierend auf der aktuellen Zeit nur einen Teil der Informationen zu D + 4. Daher wird diese Vorhersage für D + 4 verfeinert, um im Verlauf des aktuellen Tages genauer zu werden. Aus diesem Grund können bestimmte Informationen, wie die auf D + 4 erreichte MAX-Temperatur, nur am Ende des Tages sinnvoll sein.
