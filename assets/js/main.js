"use strict";

// http://aerendir.me/2018/04/06/managin-static-images-webpack-encore/
require('../images');

var $ = require('jquery');
require('popper.js');
require('bootstrap');

// preload modules
var modules = {
    common: require('./common'),
    dashboard: require('./pages/Dashboard'),
    getOrganization: require('./pages/GetOrganization')

};

$(document).ready(function() {

    new modules['common']().run();

    var page = $('body').data('page');
    if('none' !== page && modules.hasOwnProperty(page) ){
        var Module = modules[page];
        new Module().run();
    }
});

