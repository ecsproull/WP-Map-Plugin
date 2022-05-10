# WP-Map-Plugin
Wordpress plugin for adding a Google map with push pins. Google wanted me to pay $39 for a plug-in that displayed custom data on the pushpins.
Why would I pay that when I could work a couple days and write my own. 

Includes admin menus for CRUD places to the map. You will need to get your own api keys from google. 
It uses one table in the WP database for storing Places and their info.
It was created to track my upcoming RV Trip so the places have an arrive and depart date although the plugin doesn't display them anywhere.
The labels are printed on the pushpins and for my purposes the display the order of the places we visited.
The plug-in is all in one file. There are the forms for CRUD the places and a shortcode to display the map on a page. [display_eds_map]

I'd be glad to answer any questions.

~~~
CREATE TABLE `places` (
  `place_ID` int(11) NOT NULL AUTO_INCREMENT,
  `place_name` varchar(45) NOT NULL,
  `place_info` text NOT NULL,
  `place_lat` float NOT NULL,
  `place_lng` float NOT NULL,
  `place_label` int(11) DEFAULT NULL,
  `place_icon_type` tinyint(4) DEFAULT 0,
  `place_address` varchar(100) NOT NULL,
  `place_phone` varchar(45) NOT NULL,
  `place_website` varchar(150) NOT NULL,
  `place_arrive` date DEFAULT NULL,
  `place_depart` date DEFAULT NULL,
  `place_hide_info` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`place_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;
~~~
Next update will include displaying routing data on the map.

Here is a link to doc that better explains the code: https://edandlinda.com/wordpress-tech/writing-a-map-plugin/
