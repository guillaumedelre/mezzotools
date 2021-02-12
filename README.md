# BURNDOWN

A burndown chart application connected to Jira.

## Requirements:
* php >= 7.2.5
* any webserver (If you don't have a webserver you can install symfony binary by running `./symfony-installer.sh`)

## Setup
* Clone the project with `git clone <url-of-the-repository> <project-name> && cd <path-to-cloned-project>`  
* Install dependencies with `composer install`
* Load de data: `bin/console app:cache:warmer` (this command must be under an hourly cron task)
* Run the webserver if it is not already done (you can do it with `symfony server:start` if you have installed the symfony binary)
