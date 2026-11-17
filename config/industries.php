<?php

/*
| Stored fallback for the industry dropdown: the 20 NAICS 2022 sectors (the standard Oanor serves).
| Used when there is no API key, the API has never answered, or it is down and nothing is cached.
| Sector level only, so the list stays short and the titles stay general.
*/
return [
    'sectors' => [
        'Agriculture, Forestry, Fishing and Hunting',
        'Mining, Quarrying, and Oil and Gas Extraction',
        'Utilities',
        'Construction',
        'Manufacturing',
        'Wholesale Trade',
        'Retail Trade',
        'Transportation and Warehousing',
        'Information',
        'Finance and Insurance',
        'Real Estate and Rental and Leasing',
        'Professional, Scientific, and Technical Services',
        'Management of Companies and Enterprises',
        'Administrative and Support and Waste Management and Remediation Services',
        'Educational Services',
        'Health Care and Social Assistance',
        'Arts, Entertainment, and Recreation',
        'Accommodation and Food Services',
        'Other Services (except Public Administration)',
        'Public Administration',
    ],
];
