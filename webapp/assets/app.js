import $ from 'jquery';
import 'bootstrap';
import './styles/app.scss';

import Common from './js/common.js';
import Dashboard from './js/pages/Dashboard.js';
import GetOrganization from './js/pages/GetOrganization.js';

const modules = {
    dashboard: Dashboard,
    getOrganization: GetOrganization,
};

$(() => {
    new Common().run();

    const page = $('body').data('page');
    if ('none' !== page && Object.hasOwn(modules, page)) {
        new modules[page]().run();
    }
});
