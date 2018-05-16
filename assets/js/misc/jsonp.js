"use strict";

module.exports = function (url, callbackname, done) {
    let script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = url;
    let s = document.getElementsByTagName('script')[0];
    s.parentNode.insertBefore(script, s);

    window[callbackname] = done;
};
