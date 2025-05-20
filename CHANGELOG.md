# Change Log

## Upcoming version

Plugin:
- Allow zero maximum points in correction settings
- Organisation setting to forward writer after finishing the assignment an url

Writer web app:
- Support marking and comments for instructions and resources
- Automatically show first resource if no instructions are given

Bug fixes:
- fix version conflict with ILIAS logger
- fix deletion of grades
- fix export of grade statistics (Mantis #45080)
- fix the calculation of essays that have not been passed in the grade statistics for writers when viewing multiple assessments

## Version 3.6 (2025-04-01)

Plugin:
-  Switch between grade based and point based graph in grade statistic

Bug fixes:
- Increased performance of grade statistics in larger essay environments
- Deletion of material
- Prevent correction of unauthorized essays
- Hide review time on writer screen if review is not enabled
- cleanup old temporary files
- Export of corrector assignments with empty essays

## Version 3.5 (2025-02-17)
Plugin:
- Configure and adapt time limit
- Export and import of long essay assessments
- Support general rating criteria which are not assigned to comments
- Move settings for rating criteria to the criteria tab
- Prevent editing of criteria when authorized corrections exist
- Handle existing points when mode for rating criteria is changed
- Support embedded resources (showed in iframe)
- Add Log entry for writer in writer administration
- Add writing authorization date to excel export of corrector assignments
- Support copy of correctors and rating criteria from other assessments
- Use table view for resources, rating criteria, grade levels and correctors

Corrector Web app:
- Replace layout table for paragraph numbers
- Improve comment layout
- Improve keyboard navigation between essay, comments and criteria points
- Support general rating criteria which are not assigned to comments
- Improve sum of partial points and authorization warning

Bug fixes:
- Fix missing download extension of converted text submission
- Fault tolerance when no grade level is found for final points
- Add authorization date to status info
- Wrong permanent link in review notification
- Prevent automated generation of lists when typing a numbering in writer
- Don't use short url for return from writer or corrector (may not be configured) 
- PhpSpreadsheet error at export of corrector assignments

## Version 3.4 (2024-11-04)
Plugin:
- Configure path to ghostscript in the plugin (with fallback)

Bug fixes:
- Avoid endless loop when PDF file is uploaded in addition to written text

## Version 3.3 (2024-10-23)
Plugin:
- GitHub workflow to create self-contained release packages
- Activatable grade statistics for writers
- Adds a graph and general visual fixes to grade stats
- Copy grade level from other essays in grade level organisation
- New submission type "PDF upload" for participants

Writer and Corrector web apps:
- Update of all frameworks and libraries 
- update of TinyMCE to version 7.3.0
- Accessibility improvements (structure, tab sequence, hotkeys, labels)
- Word / character counter in writer app 

Bug fixes:
- Avoid conflicts of composer packages with ILIAS 9
- Fix deprecated dynamic property in PHP 9.2
- Fix counting of not attended assessments in grade statistics

## Version 3.2 (2024-09-10)
- First published version for ILIAS 9
- Corresponds functionally to version 2.4 for ILIAS 8
- See branch release2_ilias8 for further history
