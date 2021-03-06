"use strict";

var $ = require('jquery');
require('bootstrap');
// preload modules
var modules = {
    common: require('./common'),
    dashboard: require('./pages/Dashboard')
};

$(document).ready(function() {

    new modules['common']().run();

    var page = $('body').data('page');
    if('none' !== page ){
        var Module =  modules[page];
        new Module().run();
    }
});

