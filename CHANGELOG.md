# Change Log
All notable changes to this project will be documented in this file.

## [1.3.2] - 2023-03-19

### Save options of fieldtype select in database after installing language file
After setting the language file to the appropriate language and save the module configuration, the options
for the "jk_action_after" select input field will be saved in the given language in the database too.
In lower versions, you have to add them manually in other languages.

## [1.3.3] - 2023-09-27

### New Configuration for adding parent page name beside the page name of new parent pages
Due to a request from Dino, a new configuration checkbox was added to add the parent page name beside the default page name in the select input for selecting a new parent, after the publishing time has been expired.
This could be useful if you are running a multi-domain site, where page names of these domains are the same.
You can read more on the issue report on Github: [Custom Parent page dropdown option text](https://github.com/juergenweb/JkPublishPages/issues/1)

## [1.3.4] - 2023-12-08

### Add fix for setPageStatusManually Hook
Thanks to Flo from the support forum for reporting this issue and a solution.
There has not been done a check on pages save if the given page contains the fields created by this module or not.
This will lead on certain cases to unwanted side effects concerning the publishing status.
This fix adds a check for this scenario.

## [1.3.5] - 2024-04-25

### Problem on automatic population of from field fixed

After no from and start date have been entered and the save but stay unpublished button will be pressed, then the published from date was autmatically populated with the current date and time. This leads to that the article will be published after the next cron run. This is unwanted, because the article should only be published if I directly press the publish button or if I manually enter a start date. 
The automatic population of the from field have been fixed now. Thanks to Flo for reporting this issue.

## [1.3.6] - 2024-04-25

### All auto population hooks have been removed

Now publish_from and publish_until fields will be no longer auto populated with Hooks. This change is a result of the discussion in the support forum.

## [1.3.7] 2024-04-29

This new version comes with some changes, additions and improvements.

### No more manipulation of the date input fields

In the previous versions, some hooks changed or removed the values of the 2 date fields. This version leaves the date fields completely untouched. This means: it doesn't matter what you enter, nothing is added or removed. What you enter in this field remains in this field.

The problem is that users can enter illogical dates (the start date is before the end date), users publish the page but the start date entered in the input field is in the future, users publish a page but the publish end date is in the past, and so on. 

The cron job only takes the values from the date fields and publishes or blocks the visibility of a page according to these settings.

This could lead to unwanted publication or non-publication of pages if the user has entered incorrect or illogical data. To prevent such behaviour, additional display of warnings has been added.

### Showing warnings for illogical start and end date settings

As mentioned in the previous section, illogical entries can lead to undesirable publishing behaviour. The user is now informed by warning messages if the entries he has made will have an impact on the next cron run. In other words, the user will be informed/warned if a setting appears illogical and if there will be a change in the publishing status of the page during the next cron run. The user can then decide whether to accept this change or enter a different value in this field.
These warnings are no errors. The only error will be thrown if the start date entered is after the end date. All other possibilities are allowed, but not always logical and can lead to publication status change on the next cron run.

![alt text](https://raw.githubusercontent.com/juergenweb/JkPublishPages/main/images/warnings.jpg?v=1)

### New status information

Below the label of the publication fieldset, you will now find information about the current publication status of the page and what will be changed in the future via cronjob. This means you are always up to date and you can see what things will happen in the future (changes that will be made via cronjob).

![alt text](https://raw.githubusercontent.com/juergenweb/JkPublishPages/main/images/publicationinfo.jpg?v=1)

### New schedule icons in the page tree

If the status of a page is changed in the future, you will find a small clock symbol next to the page title in the page tree. If you move the mouse pointer over this symbol, you will receive more detailed information about future activities. 

![alt text](https://raw.githubusercontent.com/juergenweb/JkPublishPages/main/images/pagetree.jpg?v=1)

## [1.3.8] 2024-05-13

This new version comes with a bugfix, 2 new additions and a safety improvement.

### Bug fix of German Umlaut problem in words using only capital letters

The status of the page has been displayed in the schedule plan with capital letters in the previous version of this module. This was done with the strtoupper() function in PHP and leads to a problem with German Umlauts. Instead of using the mb_strtoupper() function to solve this problem, I have decided to display the status with bold letters. So there is no longer a problem with German Umlauts.

### Fixing of changing the publishing status of a page in the page tree

Flo, from the Support forum pointed my intention to the following issue: Independent what status of the page is saved in the database and independent of what values had been set inside the publishing fields, you have always the possibility to change the publishing status in the page tree. This is not intended, because it ignores all field validations and a possibly wrong status could be set with unwanted side effects.

To prevent such a behavior, I have added a publication check also inside the page tree. I you click fe on the publish button, a validation runs in the background and checks the values of the 2 publishing fields. If these value allow you to set the page to published, everything is fine and the publish status will be saved via Ajax. If not, you will get an error message with further instructions inside a modal container and the status will not be saved.

![alt text](https://raw.githubusercontent.com/juergenweb/JkPublishPages/main/images/pagetree.png?v=1)

### Adding more safety to save publishing field values

This is also another idea from Flo.

In the previous version, the validation of this module only shows warnings, if a user has entered illogical values into the publishing fields. This could lead to unwanted publishing of pages in the future, if the user ignores this warnings.

This could be a massive problem, if the page contains sensitive content and will be published accidentaly before a given date. Lets assume you want to set a special offer for a product online, but your customer does not allow to make this offer public until a given date. 

If an incorrect setting has been made and the warnings have been ignored, it could happen that the cronjob publishes this offer before the allowed date.

To "play safe" and prevent such a scenario, I have replaced the warnings by errors and the start field value will be removed (cleared) before saving in certain situations to stop the Cronjob of publishing a page accidentaly.

![alt text](https://raw.githubusercontent.com/juergenweb/JkPublishPages/main/images/field_deletion.png?v=1)

In addition I have also added a functionality to change the publish status according to the settings. This means, if a page has been saved as "unpublished" but the Cronjob would make this page "published" on the next run, the page will be immediately saved as published and the user can change this afterwards, if this is not the desired behavior.

Example: The user will save this page as "unpublished", but his publish settings only allow to publish the page, then this page will be immediately published at the saving process, but the user will get an information, that the prefered status could not be saved.

![alt text](https://raw.githubusercontent.com/juergenweb/JkPublishPages/main/images/status_change_warning.png?v=1)

## [1.3.9] 2024-05-14

### Reverting deletion of start field value to prevent accidential publication

There is no need to delete the start field value, as the module will change the publication status to the appropriate value beforehand. In this case, the cronjob does not publish a page if it has the status unpublished and the times set are not within the time range. This was therefore an unnecessary step that has now been removed.

What didn't fit were the texts for the publication schedule plan in all scenarios. In some circumstances, the text did not exactly match the action that the cronjob will perform in the future. This has now been changed. To be more precise: This was the case if the start date was before the end date. In this case the module throws an error, sets the status to "unpublished forever"  and the Cronjob does not do anything.

But the information that has been displayed was "will be published on.. , will be unpublished on....". This was fixed now and the text "Page remains unpublished" will be displayed instead, which is exactly what will happen.

As always, please keep an eye out if something is not working as expected and report any problems you discover.

## [1.3.10] 2024-05-28

### Wrong timestamp calculation by using html or select input type fixed

The date input field validation has worked properly only if input type text was selected. If you have chosen "html" or "select" the date will not be converted to a timestamp correctly.
The reason was that the dateformat used by this input types is different from the default text input type. 
Therefore every input type needs its own treatment to get the correct timestamp. This has been now solved by creating a new method to convert the date to a timestamp depending on the input type.

## [1.3.11] 2024-09-26

Missing "input_html" property for date fields on new installations fixed. 

https://github.com/juergenweb/JkPublishPages/issues/2

## [1.3.12] 2024-09-27

There was an Ajax error during an image upload if the template contains the date fields of this module. Thanks to ShadowMoses36 for reporting this issue.

Short description:
There was a problem during the AJAX call of an image caused by my custom sub-headline for this module.
The headline will be output during the Ajax call too and therefore the image upload Javacript complains about the "unexpected character on line 1 column 1.

This error has been fixed now and image upload works as expected.

https://github.com/juergenweb/JkPublishPages/issues/3

## [1.3.13] 2024-09-29

There was a bug when searching pages for a specific status on multilingual websites. The result was that pages will not be correctly published or unpublished according to date settings. As mentioned, this problem occured only on sites with multiple languages. This bug has been fixed now. 

Writing mistake in date format of inputfield "jk_publish_until" fixed (d-m-Y instead of d-M-Y).

## [1.3.14] 2024-11-02

- **Support for RockLanguage added**

If you have installed the [RockLanguage](https://processwire.com/modules/rock-language/) module by Bernhard Baumrock, this module now supports the sync of the language files. This means that you do not have to take care about new translations after you have downloaded a new version of JKPublishPages. All new translations (at the moment only German translations) will be synced with your your ProcessWire language files. 

Please note: The sync will only take place if you are logged in as Superuser and $config->debug is set to true (take a look at the [docs](https://www.baumrock.com/en/processwire/modules/rocklanguage/docs/)).

BTW, the (old) CSV files usage is still supported.

## [1.3.15] 2025-01-12

- **Performance check added**

Many thanks to MarkE from the support forum, who discovered a performance problem with websites with many pages.

The problem was that the getParentPages() function ran on all pages, not just on pages that had a publish field within the template. This resulted in load times of up to 20 seconds if a website has a lot of pages.

A new check within the getParentPages() method, which also comes as a suggestion from MarkE, should solve this problem now.

## [1.3.17] 2026-09-25

This version contains a large number of bug fixes, security fixes and improvements. It is recommended for all users.

### Bug fixes

- **Automatic publishing did not work:** The cron job did not find unpublished and hidden pages, because the page selectors did not contain "include=all". As a result, pages were never published automatically, and hidden pages were never unpublished, trashed, moved or deleted. Access checks are disabled for the cron job too, so pages that are not viewable by guests are also processed (LazyCron runs mostly as guest).
- **Actions after the end of publication were executed too early:** The action after the end of publication (trash, move, delete) was also executed on manually published pages whose start date was still in the future. Now the action is only executed if the end date has been reached - otherwise the page is only unpublished.
- **Manually unpublished pages were never trashed, moved or deleted:** The action after the end of publication is now also executed on pages that have been unpublished manually before the end date.
- **Deleted pages were saved again:** Pages are no longer saved after they have been deleted permanently or moved to the trash.
- **Pages with children were deleted silently or stopped the cron job:** A page with child pages is no longer deleted - it is unpublished instead. An error on one page no longer stops the processing of all other pages.
- **Moving without a valid new parent:** If no valid new parent page is selected, the page is unpublished instead of being moved. The new parent must not be the page itself, one of its children, a page in the trash or in the admin tree, and the family settings of both templates must allow the parent/child combination.
- **Status change applied to other pages:** The status change after the date validation was applied to every page saved during the same request (e.g. repeater items or pages saved by the cron job). Now it is only applied to the page that is edited.
- **Other status flags were removed:** Unpublishing a page no longer removes other status flags like "hidden" or "locked" (addStatus instead of setStatus).
- **Page tree publish/unpublish check:** The check used the page from the URL and a session value, which was only removed if the action was denied. A leftover session value could block later publishing actions. Now the action is only stored for the current request, and the dates of the page that is actually published or unpublished are checked.
- **Installation failed after an incomplete uninstallation:** If a field (e.g. the fieldset closer "jk_publish_open_END") was left over, the installation failed with "Field may not be named ... because it is already used by another field". Every field is now checked separately and existing fields are reused. The check for the field "jk_move_child" used a wrong field name. The uninstallation now removes the fields from all templates, skips missing fields and does not stop if one field cannot be deleted.
- **Fields were added/removed when saving the configuration of ANY module:** The fields are now only added to or removed from templates when the configuration of this module is saved, only on the templates offered in the module configuration (no system templates, no homepage template), and only changed templates are saved. The checkboxes show the templates that actually contain the publishing fields.
- **Schedule plan did not match the cron job:** The schedule plan in the page editor, the icon in the page tree and the sub-headline are now calculated with the same rules the cron job uses. They now also show the action after the end of publication and changes that happen on the next run of the cron job. Every date is formatted with the output format of its own field, and the plan is no longer split by commas.
- **Date fields with the input type "select"** are now evaluated correctly (year, month and day are sent as separate values).
- **Inconsistent boundaries:** A page is inside the publication period if start <= now <= end, the publication has ended if end < now. Before, a page with a start date exactly at the current time could be published and unpublished by the same cron run.
- **The select field "jk_action_after"** is now created with the correct setting "inputfieldClass".
- **JavaScript:** The script no longer overwrites window.onload (which disabled other scripts or was disabled by them). The toggle link now checks all checkboxes if at least one is unchecked, otherwise it unchecks all (before, the first click always checked all), it only changes the checkboxes of its own field and fires change events, so ProcessWire notices the changes.

### Security

- **Permission checks for the publishing settings:** Users without the required permissions could use the publishing fields to publish, trash, delete or move a page (immediately or via the cron job). Changing the start or end date now requires the permission page-publish, the action "move to trash" page-trash, "delete permanently" page-delete and "move page" the permission to move the page to the selected parent. Not allowed changes are reverted and an error message is displayed. Superusers are not affected.
- **Manipulated POST parameter:** The status determined by the date validation was passed via the POST parameter "changestatus", which could be manipulated by the user. It is now stored internally.
- **Selector injection:** The URL parameter "id" was passed unsanitized as a selector to $pages->get() on every request (also on the frontend). It is now sanitized as an integer.
- **SQL injection:** The translated titles of the options of "jk_action_after" were inserted directly into the SQL string. Now a prepared statement is used, and the options are identified by their option id instead of their title.
- **Input only from POST:** The date validation reads the values explicitly from POST and only accepts scalar values (before, GET parameters and cookies were also considered, depending on $config->wireInputOrder).
- **Escaped output:** The texts in the page tree, the headline and the schedule plan are entity-encoded.
- **Log:** The log entries of the cron job (Setup > Logs > jkpublishpages) contain the user who changed the page last, because the cron job itself runs mostly as guest.

### Performance

- The module configuration is no longer saved to the database on every request (also on the frontend).
- The possible new parent pages are only loaded on the page edit screen when they are needed (before: on every request with an "id" URL parameter).
- The JS and CSS files are only added in the admin. The file modification time is used for cache busting instead of the current time (which prevented browser caching).

### Improvements

- **Namespaces:** The JavaScript file uses a single global namespace object "JkPublishPages". All CSS classes and IDs are prefixed with "jkpp-" to avoid conflicts with other modules, because the CSS file is loaded on every admin page. The toggle link is only added to the template selection of this module (before, it was added to every checkbox field named "input_templates") and is a keyboard accessible button.
- **German translations** for all new texts (RockLanguage file and CSV language file).
- **.gitattributes:** Tests, images and the vendor folder are no longer included in release archives. PHP files always use LF line endings (PSR-12).
- **.gitignore:** Dependencies (vendor, node_modules), caches, Git bundles, IDE and operating system files are not committed.

### Code quality

- **PSR-12:** All PHP files (JkPublishPages.module, JkPublishPagesRules.php and all tests) are formatted according to PSR-12 and checked with PHP_CodeSniffer. Only a few translatable texts in JkPublishPages.module are longer than 120 characters, because they must not be split (the ProcessWire language parser only recognizes complete strings).
- **Documentation:** Every method and property has an English docblock.
- **Rules class:** The decision logic of the cron job has been moved to the new class JkPublishPagesRules, which has no dependency on ProcessWire. The cron job has been split into findCandidates() and processPage().
- **Clean-up:** Unused methods and properties have been removed, a date validation branch that could never be reached has been simplified, and missing fields no longer cause warnings.

### Tests

- **Unit tests** for the decision logic and the schedule plan: "composer install" and "composer test" (PHP 8.1+ for PHPUnit 10/11).
- **Integration tests** against the ProcessWire installation the module is installed in: "composer test:integration". They create their own test template, test pages, a test role and a test user and remove everything afterwards. The cron job itself is never executed (it would process all pages of the site). The tests cover publishing, unpublishing, trash, move and delete, hidden pages, the cron job running as guest, the permission checks, the date validation in the page editor, the schedule plan and the log. Another installation can be used via the environment variable JKPP_PW_INDEX.
- **JavaScript tests** (Node test runner and jsdom): "npm install" and "npm test".
