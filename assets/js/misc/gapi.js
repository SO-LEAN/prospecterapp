"use strict";

var jsonp = require('./jsonp');

var gapiurl = 'https://maps.googleapis.com/maps/api/js?key=AIzaSyBe0uDyMZW6s0_7c6Fp6D1tPHucu0LoxlI&callback=__googleMapsApiOnLoadCallback';

exports.load = function (done) {
    jsonp(gapiurl, '__googleMapsApiOnLoadCallback', done);
};
