"use strict";

let gapi = require('../misc/gapi');

function Dashboard() {}

Dashboard.prototype.run = function() {
  if ("geolocation" in navigator) {
    navigator.geolocation.getCurrentPosition(function (position) {
      gapi.load(function () {
        let me = {lat: position.coords.latitude, lng: position.coords.longitude};
        new google.maps.Map(document.getElementById('map'), {
          center: me,
          scrollwheel: false,
          zoom: 5
        });
      });
    }, function(err){console.log(err);});
  }
};

module.exports = Dashboard;
