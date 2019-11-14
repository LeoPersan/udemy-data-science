const mix = require('laravel-mix');

mix
    .setResourceRoot(process.env.MIX_URL)
    .js('resources/js/app.js', 'public/js')
    .sass('resources/sass/app.scss', 'public/css')
    .version()
    ;
