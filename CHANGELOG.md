2.6.1 [10th of April 2021]
- maintenance update of symfony polyfills

2.6 [26th of January 2021]
- bump up minimum php version yto 7.3 as 7.2 is EOL
- update vlucas/phpdotenv to newest version

2.5 [6th of November 2020]
- fix few undefined index errors
- add more flexibility to the configuration options

2.4.1 [14th of October 2020]
- Fix undefined offset notices when accessing $_ENV
- Fix SSL behind reverse proxy

2.4 [25th of September 2020]
- Allow for storing common environments' configuration in /configuration/common.php which is executed AFTER the environment specific config
- use Dotenv::createUnsafeImmutable() for legacy reasons, we should stick to $_ENV though

2.3.1 [24th of September 2020]
- change usage of getenv to $_ENV. Potentially dangerous, needs investigation.

2.3 [23rd of September 2020]
- add support for wp_get_environment_type added in WP Core 5.5
- update vlucas/phpdotenv to 5.2

2.2.2 [20th of August 2020]
- allow for setting the AUTOMATIC_UPDATER_DISABLED constant in environment configs

2.2.1 [17th of August 2020]
- regular maintenance update - update composer dependencies

2.2 [24th of March 2020]
- Move vlucas/phpdotenv from RaccoonWP to RaccoonWP-Core
- Bump minimum required PHP version

2.1.1 [13th of February 2020]
- Remove WP_HOME from dotenv required params

2.1 [13th of February 2020]
- Bump minimal PHP version to 7.2
- Allow to define custom webroot (public) directory

2.0 [13th of November 2019]
- initial release as a separate library
