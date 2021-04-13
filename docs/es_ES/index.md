# Complemento meteorológico

El complemento **Clima** le permite recuperar datos meteorológicos de una o más ciudades. Da acceso, entre otras cosas, a previsiones meteorológicas, información de salida y puesta del sol, temperatura, humedad, viento, etc. La información procede de la web **openweathermap**.

# Configuración del plugin

Después de instalar el complemento, debe activarlo y luego ingresar su clave API.

Para obtener su clave API, debe ir [aquí](https://home.openweathermap.org), cree una cuenta y luego copie su clave API en el área provista en la página de configuración del complemento.

> **Importante**
>
> Tienes que esperar unas horas antes de poder recuperar información tras la creación de la cuenta, mientras la clave está activa.

# Configuración del equipo

Aquí encuentras la configuración de tu equipo :

-   **Nombre del dispositivo meteorológico** : nombre de su equipo meteorológico
-   **Activar** : activa su equipo
-   **Visible** : hace que su equipo sea visible en el tablero
-   **Objeto padre** : indica el objeto padre al que pertenece el equipo
-   **Ciudad** : Debes poner el nombre de tu ciudad seguido del código del país, *(ex : Paris, fr)*

-   **Visualización completa en móvil** : muestra toda la información meteorológica o no en el móvil
-   **Modo imagen** : para mostrar imágenes en lugar de iconos en el widget


Haciendo clic en la pestaña **Comandos**, encontrará todos los comandos disponibles así como la posibilidad de registrar o no los valores numéricos. Se puede consultar el código (número) según las condiciones [a esta dirección](https://openweathermap.org/weather-conditions)

Los datos meteorológicos se actualizan cada 30 minutos.

> **Punta**
>
> Te aconsejamos que vayas [aquí](https://openweathermap.org/find?) para verificar si su ciudad, pueblo es conocido o no. En ese caso, tendrá que encontrar la ciudad conocida más cercana e ingresarla en la configuración de su equipo para poder recuperar la información.

> **Punta**
>
> Une fois la recherche de votre ville réussie le site openweathermap vous montre les informations disponibles et vous devriez avoir dans votre navigateur une url du type <https://openweathermap.org/city/2988507>. Este número al final de la url también se puede ingresar en el equipo Jeedom en lugar de París, por ejemplo, fr

>**Importante**
>OpenWeather proporciona una lista de información para las próximas 120 horas; por lo tanto, según la hora actual, solo conocemos parte de la información sobre D + 4. Por lo tanto, esta predicción en D + 4 se refina para ser más precisa a medida que avanza el día actual. Por esta razón, cierta información, como la temperatura MÁX alcanzada en D + 4, solo puede tener sentido al final del día.
