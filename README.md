**PLEASE NOTE:** This version (release_2_ilias8) requires ILIAS 8 and **at least ILIAS 8.11.**
The version for ILIAS 7 is found in the branch release1_ilias7.

# LongEssayAssessment
Plugin for the LMS ILIAS open source to realize exams with writing of long texts.

The EDUTIEK project (acronym for "Einfache Durchführung textintensiver E-Klausuren") is developing a comprehensive software solution for online exams in subjects in which longer texts have to be submitted as exam solutions. These include law, history, linguistics, philosophy, sociology and many more.

The "Long Essay Assessment" is a repository object and bundles all functions for the realisation of a text exam. Responsibilities for creating, carrying out and correcting tasks are assigned to different people via the authorisation system. Support material can be provided for editing and correction is supported by an evaluation scheme. All results can be output in PDF/A format for documentation purposes.

The integrated "Writer" is a specialised editing page for examinees during the exam. The text editor and the task or additional material can be displayed side by side or on a full page. All editing steps are logged and are reversible. Even if the network is interrupted, you can continue writing and the editing steps will be saved afterwards. At the end of the editing time, the written text is displayed for review and its submission is finally confirmed.

The integrated "Corrector" is a specialised editing page for the proofreaders. In the submitted text, passages are marked and provided with comments. With each comment, partial points can be awarded based on the evaluation scheme. The text and comments are clearly displayed next to each other, optionally also with the comments from the first correction in the case of a second correction. To create the overall vote, a proposal for the final grade is calculated from the sum of the partial points, which can be accepted or changed. The vote can be used to create a textual overall assessment.

Please look at the [EDUTIEK-Anleitung](docs/EDUTIEK-Anleitung.pdf) for a detailed description in German.

## Branches and Versions

The plugin is published for ILIAS in different branches:

* **release1_ilias7** will be maintained until end of 2024. It will receive bug fixes mainly.
* **release2_ilias8** will be maintained until April 2025 and will receive bug fixes as well as small features without breaking existing functionality and data. It may receive security fixes until end of 2025.
* **devX_iliasY** are development branches. Please do not use them.

Please consult the [CHANGELOG](CHANGELOG.md) to see the changes of different versions in this branch.

## System Requirements

This version (release_2_ilias8) requires **ILIAS 8** with minor version **8.11** or higher.

The requirements of this plugin are nearly the same as for ILIAS with the following exceptions:

* **PHP 7.4** or **PHP 8.0** is required. 

* The following PHP extensions are required by the plugin: **curl, dom, gd, imagick, json, xml, xsl**. On Debian/Ubuntu execute:

````
    apt-get install php8.0-curl, php8.0-dom, php8.0-gd, php8.0-imagick, php8.0-json, php8.0-xml, php8.0-xsl
````
The PHP imagick extension uses Imagemagick and ghostscript to convert uploaded PDF files to images. On Debian/Ubuntu execute:

 ````
    apt-get install ghostscript
    apt-get install imagemagick
````

ImageMagick must be allowed to convert PDF files. To enable this please edit the file `/etc/ImageMagick-6/policy.xml` and 
ensure that the following line is ìncluded:

````
 <policy domain="coder" rights="read | write" pattern="PDF" />
````

## ILIAS Configuration

A correct **time zone** must be configured in ILIAS for the processing of time information to work correctly. It must correspond to the time zone on the PHP server and in MariaDB or MySQL server. Under Linux it can be determined with `timedatectl`, in MariaDB with `SHOW GLOBAL VARIABLES LIKE '%time_zone';`.  It is set in the ILIAS setup via the `config.json` file:

``` 
"common" : {
    "server_timezone" : "Europe/Berlin",
    ...
}
```

ImageMagick may run into resource limits when uploading larger PDF files of participant submissions. A direct processing by **ghostscript** is better. To do this, the path to ghostscript must be set in the ILIAS setup via the `config.json` file:

```
"preview" : {
    "path_to_ghostscript" : "/usr/bin/gs"
},
```


## Installation and Update

1. Copy the plugin to `Customizing/global/plugins/Services/Repository/RepositoryObject`
2. Execute `composer install --no-dev` in the plugin folder.
3. Execute `composer du` in the ILIAS main directory.
4. Install or update the plugin in the ILIAS plugin administration.

**Optional for Cron Support:**
1. Install [LongEssayAssessmentCron](https://github.com/EDUTIEK/LongEssayAssessmentCron) as described in its [documentation](https://github.com/EDUTIEK/LongEssayAssessmentCron/?tab=readme-ov-file#installation). 
2. Activate the cron jobs of the `Plugin/LongEssayAssessmentCron` component in the administration menu item _System Settings and Maintenance > General Settings > Cron Jobs_ according to your needs.

## Known Issues

The writing and correction of exams is tested with Firefox and Chrome, so modern Chromium based browser should work. We know about issues with older Safari browsers. Please test with you local system before writing an exam and offer a tryout service for students who should write on their own device.
