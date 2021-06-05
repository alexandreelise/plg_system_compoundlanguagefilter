# System - Compound Language Filter
## Joomla! System Plugin

This plugin is based on the core languagefilter plugin and adds the feature of using multiple sef language tag 
in the url associated with the same language internally.

--------------------------------------------------------------------------------------


HOW IT WORKS:
This plugin is an enhancement of the language filter plugin. It enables the possibility to have
one to many relationships from one source language to any other "virtual languages". I call them virtual languages
because they are actually not installed in Joomla!. What I did to achieve this is to leverage the power of the Joomla!
subforms and use part of the com_languages form from the admin area and add to it what I called the "source_language".
The source_language is a standard form field from Joomla! which list the installed languages of the "Frontend" 
part of Joomla!. 

The website I built to reproduce the use case is a multilingual website with 4 languages French, English,
Spanish and German.

In order to use the name of the country rather than the language name we can simply change the title of the 
language and/or the native title accordingly. Moreover, I added the possibilty to choose the "virtual languages" and the
installed languages from the mod_languages module which Helper class has been dynamically replaced by another one by my
system plugin so as to have this custom functionnality.

By merging the subform results and the installed languages result using Joomla! Registry . It allows a powerful tool to
address this use case.

With all this you are now able to do as you describe.

NOTE: This implementation also changes the website to be multilingual for either "virtual languages" and
installed languages.

Just one side note:
The url of type /ar changes the active menu item to Spain and the content is in Spanish 
but the menu item doesn't stay on Argentina 
(I think you can get around that but I didn't have much time to answer your question)



--------------------------------------------------------------------------------------


INSTRUCTIONS:

This Joomla! plugin source code is in the src/ directory of this zip file
The final extension is in the build/ directory 
If you want to build the extension yourself, make sure you have make utility on your machine.
It is generally provided by default on linux and macOS machines maybe not on Windows.
Once you have make installed.
You can type in your terminal: (you must be in the directory on the Makefile)

```

make gen

```

The Makefile rule will check if the php files has no syntax errors and create a zip file tagged by the current datetime
in the build/ directory.

If you don't want to bother doing this. Just install the already built extension from the build/ directory

The Akeeba backup of the reproductible dev environment is in the backup/ directory.

--------------------------------------------
## INFOS

> English: [Click here to get in touch](https://github.com/mralexandrelise/mralexandrelise/blob/master/community.md "Get in touch")

> Français: [Cliquez ici pour me contacter](https://github.com/mralexandrelise/mralexandrelise/blob/master/community.md "Me contacter")
