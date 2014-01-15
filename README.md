Weather-Sentence
================

A PHP script to make a readable sentence of the current and today's weather.

Two parameters are currently handled:
  * loc=<decimal latitude>,<decimal longitude>
    Where the forecast is for.
  * debug
    Echo the JSON inputs.

The output is a pair of sentences. The first describes the current weather (including wind, precipitiation and temperature if recieved from forecast.io), the second describes the weather forecast for the current day. If any alerts are present, they are appended.

Example Output
--------------

The Weather for Fletching is Drizzle. It is 9 degrees, there is a moderate breeze from the South South West and there is a certainty of very light rain. There will be a high of 10 and a low of 5, there will be a moderate breeze from the South South West and there is a certainty of very light rain.



====
[![Build Status](https://travis-ci.org/darac/Weather-Sentence.png)](https://travis-ci.org/darac/Weather-Sentence)
