// scss
import './scss/main_global.scss';
import './tailwind/build/main_tailwind.css';
// js
import './js/app';
// import sprite_icons svg
function requireAll(r) {
	r.keys().forEach(r);
}

requireAll(require.context('./images/icons/sprite_icons/', true, /\.svg$/));
