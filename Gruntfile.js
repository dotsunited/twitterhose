/*global module:false*/
module.exports = function(grunt) {

  // Project configuration.
  grunt.initConfig({
    less: {
      main: {
        src: 'assets/less/main.less',
        dest: 'web/assets/css/main.css'
      }
    },
    uglify: {
      main: {
        src: 'assets/js/*.js',
        dest: 'web/assets/js/main.js'
      },
      plugins: {
        src: 'assets/js/plugins/*.js',
        dest: 'web/assets/js/plugins.js'
      }
    },
    watch: {
      style: {
        files: 'assets/less/**/*.less',
        tasks: 'less'
      },
      jsmain: {
        files: 'assets/js/*.js',
        tasks: 'min:main'
      },
      jsplugins: {
        files: 'assets/js/plugins/*.js',
        tasks: 'min:plugins'
      }
    }
  });

  grunt.loadNpmTasks('grunt-contrib-less');
  grunt.loadNpmTasks('grunt-contrib-uglify');

  // Default task.
  grunt.registerTask('default', ['less', 'uglify']);

};
