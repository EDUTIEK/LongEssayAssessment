# LongEssayAssessment (Pilot Version)
Plugin for the LMS ILIAS open source to realize exams with writing of long texts.

This **pilot version** is currently **under development** to complete the functionality required by the [EDUTIEK project](https://www.edutiek.de).

An initial set of features is available in the **pre-test version** names LongEssayTask which is maintained in a different [GitHub repository](https://github.com/fneumann/LongEssayTask)

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
