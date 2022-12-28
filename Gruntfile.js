/*global require*/

/**
 * When grunt command does not execute try these steps:
 *
 * - delete folder 'node_modules' and run command in console:
 *   $ npm install
 *
 * - Run test-command in console, to find syntax errors in script:
 *   $ grunt hello
 */

module.exports = function( grunt ) {

    // Load all grunt tasks.
    require( 'load-grunt-tasks' )(grunt);

    var buildtime = new Date().toISOString();
    var buildyear = 1900 + new Date().getYear();

    var conf = {
        js_files_concat: {
        },

        css_files_compile: {
        },

        plugin_dir: '',
        plugin_file: 'omnibus.php',

        // Regex patterns to exclude from transation.
        translation: {
            ignore_files: [
                '.git*',
                'node_modules/.*',
                '(^.php)',		 // Ignore non-php files.
                'release/.*',	  // Temp release files.
                '.sass-cache/.*',
                'tests/.*',		// Unit testing.
            ],
            pot_dir: 'languages/', // With trailing slash.
            textdomain: 'omnibus',
        }
    };


    // Project configuration
    grunt.initConfig( {
        pkg: grunt.file.readJSON( 'package.json' ),

        concat: {
            options: {
                stripBanners: true,
                banner: '/*! <%= pkg.title %> - v<%= pkg.version %>\n' +
                ' * <%= pkg.homepage %>\n' +
                ' * Copyright (c) <%= grunt.template.today("yyyy") %>;' +
                ' * Licensed GPLv2+' +
                ' */\n'
            },
            scripts: {
                files: conf.js_files_concat
            }
        },

        jshint: {
            all: [
                'Gruntfile.js',
                'assets/js/src/**/*.js',
                'assets/js/test/**/*.js'
            ],
            options: {
                curly:   true,
                eqeqeq:  true,
                immed:   true,
                latedef: true,
                newcap:  true,
                noarg:   true,
                sub:	 true,
                undef:   true,
                boss:	true,
                eqnull:  true,
                globals: {
                    exports: true,
                    module:  false
                }
            }
        },

        uglify: {
            all: {
                files: [{
                    expand: true,
                    src: ['*.js', '!*.min.js', '!shared*' ],
                    cwd: 'assets/js/',
                    dest: 'assets/js/',
                    ext: '.min.js',
                    extDot: 'last'
                }],
                options: {
                    banner: '/*! <%= pkg.title %> - v<%= pkg.version %>\n' +
                    ' * <%= pkg.homepage %>\n' +
                    ' * Copyright (c) <%= grunt.template.today("yyyy") %>;' +
                    ' * Licensed GPLv2+' +
                    ' */\n',
                    mangle: {
                        reserved: ['jQuery']
                    }
                }
            }
        },

        test:   {
            files: ['assets/js/test/**/*.js']
        },

        phpunit: {
            classes: {
                dir: ''
            },
            options: {
                bin: 'phpunit',
                bootstrap: 'tests/php/bootstrap.php',
                testsuite: 'default',
                configuration: 'tests/php/phpunit.xml',
                colors: true,
                tap: true,
                staticBackup: false,
                noGlobalsBackup: false
            }
        },

        sass:   {
            all: {
                options: {
                    'sourcemap=none': true, // 'sourcemap': 'none' does not work...
                    unixNewlines: true,
                    style: 'expanded'
                },
                files: conf.css_files_compile
            }
        },

        cssmin: {
            options: {
                banner: '/*! <%= pkg.title %> - v<%= pkg.version %>\n' +
                ' * <%= pkg.homepage %>\n' +
                ' * Copyright (c) <%= grunt.template.today("yyyy") %>;' +
                ' * Licensed GPLv2+' +
                ' */\n',
                mergeIntoShorthands: false
            },
            target: {
                sourceMap: true,
                expand: true,
                files: {
                    // 'assets/css/ultimate-branding-admin.min.css': [
                    // 'assets/css/admin/*.css'
                    // ]
                },
            },
        },

        watch:  {
            sass: {
                files: [
                    'assets/sass/**/*.scss',
                    'inc/modules/**/*.scss'
                ],
                tasks: ['sass', 'cssmin'],
                options: {
                    debounceDelay: 500
                }
            },
            scripts: {
                files: ['assets/js/src/**/*.js', 'assets/js/vendor/**/*.js'],
                tasks: ['jshint', 'concat', 'uglify'],
                options: {
                    debounceDelay: 500
                }
            }
        },

        clean: {
            main: {
                src: ['release/<%= pkg.version %>']
            },
            temp: {
                src: ['**/*.tmp', '**/.afpDeleted*', '**/.DS_Store'],
                dot: true,
                filter: 'isFile'
            }
        },

        // BUILD - update the translation index .po file.
        makepot: {
            target: {
                options: {
                    domainPath: conf.translation.pot_dir,
                    exclude: conf.translation.ignore_files,
                    mainFile: conf.plugin_file,
                    potFilename: conf.translation.textdomain + '.pot',
                    potHeaders: {
                        poedit: true, // Includes common Poedit headers.
                        'x-poedit-keywordslist': true // Include a list of all possible gettext functions.
                    },
                    type: 'wp-plugin',
                    updateTimestamp: true,
                    updatePoFiles: true
                }
            }
        },

        potomo: {
            dist: {
                options: {
                    poDel: false
                },
                files: [{
                    expand: true,
                    cwd: conf.translation.pot_dir,
                    src: ['*.po'],
                    dest: conf.translation.pot_dir,
                    ext: '.mo',
                    nonull: true
                }]
            }
        },

        copy: {
            // Copy the plugin to a versioned release directory
            main: {
                src:  [
                    '**',
                    '!.git/**',
                    '!.git*',
                    '!assets/sass/**',
                    '!assets/scss/**',
                    '!node_modules/**',
                    '!package-lock.json',
                    '!postcss.config.js',
                    '!README.md',
                    '!LICENSE',
                    '!**/README.md',
                    '!**/*.map',
                    '!release/**',
                    '!.sass-cache/**',
                    '!webpack.config.js',
                    '!**/bitbucket-pipelines.yml',
                    '!**/css/less/**',
                    '!**/css/sass/**',
                    '!**/css/src/**',
                    '!**/Gruntfile.js',
                    '!**/img/src/**',
                    '!**/js/src/**',
                    '!**/package.json',
                    '!**/tests/**'
                ],
                dest: 'release/<%= pkg.version %>/<%= pkg.name %>/'
            }
        },

        // BUILD: Replace conditional tags in code.
        replace: {
            options: {
                patterns: [
                    { match: /AUTHOR_NAME/g, replace: '<%= pkg.author[0].name %>' },
                    { match: /AUTHOR_URI/g, replace: '<%= pkg.author[0].uri %>' },
                    { match: /BUILDTIME/g, replace: buildtime },
                    { match: /IWORKS_RATE_TEXTDOMAIN/g, replace: '<%= pkg.name %>' },
                    { match: /IWORKS_OPTIONS_TEXTDOMAIN/g, replace: '<%= pkg.name %>' },
                    { match: /PLUGIN_DESCRIPTION/g, replace: '<%= pkg.description %>' },
                    { match: /PLUGIN_NAME/g, replace: '<%= pkg.name %>' },
                    { match: /PLUGIN_REQUIRES_PHP/g, replace: '<%= pkg.requires.PHP %>' },
                    { match: /PLUGIN_REQUIRES_WORDPRESS/g, replace: '<%= pkg.requires.WordPress %>' },
                    { match: /PLUGIN_TAGLINE/g, replace: '<%= pkg.tagline %>' },
                    { match: /PLUGIN_TILL_YEAR/g, replace: buildyear },
                    { match: /PLUGIN_TITLE/g, replace: '<%= pkg.title %>' },
                    { match: /PLUGIN_URI/g, replace: '<%= pkg.homepage %>' },
                    { match: /PLUGIN_VERSION/g, replace: '<%= pkg.version %>' },
                    { match: /^Version: .+$/g, replace: 'Version: <%= pkg.version %>' },
                ]
            },
            files: {
                expand: true,
                src: [
                    'release/**',
                    '!release/**/images/**'
                ],
                dest: '.'
            }
        },

        compress: {
            main: {
                options: {
                    mode: 'zip',
                    archive: './release/<%= pkg.name %>-<%= pkg.version %>.zip'
                },
                expand: true,
                cwd: 'release/<%= pkg.version %>/',
                src: ['**/*'],
                dest: conf.plugin_dir
            }
        },

        checktextdomain: {
            options: {
                text_domain: [ 'og', 'IWORKS_RATE_TEXTDOMAIN' ],
                keywords: [ //List keyword specifications
                    '__:1,2d',
                    '_e:1,2d',
                    '_x:1,2c,3d',
                    'esc_html__:1,2d',
                    'esc_html_e:1,2d',
                    'esc_html_x:1,2c,3d',
                    'esc_attr__:1,2d', 
                    'esc_attr_e:1,2d', 
                    'esc_attr_x:1,2c,3d', 
                    '_ex:1,2c,3d',
                    '_n:1,2,4d', 
                    '_nx:1,2,4c,5d',
                    '_n_noop:1,2,3d',
                    '_nx_noop:1,2,3c,4d'
                ]
            },
            files: {
                src: ['og.php', 'vendor/**/*.php' ], //all php 
                expand: true,
            },
        },

    } );

    grunt.registerTask( 'notes', 'Show release notes', function() {
        grunt.log.subhead( 'Release notes' );
        grunt.log.writeln( '  1. Check BITBUCKET for pull-requests' );
        grunt.log.writeln( '  2. Check ASANA for high-priority bugs' );
        grunt.log.writeln( '  3. Check EMAILS for high-priority bugs' );
        grunt.log.writeln( '  4. Check FORUM for open threads' );
        grunt.log.writeln( '  5. REPLY to forum threads + unsubscribe' );
        grunt.log.writeln( '  6. Update the TRANSLATION files' );
        grunt.log.writeln( '  7. Generate ARCHIVE' );
        grunt.log.writeln( '  8. Check ARCHIVE structure - it should be a folder with plugin name' );
        grunt.log.writeln( '  9. INSTALL on a clean WordPress installation' );
        grunt.log.writeln( ' 10. RELEASE the plugin!' );
    });

    // Default task.

    grunt.registerTask( 'default', ['clean:temp', 'concat', 'uglify', 'sass', 'cssmin' ] );
    grunt.registerTask( 'js', [ 'concat', 'uglify' ] );
    grunt.registerTask( 'css', [ 'sass', 'cssmin' ] );
    grunt.registerTask( 'i18n', [ 'checktextdomain', 'makepot', 'potomo' ] );

    // grunt.registerTask( 'build', [ 'default', 'i18n', 'clean', 'copy', 'compress', 'notes'] );
    grunt.registerTask( 'build', [ 'default', 'clean', 'copy', 'replace', 'compress', 'notes'] );
    grunt.registerTask( 'test', ['phpunit', 'jshint', 'notes'] );

    grunt.util.linefeed = '\n';
};
