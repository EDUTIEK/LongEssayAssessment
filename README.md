# LongEssayAssessment
Plugin for the LMS ILIAS to realize exams with writing of long texts

## Installation

1. Copy the plugin to Customizing/global/plugins/Services/Repository/RepositoryObject
2. Go to the plugin folder
3. Execute ````composer install --no-dev````
4. Install the plugin in the ILIAS plugin administration

## Update from Git

1. Go to the plugin folder
2. Execute the following commands:
````
 rm -R vendor
 rm composer.lock
 composer install --no-dev
````
