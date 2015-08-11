/* jshint node:true */
module.exports = function( grunt ) {
	'use strict';

	grunt.initConfig({
		// Getting project data
		pkg: grunt.file.readJSON('package.json'),

		// Generate POT files.
		makepot: {
			options: {
				type: 'wp-plugin',
				domainPath: 'languages',
				potHeaders: {
					'report-msgid-bugs-to': 'https://github.com/SiR-DanieL/woocommerce-custom-thankyou/issues',
					'language-team': 'LANGUAGE <EMAIL@ADDRESS>'
				}
			},
			plugin: {
				options: {
					potFilename: 'woocommerce-custom-thankyou.pot'
				}
			}
		},

		// Check textdomain errors.
		checktextdomain: {
			options:{
				text_domain: 'woocommerce-custom-thankyou',
				keywords: [
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
				src:  [
					'**/*.php',         // Include all files
					'!node_modules/**'  // Exclude node_modules/
				],
				expand: true
			}
		},

		clean: {
			main: ['deploy/<%= pkg.version %>']
		},

		copy: {
			// Copy the plugin to a versioned deploy directory
			main: {
				src:  [
					'**',
					'!node_modules/**',
					'!deploy/**',
					'!.git/**',
					'!Gruntfile.js',
					'!package.json',
					'!.gitignore',
					'!.editorconfig'
				],
				dest: 'deploy/<%= pkg.version %>/'
			}
		},

		compress: {
			main: {
				options: {
					mode: 'zip',
					archive: './deploy/<%= pkg.name %>.zip'
				},
				expand: true,
				cwd: 'deploy/<%= pkg.version %>/',
				src: ['**/*']
			}
		}
	});

	// Load NPM tasks to be used here
	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.loadNpmTasks( 'grunt-checktextdomain' );
	grunt.loadNpmTasks( 'grunt-contrib-copy' );
	grunt.loadNpmTasks( 'grunt-contrib-clean' );
	grunt.loadNpmTasks( 'grunt-contrib-compress' );

	grunt.registerTask( 'i18n', [
		'checktextdomain',
		'makepot'
	]);

	grunt.registerTask( 'deploy', [
		'clean',
		'copy',
		'compress'
	]);
};
