"use strict";

let $ = require('jquery');
let jsonp = require('./jsonp');

let $body = $('body');
let gapiurl = "https://maps.googleapis.com/maps/api/js?key=" + $body.data( "googleApiKey") + "&callback=__googleMapsApiOnLoadCallback";

exports.load = function (done) {
  jsonp(gapiurl, '__googleMapsApiOnLoadCallback', done);
};
