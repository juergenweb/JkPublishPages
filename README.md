# JkPublishPages - ProcessWire module to publish and unpublish pages depending on dates

This module is intended to schedule pages depending on publishing date and times.
After the publishing end date has been reached, you can decide what should happen with this page.
There are several options:

* No, action - The page is just set to unpublished
* Move to trash - The page will be moved directly to the trash after the page was unpublished
* Delete permanently - The page will be deleted directly without moving it to the thrash before
* Move the page to a new parent - This could be useful if you want to move the page fe to an archive. In this case, the
page will only be moved to a new position inside the page tree, but will not be unpublished, so it could still be 
displayed

This module creates new input fields for entering dates and to select what should happen after the publishing end date
has been reached. These fields can be added with one click to templates. There is no need to add these fields to each
desired template manually.

## Module configuration fields
![alt text](https://raw.githubusercontent.com/juergenweb/JkPublishPages/main/images/configuration.png?v=1)

As you can see, you have a select input with all LazyCron time intervals - default is 1 hour.
Afterwards is a list of checkboxes. Each checkbox represents a frontend template where you can add the publishing fields.
If the checkbox of a certain template is checked, then the publishing fields will be added automatically to this template
after saving the module configuration.
On the opposite, the publishing fields will be removed from all templates where the checkbox is unchecked.
So there is no longer the need to add or remove the publishing fields manually to/from each template.

To enable/disable all checkboxes at once, you can use the toggle link above the checkboxes. This makes it much more
comfortable.


## Template view

Every template that is selected in the module configuration has the following fields to set the publishing options.
![alt text](https://raw.githubusercontent.com/juergenweb/JkPublishPages/main/images/default-page-fields.png)

If a publish end date was set, an additional field will appear. In this case, you can select what should happen after the
publish end date has been reached.
![alt text](https://raw.githubusercontent.com/juergenweb/JkPublishPages/main/images/action.png)

If you have selected the action to move the page to a new parent a further field appear, where you can select the new
parent page.
![alt text](https://raw.githubusercontent.com/juergenweb/JkPublishPages/main/images/move.png)


This is what it looks like inside the template of a page.

## Usage
Entering values inside the 2 date fields is optional.

* If you have entered no values inside the date fields means that the page is visible after pressing the save button 
without time limitations.
* If you enter only a publishing from date means that the page will be visible from this date on and has no end date.
* If you enter only a publishing until date means that the page will be visible immediately after publishing til the end
  date.
* Entering both, start and end date means that the page is visible in the time range in between.

So this module publishes or disable the publishing of a page according to the dates you have set.

If you want to change the labels of the input fields or the title of the fieldset, please go to the fields'
configuration page and make your changes there.

But be aware: If you are using the language file of this module, this will overwrite all manual changes of the language
values.

As an addition, you can decide afterwards what should happen with this page. You can set that the page should be deleted
permanently, should be moved to trash or should be moved to another position inside the page tree.

If you want to move the page to be a child of another page, you can only select pages as parent, which are allowed to be 
a parent according to your settings.

Explanation: 
You have some setting possibilities inside your template configuration to set which templates are allowed to be a parent
or which templates are allowed to be a child template. In addition, you can decide, whether a given template can have
children or not.
All these settings have an impact on the select drop-down input field. This means that only pages can be selected to be
a new parent page for the current page, that is allowed to be a parent page (depending on your settings).

In other words, the values of the select field displayed are dynamically and can differ from page to page. You do not
have to take care of it - it all runs behind the scenes. 

Only one thing to mention in this case: If you change your template settings later on, and you have chosen a
parent page which will no longer be allowed to be a parent page, the movement will not take place.
So be aware of changing template settings later on - in this case you have to change the parent page manually on all of
your pages.

## Multi-language

This module will be shipped with the German translation file. You will need ProcessWire 3.0.195 or higher to import 
these translations. The reason is that the processCSV method has to be hookable and this is only possible since the 
3.0.195 version of ProcessWire.
This Hook method will take care that the newly created fields will also be translated after importing the language files.
If your version is lower, you have to add the translations for these fields manually.

## How to install

1. Download and place the module folder named "JkPublishPages" in:
/site/modules/

2. In the admin control panel, refresh all your modules.

3. Now scroll to the PublishingOptions module and click "Install". The required input fields and the fieldset will be 
created automatically.

4. After the module has been installed, go to the module configuration and choose the templates where you want to add 
the publishing fields by checking the appropriate checkboxes in the module configuration and click the save button.

## How to uninstall

Go to the module configuration and check checkbox for uninstallation. All fields of this module will be removed from 
every template and will be deleted from the database afterwards.
