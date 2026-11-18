<?php

/*
| The industry dropdown on the company invite form and the company profile.
| `file` is the complete NAICS 2022 list from the U.S. Census Bureau (public domain): 2,125 codes with their
| titles, from the 20 main sectors to the national industries. It is the dropdown when there is no API key,
| the API has never answered, or it is down and nothing is cached (see App\Services\IndustryCatalog).
*/
return [
    'file' => database_path('data/naics-2022.json'),
];
