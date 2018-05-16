"use strict";

let $ = require('jquery');
let gapi = require('../misc/gapi');

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
  let $body = $('body');

  this.latitude = $body.data('latitude');
  this.longitude = $body.data('longitude');
  this.address = $body.data('address');

  this.directionUrl = 'https://www.google.com/maps/dir/?api=1';

  this.initMap();
};

GetOrganization.prototype.initMap = function () {
  if (undefined === gapi) {
    return;
  }

  let that = this;
  gapi.load(function () {
    let organizationLocation = {lat: that.latitude, lng: that.longitude};
    let map = new google.maps.Map(document.getElementById('map'), {
      center: organizationLocation,
      scrollwheel: false,
      zoom: 17
    });
    let marker = new google.maps.Marker({
      position: organizationLocation
    });

    marker.setMap(map);

    marker.addListener('click', function() {
      window.open(that.directionUrl.concat('&destination=', encodeURIComponent(that.address)),'_blank');
    });
  });
};

module.exports = GetOrganization;
