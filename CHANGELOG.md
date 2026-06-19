# Change Log

Please look at the [changelog of version 3](https://github.com/EDUTIEK/LongEssayAssessment/blob/release3_ilias9/CHANGELOG.md)
for changes in that version.

## Upcoming Version

New functions, visible changes

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