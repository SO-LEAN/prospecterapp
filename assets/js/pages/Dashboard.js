"use strict";

let $ = require('jquery');
let gapi = require('../misc/gapi');

function Dashboard() {
  this.$map = $('#map');
  this.organizations = this.$map.data('organizations');

  this.loadMap = function (lat, lng) {
    let that = this;
    gapi.load(function () {
      let map = new google.maps.Map(that.$map[0], {
        center: {lat: lat, lng: lng},
        scrollwheel: false,
        zoom: 5
      });
      for (let i in that.organizations) {
        that.appendOrganization(map, that.organizations[i]);
      }
    });
  };

  this.appendOrganization = function (map, organization) {
    if(!organization.hasOwnProperty('coordinates')) {
      return;
    }

    let location = {lat: organization.coordinates.latitude, lng: organization.coordinates.longitude};
    console.log(map);
    let marker = new google.maps.Marker({
      position: location,
      title: organization.fullName,
      icon: organization.logo,
      map: map
    });

    marker.addListener('click', function () {
      location.href = organization.link;
    });
  };
}

Dashboard.prototype.run = function () {
  let that = this;
  if ('geolocation' in navigator) {
    navigator.geolocation.getCurrentPosition(function (position) {
      that.loadMap(position.coords.latitude, position.coords.longitude);
    });
  } else {
    this.loadMap(48.866667, 2.333333);
  }
};

module.exports = Dashboard;
