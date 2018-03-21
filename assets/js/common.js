"use strict";

var $ = require('jquery');

function Common() {
  var that = this;

  this.$_body = $('body');
  this.$_navBar = $('.navbar-main');
  this.$_sidebarSticky = $('.sidebar-sticky', this.$_sidebar);

  this.navBarHeight = this.$_navBar.height();

  this.initMainNavBarScrolling = function () {
    $(function () {
      $(window).scroll(function () {

        if ($(this).scrollTop() > that.navBarHeight) {
          that.$_body.addClass('body-main_navbar-offset');
          that.$_navBar.addClass('fixed-top');
          that.$_sidebarSticky.addClass('active');
        } else {
          that.$_body.removeClass('body-main_navbar-offset');
          that.$_navBar.removeClass('fixed-top');
          that.$_sidebarSticky.removeClass('active');
        }
      });
    });
  };
}

Common.prototype.run = function () {
  this.initMainNavBarScrolling();
};

module.exports = Common;
