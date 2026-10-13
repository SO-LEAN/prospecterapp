import $ from 'jquery';
import jsonp from './jsonp.js';

let $google = $('.google').first();
let gapiurl = "https://maps.googleapis.com/maps/api/js?key=" + $google.data( "googleApiKey") + "&callback=__googleMapsApiOnLoadCallback";

export function load(done) {
  jsonp(gapiurl, '__googleMapsApiOnLoadCallback', done);
}
