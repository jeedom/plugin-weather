Configuration
=============

El complemento Weather le permite recuperar datos meteorológicos para
una o mas ciudades. Entre otras cosas, proporciona información.
amanecer y atardecer, temperatura, pronóstico, viento,
etc. La información recuperada proviene del sitio web
openweathermap.

Configuración del plugin
-----------------------

Después de instalar el complemento, debe activarlo y luego ingresar su
Clave API /

Para obtener su clave API, debe ir
[aquí](https://home.openweathermap.org), crear una cuenta y luego allí
copie su clave API en el área provista en la página
Configuración del plugin.

> **Important**
>
> Tienes que esperar unas horas después hasta que la llave esté
> activo antes de que pueda recuperar información

Configuración del equipo
-----------------------------

Aquí encontrarás toda la configuración de tu equipo :

-   **Nombre del dispositivo meteorológico** : nombre de su equipo meteorológico

-   **Activer** : activa su equipo

-   **Visible** : hace que su equipo sea visible en el tablero

-   **Objeto padre** : indica el objeto padre al que pertenece
    equipo

-   **Ville** : Debes poner el nombre de tu ciudad seguido del código del país,
    ex : Paris, fr

-   **Visualización completa en móvil** : muestra todo
    información meteorológica o no en el móvil

A continuación encontrará todos los comandos disponibles, así como el
posibilidad de historizar o no los valores numéricos. El código (número)
dependiendo de las condiciones está disponible
[aquí](https://openweathermap.org/weather-conditions)

Los datos meteorológicos se actualizan cada 30 minutos..

> **Tip**
>
> Te aconsejamos que vayas
> [aquí](https://openweathermap.org/find?) para verificar si su
> ciudad, pueblo es conocido o no. En cuyo caso deberás encontrar la ciudad
> más cercano conocido e ingrese este último en la configuración
> de su equipo para poder recuperar información.

> **Tip**
>
> Una vez que la búsqueda de su ciudad es exitosa, el sitio openweathermap
> le muestra la información disponible y debe tener en
> su navegador una url del tipo
> <https://openweathermap.org/city/2988507>. Este número al final de la url
> también se puede ingresar en el equipo Jeedom en lugar de
> Paris, fr por ejemplo

>**IMPORTANT**
>OpenWeather proporciona una lista de información para las próximas 120 horas; por lo tanto, según la hora actual, solo conocemos parte de la información sobre D + 4. Por lo tanto, esta predicción en D + 4 se refina para ser más precisa a medida que avanza el día actual.. Por esta razón, cierta información, como la temperatura MÁX alcanzada en D + 4, solo puede tener sentido al final del día..
