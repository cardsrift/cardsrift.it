// ------------------- imports
import $ from 'jquery';

import { calcViewportHeight, documentReady, pageLoad } from './utils';
// ------------------- imports###

import Header from './components/header';
import shop from './components/shop';
import globalComponents from './global-components';
import initEffects from './utils/effects';

window.jQuery = $;
window.$ = $;

const readyFunc = () => {
	Header();
	shop();
	initEffects();
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
