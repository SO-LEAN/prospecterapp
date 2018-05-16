"use strict";

// http://aerendir.me/2018/04/06/managin-static-images-webpack-encore/
require('../images');

let $ = require('jquery');
require('popper.js');
require('bootstrap');

// preload modules
let modules = {
    common: require('./common'),
    dashboard: require('./pages/Dashboard'),
    getOrganization: require('./pages/GetOrganization')

};

$(document).ready(function() {

    new modules['common']().run();

    let page = $('body').data('page');
    if('none' !== page && modules.hasOwnProperty(page) ){
        let Module = modules[page];
        new Module().run();
    }
});

