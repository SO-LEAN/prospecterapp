'use strict';

var browserify   = require('browserify'),
    del          = require('del'),
    gulp         = require('gulp'),
    autoprefixer = require('gulp-autoprefixer'),
    cache        = require('gulp-cache'),
    cleanCSS     = require('gulp-clean-css'),
    concat       = require('gulp-concat'),
    imagemin     = require('gulp-imagemin'),
    plumber      = require('gulp-plumber'),
    sass         = require('gulp-sass'),
    sourcemaps   = require('gulp-sourcemaps'),
    util         = require('gulp-util'),
    uglify       = require('gulp-uglify'),
    watch        = require('gulp-watch'),
    merge        = require('merge2'),
    source       = require('vinyl-source-stream'),
    buffer       = require('vinyl-buffer');


var config = {
    base_source: 'app/Resources/assets',
    base_target: 'public/statics'
};

config.styles = {
    name: 'portail.css',
    dest: config.base_target + './css',
    source: config.base_source + '/sass/main.scss',
    watch: config.base_source + '/**/**/*.{scss,css}'
};

config.fonts = {
    source: config.base_source + '/fonts/*.{eot,svg,ttf,woff,woff2}',
    dest: config.base_target + '/fonts'
};

config.images = {
    source: [config.base_source + '/images/**/*'],
    dest: config.base_target + '/images'
};

config.scripts = {
    name: 'main.js',
    dest: config.base_target + '/js',
    source: config.base_source + '/js/main.js',
    watch: [config.base_source + '/js/*.js', config.base_source + '/js/pages/*.js']
};

config.vendors = {
    'css': [],
    'fonts': [
        'node_modules/font-awesome/fonts/*'
    ]
};

var onError = function (err) {
    util.log(util.colors.red(err.message));
    this.emit('end');
};

gulp.task('css', function () {
    return merge(
            gulp.src([config.styles.source])
                .pipe(plumber({ errorHandler: onError }))
                .pipe(sass())
                .pipe(autoprefixer({browsers: [
                    'Chrome >= 35', // Exact version number here is kinda arbitrary
                    'Firefox >= 38', // Current Firefox Extended Support Release (ESR); https://www.mozilla.org/en-US/firefox/organizations/faq/
                    'Edge >= 12',
                    'Explorer >= 9',
                    'iOS >= 8',
                    'Safari >= 8',
                    'Android 2.3',
                    'Android >= 4',
                    'Opera >= 12'
                ]})),
            gulp.src(config.vendors.css)
                .pipe(concat('vendors.css'))
    )
        .pipe(concat(config.styles.name))
        .pipe(cleanCSS({compatibility: 'ie9'}))
        .pipe(gulp.dest(config.base_target + '/css'));
});

gulp.task('fonts', function () {
    return gulp.src([config.fonts.source].concat(config.vendors.fonts))
        .pipe(gulp.dest(config.fonts.dest));
});

gulp.task('images', function () {
    return gulp.src(config.images.source)
        .pipe(
            cache(
                imagemin({
                    progressive: true,
                    interlaced: true,
                    svgoPlugins: [{cleanupIDs: false}]
                })
            )
        )
        .pipe(gulp.dest(config.images.dest))
});

gulp.task('scripts', function () {
    return browserify(config.scripts.source).bundle()
        .pipe(source(config.scripts.name))
        .pipe(buffer())
        .pipe(uglify())
        .pipe(gulp.dest(config.scripts.dest));
});

gulp.task('watch', function () {
    gulp.watch(config.styles.watch, ['css']);
    gulp.watch(config.fonts.source, ['fonts']);
    gulp.watch(config.images.source, ['images']);
    gulp.watch(config.scripts.watch, ['scripts']);
});


gulp.task('clean', function (callback) {
    del([config.base_target], callback);
});

gulp.task('default', ['css', 'fonts', 'images', 'scripts']);
gulp.task('dev', ['watch']);
