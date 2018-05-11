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
    prospect: require('./pages/Prospect'),
    addOrganization: require('./pages/AddOrganization'),
    getOrganization: require('./pages/GetOrganization'),
    findOrganization: require('./pages/FindOrganization')
};

$(document).ready(function() {

    new modules['common']().run();

    var page = $('body').data('page');
    if('none' !== page ){
        var Module =  modules[page];
        new Module().run();
    }
});

