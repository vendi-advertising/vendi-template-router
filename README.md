README
======

The goal of this plugin was to better override WordPress's routing engine in a more explicit and controlled manner. There are times when you want the full power of WordPress's CMS backend but you want to take complete control of the rendering. Usually this scenario pops up most when you are developing an *application* instead of just a *website*.

At the time that this plugin was created the REST API was just entering beta and the work on the rewrite engine wasn't spoken of yet. (Hopefully that's coming soon, though!)

The general use-case for this plugin is for URL "folders" that you want to redirect to your own template folder based on name. For instance, if you want everything at `https://www.example.com/app/` to go to your own application folder (which doesn't even need to live in the web root).

This plugin can be installed as a normal plugin however it is recommended that you actually install this as an MU plugin.

To use this plugin you just need to register your route:

    \Vendi\Shared\template_router::register_context(
                                                    <context_name>,
                                                    <url_folder>,
                                                    <template_location_root>,
                                                    <magic_page = 'page'>,
                                                    <template_subfolder = 'templates'>
                                                );

 - &lt;context_name&gt;
    - Arbitrary string name that represents URLs relative to &lt;url_folder&gt;.
    - If you want to ask the `template_router` to generate URLs later you'll need this.
 - &lt;url_folder&gt;
    - Arbitrary valid URL string that represents the "folder" that your application is served from.
 - &lt;template_location_root&gt;
    - The absolute path that requests to &lt;url_folder&gt; will be passed to.
 - &lt;magic_page&gt;
    - When passing the requested route to your template this query string variable will be used.
    - Make sure that your app does not depend on this value.
    - This value is optional and defaults to "page".
    - This parameter is poorly named and really should be "internal_query_string_key" or something.
 - &lt;template_subfolder&gt;
    - The subfolder relative to &lt;template_location_root&gt; that holds the templates for this route.
    - This value is optional and defaults to "templates"

# Example
For a general application called `Test` that you want to have all routes start with `test` and with the WordPress install at  `/var/www/wordpress-root-folder/` and with templates living in `/var/www/wordpress-root-folder/templates/` you would register your context using:

    \Vendi\Shared\template_router::register_context(
                                                    'Test',
                                                    'test',
                                                    '/var/www/wordpress-root-folder/'
                                                );
