const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */


//复制资源
mix.copy('resources/assets/statics/public',     'public');

//生成vue页面js
mix.js('resources/assets/js/main/app.js',       'js/app.js');

//生成css样式文件
mix.sass('resources/assets/sass/app.scss',      'css');

//配置webpack
mix.webpackConfig({
    output: {
        hashDigestLength: 32,
        chunkFilename: 'js/build/[name].js?[hash]',
    },
    devServer:{
        disableHostCheck: true,
    }
});
