## Laravel Translation Manager

This is a package to manage Laravel translation files.
It does not replace the Translation system, only import/export the php files to a database and make them editable through a webinterface.
The workflow would be:

    - Import translations: Read all translation files and save them in the database
    - Find all translations in php/twig sources
    - Optionally: Listen to missing translation with the custom Translator
    - Translate all keys through the webinterface
    - Export: Write all translations back to the translation files.

This way, translations can be saved in git history and no overhead is introduced in production.

![Screenshot](http://i.imgur.com/4th2krf.png)

## Installation

Require this package in your composer.json and run composer update (or run `composer require barryvdh/laravel-translation-manager:*` directly):

    "barryvdh/laravel-translation-manager": "0.1.x"

After updating composer, add the ServiceProvider to the providers array in app/config/app.php

    'Barryvdh\TranslationManager\ManagerServiceProvider',

You need to run the migrations for this package

    $ php artisan migrate --package="barryvdh/laravel-translation-manager"

You need to publish the config file for this package. This will add the file `app/config/packages/barryvdh/laravel-translation-manager/config.php`, where you can configure this package.

    $ php artisan config:publish barryvdh/laravel-translation-manager

You have to add the Controller to your routes.php, so you can set your own url/filters.

    Route::group(array('before' => 'auth_admin'), function() {
        Route::controller('translations', 'Barryvdh\TranslationManager\Controller');
    });

This example will make the translation manager available at `http://yourdomain.com/translations`

## Usage

### Web interface

When you have imported your translation (via buttons or command), you can view them in the webinterface (on the url you defined the with the controller).
You can click on a translation and an edit field will popup. Just click save and it is saved :)
When a translation is not yet created in a different locale, you can also just edit it to create it.

Using the buttons on the webinterface, you can import/export the translations. For publishing translations, make sure your application can write to the language directory.

You can also use the commands below.

### Import command

The import command will search through app/lang and load all strings in the database, so you can easily manage them.

    $ php artisan translations:import
    
Note: By default, only new strings are added. Translations already in the DB are kept the same. If you want to replace all values with the ones from the files, 
add the `--replace` (or `-R`) option: `php artisan translations:import --replace`

### Find translations in source

The Find command/button will look search for all php/twig files in the app directory, to see if they contain translation functions, and will try to extract the group/item names.
The found keys will be added to the database, so they can be easily translated.
This can be done through the webinterface, or via an Artisan command.

    $ php artisan translations:find

### Export command

The export command will write the contents of the database back to app/lang php files.
This will overwrite existing translations and remove all comments, so make sure to backup your data before using.
Supply the group name to define which groups you want to publish.

    $ php artisan translations:export <group>

For example, `php artisan translations:export reminders` when you have 2 locales (en/nl), will write to `app/lang/en/reminders.php` and `app/lang/nl/reminders.php`

### Clean command

The clean command will search for all translation that are NULL and delete them, so your interface is a bit cleaner. Note: empty translations are never exported.

    $ php artisan translations:clean

### Reset command

The reset command simply clears all translation in the database, so you can start fresh (by a new import). Make sure to export your work if needed before doing this.

    $ php artisan translations:reset



### Detect missing translations

Most translations can be found by using the Find command (see above), but in case you have dynamic keys (variables/automatic forms etc), it can be helpful to 'listen' to the missing translations.
To detect missing translations, we can swap the Laravel TranslationServicepProvider with a custom provider.
In your config/app.php, comment out the original TranslationServiceProvider and add the one from this package:

    //'Illuminate\Translation\TranslationServiceProvider',
    'Barryvdh\TranslationManager\TranslationServiceProvider',

This will extend the Translator and will create a new database entry, whenever a key is not found, so you have to visit the pages that use them.
This way it shows up in the webinterface and can be edited and later exported.
You shouldn't use this in production, just in production to translate your views, then just switch back.

## TODO

This package is still very alpha. Few thinks that are on the todo-list:

    - Add locales/groups via webinterface
    - Improve webinterface (more selection/filtering, behavior of popup after save etc)
    - Seed existing languages (https://github.com/caouecs/Laravel4-lang)
    - Suggestions are welcome :)
