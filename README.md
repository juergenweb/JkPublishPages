# JkPublishPages - ProcessWire module to publish and unpublish pages depending on dates

This module is intended to schedule pages depending on publishing date and times.
You can determine when a page is published or unpublished again by setting start and end date.
After the publishing end date has been reached, you can decide what should happen with this page.

There are several options:

* No, action - The page is just set to unpublished
* Move to trash - The page will be moved directly to the trash after the publication end date is reached
* Delete permanently - The page will be deleted directly without moving it to the thrash before
* Move the page to a new parent - This could be useful if you want to move the page fe to an archive. In this case, the
page is only moved to a new position within the page tree, but remains published so that it can still be viewed

This module creates new input fields for entering dates and to select what should happen after the publishing end date
has been reached. These fields can be added with one click to templates. These fields do not have to be added manually
to each template.

## Module configuration
![alt text](https://raw.githubusercontent.com/juergenweb/JkPublishPages/main/images/configuration.png?v=1)

As you can see, you have a select input with all LazyCron time intervals - default is 1 hour.
Afterwards is a list of checkboxes. Each checkbox represents a frontend template where you can add the publishing fields.
If the checkbox of a certain template is checked, then the publishing fields will be added automatically to this template
after saving the module configuration.
On the opposite, the publishing fields will be removed from all templates where the checkbox is unchecked.
So there is no the need to add or remove the publishing fields manually to/from each template.

To enable/disable all checkboxes at once, you can use the toggle link (Un/check all) above the checkboxes. A little
JavaScript allows to toggle the checking/unchecking of all checkboxes at once. This makes it much easier and 
comfortable to add the fields to several templates.


## Template view

Every template which was selected to use the publishing options includes the following fields:

![alt text](https://raw.githubusercontent.com/juergenweb/JkPublishPages/main/images/default-page-fields.png)

If a publication end date has been set, an additional field will be displayed below. In this case, you can choose what to do after
the publication end date is reached.

![alt text](https://raw.githubusercontent.com/juergenweb/JkPublishPages/main/images/action.png)

If you have selected the action to move the page to a new parent page, another box will appear where you can select
the new parent page.

![alt text](https://raw.githubusercontent.com/juergenweb/JkPublishPages/main/images/move.png)


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

As written in the introduction, you can decide what should happen with this page after the publication end date is
reached.

Most options are self-explanatory, so I only want to focus on the option moving the page to a new parent and explain
this a little more in detail.

Explanation: 
You have some setting possibilities inside your template configuration to set which templates are allowed to be a parent
or which templates are allowed to be a child template. In addition, you can decide, whether a given template can have
children or not.
All these settings have an impact on the select drop-down input field. This means that only pages can be selected to be
a new parent page for the current page, that are allowed to be a parent page (depending on your settings).

In other words, the values of the select field displayed are dynamically and can differ from page to page (or template
to template). You do not have to take care of it - it all runs behind the scenes. 

But there is one thing to mention in this case: If you change your template settings later on, and you have chosen a
parent page which will no longer be allowed to be a parent page, the movement will not take place.
So be aware of changing template settings later on - in this case you have to check all your pages manually if the
change will affect the page or not.
But this is a very rare scenario, because the template settings will be done at the beginning and will not be changed
later on.

## Multi-language

This module will be shipped with the German translation file. You will need ProcessWire 3.0.195 or higher to import 
these translations to the created input fields too. The reason is that the processCSV method has to be hookable and this
is only possible since the 3.0.195 version of ProcessWire.
This Hook method will take care that the newly created fields will also be translated with one click after importing the
language files.
If your version is lower this module should also work, but you have to add the translations for these fields manually.

## How to install

1. Download and place the module folder named "JkPublishPages" in:
/site/modules/

2. In the admin control panel, refresh all your modules.

3. Find this module and click "Install". The module will be installed and the required input fields will be 
created automatically.

4. After the module has been installed, you can make your settings.

## How to uninstall

Go to the module configuration and check the checkbox for uninstallation. All fields of this module will be removed from 
every template and will be deleted from the database afterwards.
