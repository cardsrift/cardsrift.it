// ------------------- imports
import $ from 'jquery';

import { calcViewportHeight, documentReady, pageLoad } from './utils';
// ------------------- imports###

import Header from './components/header';
import globalComponents from './global-components';

window.jQuery = $;
window.$ = $;

const readyFunc = () => {
	Header();
};

const loadFunc = () => {
	// All js after page load
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
