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
