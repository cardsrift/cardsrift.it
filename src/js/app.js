// ------------------- imports
import $ from 'jquery';

import { GLOBAL_VARS, filterData } from './utils/constants';
// import pageWidgetInit from './dev_vendors/dev_widget';
import {
	onWindowScroll, onWindowResize, calcViewportHeight, documentReady, pageLoad,
} from './utils';
// ------------------- imports###

import Test from './components/test';
import Header from './components/header';
import inputInit from './components/inputInit';
import selectInit from './components/selectInit';
import globalComponents from './global-components';

window.jQuery = $;
window.$ = $;

const readyFunc = () => {
	const $body = document.querySelector('body');
	Test();
	Header();
};

const loadFunc = () => {
	// All js after page load
	// inputInit();
	selectInit();
	globalComponents();
};

document.documentElement.style.setProperty('--full-height', `${window.innerHeight}px`);

documentReady(() => {
	readyFunc();
});

pageLoad(() => {
	document.body.classList.add('body--loaded');
	calcViewportHeight();
	loadFunc();
});
