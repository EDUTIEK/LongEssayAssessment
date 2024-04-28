# Change Log

## Version 1.6 (upcoming)
- plugin: extend result export with correctors names and points
- plugin: apply utf8 decoding in csv export of results for better use with ms excel
- plugin: align implementation of file delivery and screen messages with version 2 for ILIAS 8
- plugin: add description to material on writer start creen
- plugin: fix file removal, when the file resource is just saved without an upload. Prevent from saving, when file upload is empty.
- plugin: support PDF conversion to images by ghostscript. Ghostscript is directly used if its path is configured in ILIAS. The conversion with imagick may not process all pages of an uploaded PDF file.
- service: fix html processing error when comments include "&"
- service: prevent use of outdated frontends from cache

## Version 1.5 (2024-03-23)
- plugin: function to add all course tutors as correctors
- plugin: fixed 0040804: Minimal margin size is not set to minimum (5mm) but to 4
- plugin: optimized file delivery of instructions, material, solution
- service: fixed error sending new steps after partially successful sending
- writer: don't block writer startup by loading instructions and material
- writer: fixed scrolling area of preview for authorisation

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