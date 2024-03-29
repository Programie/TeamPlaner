# Changelog

## [1.4.0] - 2023-10-11

* Updated Dockerfile to use PHP 8.2
* Push Docker image to [Docker Hub](https://hub.docker.com/r/programie/teamplaner)
* Require at least PHP 7.0

## [1.3] - 2019-12-03

* Added support for report mails for multiple teams in a single mail
* Allow to define custom style for each entry type in config

## [1.2.3] - 2018-07-17

* Use browser locale for moment dates
* Center months in columns
* Better separation between months
* Use 90 degrees instead of 45 degrees for table header rotation
* Highlight date columns to make it easier to read

## [1.2.2] - 2016-12-22

* Do not include "extensions" folder in repository (it might be another git clone)

## [1.2.1] - 2016-11-19

* Fixed frontend path in build script (bin/build.sh)
* Fixed Vagrant box no longer available
* First pre-built release

## [1.2] - 2016-11-16

* Implemented iCal feed
* Use RESTful HTTP API in backend
* Use single "httpdocs" folder as docroot
* Prevent changing entries of other teams
* Fixed duplicating team selection menu
* Added configuration files for Vagrant VM
* Store selected team in cookie

## [1.1] - 2015-02-18

* Support for multiple teams
* Better reporting
    * Improved reporting API
    * CLI scripts allowing cronjob triggered reportings
* Notify user on data request errors

## [1.0] - 2015-02-18

First release