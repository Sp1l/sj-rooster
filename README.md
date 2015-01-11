# sj-rooster
School time-table Mobile mash-up for St. Joris College Eindhoven

Extremely light-weight and minimalistic mash-up of the school
time-table (Zermelo Rasterscherm) and the time-table changes.
Uses memcache to cache the origin and the result pages, once
warmed up this makes the site exceptionally quick (on a lowly
Core2Duo Celeron Ultra-Mobile CPU)

Sources:
* [Time-table](http://pcsintjoris.mwp.nl/SintJoriscollege/Leerlingen/Roosters/Roosterwijzigingen/tabid/579/Default.aspx)
* [Time-table changes](http://pcsintjoris.mwp.nl/SintJoriscollege/Leerlingen/Roosters/Huidigrooster/tabid/573/Default.aspx)

Requires
* PHP 5
* SimpleXML (origin pages can't be parsed as clean XML)
* memcached
