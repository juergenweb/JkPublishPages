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

## [1.3.7] 2024-04-26

This new version comes with some changes, additions and improvements.

### No more manipulation of the date input fields

In the previous versions, some hooks changed or removed the values of the 2 date fields. This version leaves the date fields completely untouched. This means: it doesn't matter what you enter, nothing is added or removed. What you enter in this field remains in this field.

The problem is that users can enter illogical dates (the start date is before the end date), users publish the page but the start date entered in the input field is in the future, users publish a page but the publish date is also in the future, and so on. 

The cron job only takes the values from the date fields and publishes or blocks the visibility of a page according to these settings.

This could lead to unwanted publication or non-publication of pages if the user has entered incorrect or illogical data. To prevent such behaviour, 1 new input field check and the additional output of warnings have been added.

### 1 new validator and multiple warnings for start and end date

As mentioned in the previous section, illogical entries can lead to undesirable publishing behaviour. The user is now informed by warning messages if the entries he has made will have an impact on the next cron run. In other words, the user will be informed/warned if a setting appears illogical and if there will be a change in the publishing status of the page during the next cron run. The user can then decide whether to accept this change or enter a different value in this field.

A new validator has been added that checks whether both dates (start and end date) are in the past or not. If both dates are in the past, an error is displayed, as it is not permitted (and makes no sense) to enter dates in the past for the scheduling of a page. In this case, you cannot save the page until you have corrected this error.

### New status information

Below the label of the publication fieldset, you will now find information about the current publication status of the page and what will be changed in the future via cronjob. This means you are always up to date and you can see what things will happen in the future (changes that will be made via cronjob).

### New schedule icons in the page tree

If the status of a page is changed in the future, you will find a small clock symbol next to the page title in the page tree. If you move the mouse pointer over this symbol, you will receive more detailed information about future activities. 

![alt text](https://raw.githubusercontent.com/juergenweb/JkPublishPages/main/images/pagetree.jpg?v=1)
