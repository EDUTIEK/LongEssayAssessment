# Change log

## Version 1.3 (upcoming)
- Applied code style of ILIAS 8
- fixed 0040291: Imagemagick error in edutiek on ubuntu 22.04
- fixed 0040150: pdf upload generates an error
- fixed 0040296: Missing German Translation in Participant Management // Supervision Log
- partially fixed 0040295: Supervisionlog does not contain all events
  - always write log entries for removal and exclusion
  - keep and exclude participant if writing hasn't started (prevent further access in tasks with instant participation)
  - log entries are not yet created for addition

## Version 1.2 (2042-01-21)
- fixed error with ascii control characters when written text is processed
- fixed juristic headline scheme
- added settings for processing written text (paragraph numbers, correction margins)
- added settings for pdf generation (heqader, footer, margins)
- improved pdf layout for written text and correction
- check existence of corrector comments for pdf upload and editor settings
- removed "Pilot" from language variables

## Version 1.1 (2023-12-20)
- First published version of the plugin for ILIAS 7