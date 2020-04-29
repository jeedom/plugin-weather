Configuration
=============

The Weather plugin allows you to recover weather data for
one or more cities. Among other things, it provides information
sunrise and sunset, temperature, forecast, wind,
etc. The information retrieved comes from the website
openweathermap.

Plugin configuration
-----------------------

After installing the plugin, you must activate it and then enter your
API key /

To get your API key you have to go
[here](https://home.openweathermap.org), create an account and then there
copy your API key in the area provided on the page
Plugin configuration.

> **Important**
>
> You have to wait a few hours afterwards until the key is
> active before you can retrieve information

Equipment configuration
-----------------------------

Here you find all the configuration of your equipment :

-   **Weather device name** : name of your weather equipment

-   **Activer** : makes your equipment active

-   **Visible** : makes your equipment visible on the dashboard

-   **Parent object** : indicates the parent object to which belongs
    equipment

-   **Ville** : You must put the name of your city followed by the country code,
    Ex : Paris, fr

-   **Full display on mobile** : displays all
    weather information or not on mobile

You will find below all the available commands as well as the
possibility of historizing or not the numerical values. The code (number)
depending on conditions is available
[here](https://openweathermap.org/weather-conditions)

The weather data is refreshed every 30 minutes.

> **Tip**
>
> We advise you to go
> [here](https://openweathermap.org/find?) in order to check if your
> city, village is known or not. In which case you will have to find the city
> closest known and enter the latter in the configuration
> of your equipment to be able to retrieve information.

> **Tip**
>
> Once the search for your city is successful the openweathermap site
> shows you the information available and you should have in
> your browser a url of the type
> <https://openweathermap.org/city/2988507>. This number at the end of the url
> can also be entered into Jeedom equipment instead of
> Paris, fr for Example

>**IMPORTANT**
>OpenWeather provides a list of information for the next 120 hours; therefore, based on the current time, we only know part of the information on D + 4. Thus, this prediction on D + 4 is refined to become more precise as the current day progresses.. For this reason, certain information, such as the MAX temperature reached on D + 4 can only make sense at the end of the day..
