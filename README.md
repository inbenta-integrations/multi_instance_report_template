# MULTI INSTANCE REPORT
 
### TABLE OF CONTENTS
* [OBJECTIVE](#objective)
* [FUNCTIONALITIES](#functionalities)
* [INSTALLATION](#installation)
* [DEPENDENCIES](#dependencies)
 
### OBJECTIVE
The purpose of this project is to have a tool to concentrate the Sessions and User questions for multiple instances in a simple way.
 
### FUNCTIONALITIES
This application allows to see **Session data** and **User questions data** (for the configured instances), with the next values:
* Total (sessions / user questions)
* Found (sessions / user questions)
* No clicks (sessions / user questions)
* Not found (sessions / user questions)
 
Data can be filtered through two filters, the first one for select instances and another for date range. The report can be downloaded in CSV or XLS format.
 
### INSTALLATION
In order to install the application, you need to run the next command from the root of your project:
```bash
$ composer install
```
##### Configuration files
Once the project is installed, the configuration for the instances is needed. Into the `"config/app.php"` file is where the instances can be setted (you can add as many instances as required). The next values are needed:

* instance name, 
* apiKey
* secretKey
* signatureKey

```php
return [
  'lang' => 'en',
  'instances' => [
    'instance_name_1' => [
      "apiKey" => "",
      "secretKey" => "",
      "signatureKey" => ""
    ],
    //... more instances
  ]
];
```

You can configure another default language, just adding the file into the `"lang/"` folder and changing the value in the `"config/app.php"`. Example for english:
```php
return [
    'tag_title' => 'Multi Instance Reporting',
    'tag_instances' => 'Instances',
    'tag_date_range' => 'Date range',
    'placeholder_date-range' => 'From - to',
    'btn_download' => 'Download XLS',
    'btn_submit' => 'Search',
    'select_all_instances' => 'All instances',
    'col_instance_name' => 'Instance Name',
    'col_date' => 'Date',
    'col_total_sessions' => 'Total Sessions',
    'col_found_sessions' => 'Found Sessions',
    'col_no_click_sessions' => 'No Clicks Sessions',
    'col_not_found_sessions' => 'Not Found Sessions',
    'col_total_uqs' => 'Total User Questions',
    'col_found_uqs' => 'Found % User Questions',
    'col_no_clicks_uqs' => 'No Clicks % User Questions',
    'col_not_found_uqs' => 'Not Found % User Questions',
    'table_total' => 'Total'
];
 ```
 
### DEPENDENCIES
This application imports `inbenta-products/api-signature-client-php`, `guzzlehttp/guzzle` as a Composer dependency, that includes `guzzlehttp/psr7@^1.5` and `phpunit/phpunit@^4.8`.