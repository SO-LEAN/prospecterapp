"use strict";

var $ = require('jquery');
var jsonp = require('./jsonp');

var gapiurl = "https://maps.googleapis.com/maps/api/js?key=" + $( "body" ).data( "googleApiKey") + "&callback=__googleMapsApiOnLoadCallback";

exports.load = function (done) {
    jsonp(gapiurl, '__googleMapsApiOnLoadCallback', done);
};
