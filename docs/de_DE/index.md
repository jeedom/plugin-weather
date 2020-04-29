Configuration
=============

Mit dem Wetter-Plugin können Sie Wetterdaten für wiederherstellen
eine oder mehrere Städte. Es liefert unter anderem Informationen
Sonnenaufgang und Sonnenuntergang, Temperatur, Vorhersage, Wind,
usw. Die abgerufenen Informationen stammen von der Website
openweathermap.

Plugin Konfiguration
-----------------------

Nach der Installation des Plugins müssen Sie es aktivieren und dann Ihr eingeben
API-Schlüssel /

Um Ihren API-Schlüssel zu erhalten, müssen Sie gehen
[hier](https://home.openweathermap.org), Erstellen Sie ein Konto und dann dort
Kopieren Sie Ihren API-Schlüssel in den auf der Seite angegebenen Bereich
Plugin Konfiguration.

> **Important**
>
> Sie müssen einige Stunden danach warten, bis der Schlüssel ist
> aktiv, bevor Sie Informationen abrufen können

Gerätekonfiguration
-----------------------------

Hier finden Sie die gesamte Konfiguration Ihrer Geräte :

-   **Name der Wetterausrüstung** : Name Ihrer Wetterausrüstung

-   **Activer** : macht Ihre Ausrüstung aktiv

-   **Visible** : macht Ihre Ausrüstung auf dem Armaturenbrett sichtbar

-   **Übergeordnetes Objekt** : gibt das übergeordnete Objekt an, zu dem es gehört
    Ausrüstung

-   **Ville** : Sie müssen den Namen Ihrer Stadt gefolgt von der Landesvorwahl eingeben,
    ex : Paris, fr

-   **Mobil Vollansicht** : zeigt alle an
    Wetterinformationen oder nicht auf dem Handy

Nachfolgend finden Sie alle verfügbaren Befehle sowie die
Möglichkeit der Historisierung oder nicht der numerischen Werte. Der Code (Nummer)
je nach bedingung ist vorhanden
[hier](https://openweathermap.org/weather-conditions)

Die Wetterdaten werden alle 30 Minuten aktualisiert.

> **Tip**
>
> Wir empfehlen Ihnen zu gehen
> [hier](https://openweathermap.org/find?) um zu überprüfen, ob Ihre
> Stadt, Dorf ist bekannt oder nicht. In diesem Fall müssen Sie die Stadt finden
> am nächsten bekannt und geben Sie letzteres in die Konfiguration ein
> Ihrer Ausrüstung, um Informationen abrufen zu können.

> **Tip**
>
> Sobald die Suche nach Ihrer Stadt erfolgreich ist, klicken Sie auf die openweathermap-Website
> zeigt Ihnen die verfügbaren Informationen und Sie sollten in haben
> Ihr Browser eine URL des Typs
> <https://openweathermap.org/city/2988507>. Diese Nummer am Ende der URL
> kann auch in Jeedom Ausrüstung anstelle von eingegeben werden
> Paris zum Beispiel fr

>**IMPORTANT**
>OpenWeather bietet eine Liste mit Informationen für die nächsten 120 Stunden. Daher kennen wir basierend auf der aktuellen Zeit nur einen Teil der Informationen zu D + 4. Daher wird diese Vorhersage für D + 4 verfeinert, um im Verlauf des aktuellen Tages genauer zu werden.. Aus diesem Grund können bestimmte Informationen, wie die auf D + 4 erreichte MAX-Temperatur, nur am Ende des Tages sinnvoll sein..
