"use strict";

var $ = require('jquery');

/**
 *
 * @constructor
 */
function Common() {
    var that = this;

    this.$_body = $('body');
    this.$_navBar = $('.navbar-main');

    this.navBarHeight = this.$_navBar.height();

    this.initMainNavBarScrolling = function (){
        $(function () {
            $(window).scroll(function () {

                if ($(this).scrollTop() > that.navBarHeight) {
                    that.$_body.addClass('body-main_navbar-offset');
                    that.$_navBar.addClass('navbar-fixed-top')
                } else {
                    that.$_body.removeClass('body-main_navbar-offset');
                    that.$_navBar.removeClass('navbar-fixed-top');
                }
            });
        });
    };
}

/**
 *
 */
Common.prototype.run = function() {
   this.initMainNavBarScrolling();
};

module.exports = Common;
