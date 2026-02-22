import './bootstrap.js';
import '@hotwired/turbo';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

document.addEventListener('keydown', (event) => {
	const textarea = event.target;
	if (!textarea || textarea.tagName !== 'TEXTAREA') {
		return;
	}

	if (textarea.name !== 'contenu') {
		return;
	}

	if (event.key === 'Enter' && !event.shiftKey) {
		const form = textarea.closest('form');
		if (form) {
			event.preventDefault();
			form.requestSubmit();
		}
	}
});

document.addEventListener('turbo:submit-end', (event) => {
	const form = event.target;
	if (!form || form.tagName !== 'FORM') {
		return;
	}

	if (!form.dataset.turboFrame || !event.detail?.success) {
		return;
	}

	if (!form.action.includes('/commentaire')) {
		return;
	}

	if (!form.querySelector('.comment-submit-button')) {
		return;
	}

	const textarea = form.querySelector('textarea[name="contenu"]');
	if (textarea) {
		textarea.value = '';
	}
});
