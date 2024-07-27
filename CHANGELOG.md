# Change Log

## Upcoming version
New features:
- correction setting to anonymize the corrector names for the writers
- add correction finalize date to results export
- general cron job support [cron job support](https://github.com/EDUTIEK/LongEssayAssessmentCron/)
- disable correction reviews 
- send review notifications after finalization and within review period via cron job
- compare grade statistics over multiple plugin objects within the same repository context
- adding writer and corrector statistics with a csv export
- allow correction without points if no grade level is defined
- simplify the writer start screen when essay is authorized
- write mail to selected writers and/or correctors
- forced setting for inclusion of correction details
- writing and download of correction reports

- Bug fixes:
- plugin: fix uninstallation problems
- plugin: fix 40808: Blank pages occur when Text and PDF are submitted
- plugin: fix huge user images in remove/repeal participant confirmation modal
- plugin: fix double file error in pdf version upload, when nothing was changed
- plugin: fix possibility to remove pdf version uploads
- plugin: fix corrector assignment file upload error handling with required file
- plugin: fix str_word_count on null in corrector assignment file export
- corrector: fix jumping in scrolled essay after autosave
- crrector: fix orphaned data after quick navigation between writers
- corrector: improve startup when sendings are open in local data


## Version 2.2 (2024-06-24)
New features in corrector:
- allow multiple marks for one comment if drawn with shift key pressed
- show warnings for empty summary or diverging points in authorization dialogue

Bug fixes:
- plugin: fix uninstallation problems
- plugin: fix 40808: Blank pages occur when Text and PDF are submitted
- plugin: fix corrector assignment file upload error handling with required file
- plugin: fix str_word_count on null in corrector assignment file export
- plugin: fix wrong display of correction status for corrector 1
- plugin: fix error at correction start as admin, if object is not online
- plugin: adding the possibility to compare grade statistics over multiple plugin objects within the same repository context
- service: reduce size of correction PDF
- service: fix wrong end date in PDF header
- service: fix removing of empty lines
- writer: fix paste from word with fake numbered lists
- writer: fix wrong display of numbering on preview screen when sendings are open
- corrector: fix problems with loading resources and pages in safari
- corrector: allow multiple marks for one comment if drawn with shift key pressed
- corrector: show warnings for empty summary or diverging points in authorization dialogue
- First published version for ILIAS 9
- Based on version 2.2 for ILIAS 8