"use strict";

let $ = require('jquery');
let jsonp = require('./jsonp');

let $google = $('.google').first();
let gapiurl = "https://maps.googleapis.com/maps/api/js?key=" + $google.data( "googleApiKey") + "&callback=__googleMapsApiOnLoadCallback";

exports.load = function (done) {
  jsonp(gapiurl, '__googleMapsApiOnLoadCallback', done);
};
