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
