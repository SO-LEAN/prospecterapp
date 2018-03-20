"use strict";

var $ = require('jquery');
var gapi = require('../misc/gapi');

/**
 *
 * @constructor
 */
function Contact() {
}
/**
 *
 */
Contact.prototype.run = function() {
    this.initMap();
};

Contact.prototype.initMap = function () {
    gapi.load(function () {
        var map = new google.maps.Map(document.getElementById('map'), {
            center: {lat: 48.555, lng: 7.7640},
            scrollwheel: false,
            zoom: 17
        });
    });

};

module.exports = Contact;
