Here's the information you'll need to localize the plugin into another language.

## Using Launchpad (Updated Sept 24) ##

A Launchpad translation project has been setup: [Click here to view a list of all currently available translations](https://translations.launchpad.net/tantan-flickr/trunk/+pots/tantan-flickr). Very briefly, Launchpad will enable a community of translators to help translate the plugin into whatever language is needed.

## How to translate with Launchpad ##

  1. Setup a [Launchpad.net account](https://translations.launchpad.net/tantan-flickr/trunk/+pots/tantan-flickr/de/+login).
  1. Select your [preferred languages](https://translations.launchpad.net/+editmylanguages). This will be the language you want to translate.
  1. You should see your language [show up in this list](https://translations.launchpad.net/tantan-flickr/trunk/+pots/tantan-flickr). Simply click the name to get started with the translations. The system will try to offer suggests where available (retrieved from other open source projects).
  1. Your translations are saved as you proceed. When it comes time to package a release, your translation will be automatically included in the release for everyone to use. Thanks for your help!



---



## Special cases ##

There are certain special cases where variable codes are used in the text. Here's a brief explanation of the codes used by the plugin.

**A single variable code**

`msgid "View all %d albums &gt;"`

What this means is that the plugin will substitute the code `%d` with a number. The `&gt;` (note the semicolon at the end) is an HTML character, and will get translated into `>` by the browser. You should make sure your translated strings use these `%` and `&` codes appropriately and also any HTML tags.

**Multiple variable codes**

`msgid "%1$d - %2$d of %3$d Photos"`

Sometimes text will contain more than one variable. In this case, the plugin will pass in 3 parameters in this order: a start position (number), a stop position (number), and a total (number). The `%1$d`, `%2$d`, and `%3$d` refer to these parameters and the order they were passed in. In technical terms, %1$d will always refer to the first parameter (`%1`), and always contain a number (`$d`). You are free to reverse and/or change the order of the parameters used as necessary. Eg, you can do this:

`msgid "%3$d total photos. %1$d - %2$d"`

**Dates**

`msgid "F j, Y"`

The plugin uses PHP's date() function to format dates. The above characters will get translated into something like "September 19, 2008". A full list of date codes is available here: http://us3.php.net/date

And more info here:

http://codex.wordpress.org/Translating_WordPress#Date_Formatting_Strings



---


# Manual translation #

If you _don't_ want to setup a Launchpad account, please download following the language file. These instructions are roughly based on the [WordPress documentation available here](http://codex.wordpress.org/Translating_WordPress). Once you are done with a localization, please email the translated .po file to joetan54@gmail.com.

http://photo-album.googlecode.com/svn/branches/tantan-flickr-1.1/tantan-flickr/languages/tantan-flickr.po

This is a text file and contains all the text (in plain English) used by the plugin.

## Translate into (your language) ##

When you open this file in Notepad (or other text editor), you see a bunch of entries similar to this:

```
#: flickr/class-admin.php:316
msgid "Photo Stream"
msgstr ""
```

The first line (`#:`) tells you where in the code the text appears. The second line (msgid) is the English text used. The third line (`msgstr`) is where your translation of the second line would go. So for example, if you were translating into Spanish, you might edit the third (`msgstr`) line such that this entry will look something like this:

```
#: flickr/class-admin.php:316
msgid "Photo Stream"
msgstr "Foto Stream"
```


## Helpful Tools ##

While you can edit the text file in Notepad, you may want to consider using poEdit to help you translate faster. This utility is available here: http://www.poedit.net/

Information about using poEdit is here:
http://codex.wordpress.org/Translating_WordPress#Translating_With_poEdit

## Examples ##

Here are some example translations provided for the main WordPress application.

  * [Bangla - Bengali (bn\_BD)](http://svn.automattic.com/wordpress-i18n/bn_BD/trunk/messages/bn.po)
  * [Hong Kong (香港) (zh\_HK)](http://svn.automattic.com/wordpress-i18n/zh_HK/trunk/messages/zh_HK.po)
  * [Greek](http://svn.automattic.com/wordpress-i18n/el/trunk/messages/el.po)