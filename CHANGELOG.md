# Change Log

## Version 1.5 (upcoming)
- function to add all course tutors as correctors

## Version 1.4.1 (2024-03-01)
- fixed pseudonym creation for writers

## Version 1.4 (2024-02-29)
- cleanup cross-task corrector assignments created by wrong imports
- improve corrector assignments export and import
    - only login is used to identify writers and correctors
    - create writer or corrector on the fly if login exists

## Version 1.3 (2024-02-24)
- unnumbered headlines schemes with one or three levels
- switch to allow browser spellcheck for writing a task
- fixed 0040291: Imagemagick error in edutiek on ubuntu 22.04
- fixed 0040150: pdf upload generates an error
- fixed 0040296: Missing German Translation in Participant Management // Supervision Log
- partially fixed 0040295: Supervisionlog does not contain all events
  - always write log entries for removal and exclusion
  - keep and exclude participant if writing hasn't started (prevent further access in tasks with instant participation)
  - log entries are not yet created for addition
- fixed 0040304: format templates for hierarchizing headings should display the preview in the dropdown menu
- Applied code style of ILIAS 8

## Version 1.2 (2024-01-21)
- fixed error with ascii control characters when written text is processed
- fixed juristic headline scheme
- added settings for processing written text (paragraph numbers, correction margins)
- added settings for pdf generation (heqader, footer, margins)
- improved pdf layout for written text and correction
- check existence of corrector comments for pdf upload and editor settings
- removed "Pilot" from language variables

## Version 1.1 (2023-12-20)
- First published version of the plugin for ILIAS 7