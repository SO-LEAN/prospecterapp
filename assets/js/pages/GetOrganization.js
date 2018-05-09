"use strict";

var $ = require('jquery');
var gapi = require('../misc/gapi');

/**
 *
 * @constructor
 */
function GetOrganization() {
}
/**
 *
 */
GetOrganization.prototype.run = function() {
  var $body = $('body');

  this.latitude = $body.data('latitude');
  this.longitude = $body.data('longitude');
  this.address = $body.data('address');

  this.directionUrl = 'https://www.google.com/maps/dir/?api=1';

  this.initMap();
};

GetOrganization.prototype.initMap = function () {
  var that = this;
  gapi.load(function () {
    var organizationLocation = {lat: that.latitude, lng: that.longitude};
    var map = new google.maps.Map(document.getElementById('map'), {
      center: organizationLocation,
      scrollwheel: false,
      zoom: 17
    });
    var marker = new google.maps.Marker({
      position: organizationLocation
    });

    marker.setMap(map);

    marker.addListener('click', function() {
      window.open(that.directionUrl.concat('&destination=', encodeURIComponent(that.address)),'_blank');
    });
  });
};

module.exports = GetOrganization;
