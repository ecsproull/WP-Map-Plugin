# WP-Map-Plugin
Wordpress plugin for adding a Google map with push pins. Google wanted me to pay $39 for a plug-in that displayed custom data on the pushpins.
Why would I pay that when I could work a couple days and write my own. 

Includes admin menus for CRUD places to the map. You will need to get your own api keys from google. 
It uses one table in the WP database for storing Places and their info.
It was created to track my upcoming RV Trip so the places have an arrive and depart date although the plugin doesn't display them anywhere.
The labels are printed on the pushpins and for my purposes the display the order of the places we visited.
The plug-in is all in one file. There are the forms for CRUD the places and a shortcode to display the map on a page. [display_eds_map]

The latest edition moved the map keys to the DB. Both the keys and places tables are now created when the plugin is activated.

I'd be glad to answer any questions.

Next update will include displaying routing data on the map.

Here is a link to doc that better explains the code: https://edandlinda.com/wordpress-tech/writing-a-map-plugin/
