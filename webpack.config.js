const Encore = require('@symfony/webpack-encore');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // public path used by the web server to access the output path
    .setPublicPath('/build')

    // Entry point
    .addEntry('app', './assets/app.js')

    // Enable single runtime chunk
    .enableSingleRuntimeChunk()

    // Clean output folder before each build
    .cleanupOutputBeforeBuild()

    // Enable source maps during development
    .enableSourceMaps(!Encore.isProduction())

    // Disable versioning for stable filenames (IServ compatibility)
    .enableVersioning(false)

    // Configure Babel (optional)
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.23';
    })

    // Enable PostCSS loader for Tailwind CSS
    .enablePostCssLoader((options) => {
        options.postcssOptions = {
            plugins: [
                require('tailwindcss'),
                require('autoprefixer'),
            ]
        };
    })
;

module.exports = Encore.getWebpackConfig();
