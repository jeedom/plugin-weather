# Weather plugin

The plugin **Weather** allows you to retrieve meteorological data from one or more cities. It gives access, among other things, to weather forecasts, information on sunrise and sunset, temperature, humidity, wind, etc. The information comes from the website **openweathermap**.

# Equipment configuration

Here you find the configuration of your equipment :

-   **Weather device name** : name of your weather equipment
-   **Activate** : makes your equipment active
-   **Visible** : makes your equipment visible on the dashboard
-   **Parent object** : indicates the parent object to which the equipment belongs
-   **Latitude** : Latitude of the place where you want the weather (in the form XX.XXXXXXX)
-   **Longitude** : Longitude of the place where you want the weather (in the form XX.XXXXXXX)
-   **Full display on mobile** : displays all weather information or not on mobile
-   **Image mode** : to display images instead of icons on the widget


By clicking on the tab **Commands**, you will find all the available commands as well as the possibility of logging or not the numerical values. The code (number) according to the conditions can be consulted [at this address](https://openweathermap.org/weather-conditions)

The weather data is refreshed every 30 minutes.

>**Important**
>OpenWeather provides a list of information for the next 120 hours; therefore, based on the current time, we only know part of the information on D + 4. Thus, this prediction on D + 4 is refined to become more precise as the current day progresses. For this reason, certain information, such as the MAX temperature reached on D + 4 can only make sense at the end of the day.
