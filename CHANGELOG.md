# Change Log

Please look at the [changelog of version 3](https://github.com/EDUTIEK/LongEssayAssessment/blob/release3_ilias9/CHANGELOG.md)
for changes in that version.

## Upcoming version (in Git)

New functions, Visible changes
- Add Corrector as Filter in Correction Admin and Collection View
- Support a default essay import type with logins + '.pdf' as filenames in the ZIP
- Add table export to writer administration, writing dashboard and corrector start screen
- Add an optional writing supervision screen if the online editor is used
- Request the writers' acceptance for additional data shown on the supervision screen
- Add an optional start password for the writing and request it in the start modal
- Move the notification of writers from the protocol to the supervision and writer admin
- Subdivide the organizational settings
- Allow hiding of all settings except object properties (title, description, online)
- Don't show a tab if all settings on it are hidden
- Don't change a corrector assignment for an authorized correction

Bug Fixes / Refactorings

- Force storing of null for optional files (fixing creation from older templates)
- Fix error importing essays of type NRW
- Fix issue with the voting view in LiveVoting plugin
- Fix php error on statistics pages in ILIAS 11 with PHP 8.4
- Fix failing table export in ILIAS 11
- Don't create a writer entry when just the start page is viewed
- Extend the user session on update call from writer app
- Fix wrong assignment of locations to writers
- Fix the saving of disabled settings

## Version 10.2 (2026-08-03)

New functions, Visible changes

- Add text-based PDF marking for correction (pilot)
- Add maintenance lists, import and export for text snippets
- Add auto-completion for text snippets
- Use corrector colors in section headers
- Add description texts of rating criteria in corrector web app
- Improve authorization and pre-grading dialogs
- Allow bulk authorization only for pre-graded corrections
- Restructure settings for correction procedure and stitch decision
- Change oder of sections on the documentation settings page
- Add a notification of a corrector that is chosen for stitch decision
- Show a message of open tasks to correctors on their start screen
- Show a message of open assignments in correction administration
- Require a revert of correction authorizations to revert a writing authorization
- Require a revert of writing authorization to change a writing content
- Remove correction data if writing content is changed
- Show part of partial points in correction PDF only if some exist
- Add a filter for the status of the whole correction to the corrector start screen

Bug fixes / Refactorings

- Update TinyMCE to version 8.7 with improved arrow navigation in tables
- Update of components in writer and corrector web apps
- Fix compatibility issues with ILIAS 10.9 and 11
- Fix wrong redirect after failed adding of a writer
- Fix item status display after pre-grading is set
- Revise scrollbar visibility in corrector web app
- Don't show criteria overview of unauthorized other corrections in PDF
- Allow a view of unassigned corrections from correction administration 
- Don't use real corrector initials for correction
- Fix correction PDF file name
- Improve cleanup of obsolete and temporary files
- Ensure an existing default language
- Fix a parsing error if a comment has no marks
- Fix title and font sizes of correction PDF
- Fix wrong encoding in PDF creation
- Fix the display of deleted user data

## Version 10.1 (2026-06-22)

New functions, Visible changes

- Remove own authorization as a first corrector
- Remove the authorization of the first corrector as a second corrector
- Remove correction steps as an administrator, keep data to resume
- Show missing logins at mass import of essay pdfs
- Disable correction settings when authorized corrections exist
- New cron job to delete unused files that are older than one day

Bug fixes / Refactorings

- Font access for PDF creation
- Notification of correctors on changed writing content
- Link function for changed StaticUrlBuilder
- Database update from version 3 for points in comments
- Clean up obsolete correction data when a correction is finalized
- Front page and consulting page in correction pdf for NRW
- Sorting of criteria in corrector web app
- Prepare custom UI elements for later use in ILIAS 11
- Extend database field lengths for textual content
- Disable some correction settings if corrections are authorized
- Remove unused access to the current user from the service
- Deletion of files when an assessment is deleted
- Default values for a new rating cterion
- Paging change after modal edit in resources and croteria administration
- Avoid potential locking problem with text updates in web apps
- Reduce sending interval of open correction changes to 1 second (as in with writing app)
- Prevent applying snippet to disabled comments
- Restore zoom function for correction summary
- Fix display of other correctors revision title and statement
- Force multi line display of editor toolbar
- Fix initial column display for admin view in correction web app

## Version 10.0 (2026-04-14)

This is the initial version for ILIAS 10. The plugin and its web apps have been completely refactored for better extensibility in the future.

- Partial questions with different correctors
- Images, audio and video as resources for writing
- Extended text formatting (alignment, tables)
- More choices for pseudonymisation
- Formal approximation or consulting procedure for two correctors
- Stitch decision by a separate corrector with full correction features
- Data protection of unfinished correction status
- Configurable download possibility for correctors
- Templates for correction summaries
- Upload of a PDF file as a correction summary
- selectable correction features (comments, partial points, ratings)
- Three documentation and result export formats
- Selection and ordering of parts in a correction PDF
- Configuration of mail notifications for writers, correctors and admins
- Configurable table views in writing and correction administration
- Import of PDF exams from another system
- collective dowload of writings and corrections 
- Use of the export tab for all kinds of exports
- Use of background tasks for all long-lasting exports
- Set objects as templates for the creation of other objects
- Fix and hide settings in templates