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
