Configuration
=============

O plugin Weather permite recuperar dados meteorológicos para
uma ou mais cidades. Entre outras coisas, fornece informações
nascer e pôr do sol, temperatura, previsão, vento,
etc. As informações recuperadas vêm do site
openweathermap.

Configuração do plugin
-----------------------

Após instalar o plug-in, você deve ativá-lo e depois inserir seu
Chave da API /

Para obter sua chave de API, você precisa ir
[aqui](https://home.openweathermap.org), criar uma conta e depois lá
copie sua chave de API na área fornecida na página
Configuração do plugin.

> **Important**
>
> Você tem que esperar algumas horas depois até que a chave seja
> ativo antes que você possa recuperar informações

Configuração do equipamento
-----------------------------

Aqui você encontra toda a configuração do seu equipamento :

-   **Equipamentos clima nome** : nome do seu equipamento meteorológico

-   **Activer** : torna seu equipamento ativo

-   **Visible** : torna seu equipamento visível no painel

-   **Objeto pai** : indica o objeto pai ao qual pertence
    o equipamento

-   **Ville** : Você deve colocar o nome da sua cidade seguido pelo código do país,
    ex : Paris, fr

-   **Exibição móvel completa** : exibe todos
    informações meteorológicas ou não no celular

Você encontrará abaixo todos os comandos disponíveis, bem como os
possibilidade de historiar ou não os valores numéricos. O código (número)
dependendo das condições está disponível
[aqui](https://openweathermap.org/weather-conditions)

Os dados meteorológicos são atualizados a cada 30 minutos.

> **Tip**
>
> Aconselhamos que você vá
> [aqui](https://openweathermap.org/find?) para verificar se o seu
> cidade, vila é conhecida ou não. Nesse caso, você terá que encontrar a cidade
> mais próximo conhecido e digite o último na configuração
> do seu equipamento para poder recuperar informações.

> **Tip**
>
> Assim que a pesquisa da sua cidade for bem-sucedida, o site openweathermap
> mostra as informações disponíveis e você deve ter em
> seu navegador, um URL do tipo
> <https://openweathermap.org/city/2988507>. Esse número no final do URL
> também pode ser inserido no equipamento Jeedom em vez de
> Paris, fr por exemplo

>**IMPORTANT**
>O OpenWeather fornece uma lista de informações pelas próximas 120 horas; portanto, com base no tempo atual, sabemos apenas parte das informações em D + 4. Assim, essa previsão em D + 4 é refinada para se tornar mais precisa à medida que o dia atual progride.. Por esse motivo, certas informações, como a temperatura MAX atingida em D + 4, só fazem sentido no final do dia..
