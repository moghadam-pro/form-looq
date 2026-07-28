(function () {
	'use strict';

	function focusFirstError() {
		document.querySelectorAll('.fmpf-form-wrap[data-fmpf-has-errors="1"]').forEach(function (wrapper) {
			var target = wrapper.querySelector('[aria-invalid="true"]');
			if (!target) {
				target = wrapper.querySelector('.fmpf-error-summary');
			}
			if (target && typeof target.focus === 'function') {
				target.focus({ preventScroll: true });
				target.scrollIntoView({ block: 'center', behavior: 'smooth' });
			}
		});
	}

	function scheduleFocusFirstError() {
		if (typeof window.requestAnimationFrame !== 'function') {
			window.setTimeout(focusFirstError, 0);
			return;
		}

		window.requestAnimationFrame(function () {
			window.requestAnimationFrame(focusFirstError);
		});
	}

	function cleanStatusParameters() {
		if (!window.history || !window.URL) {
			return;
		}

		var url = new URL(window.location.href);
		if (!url.searchParams.has('fmpf_status') && !url.searchParams.has('fmpf_state') && !url.searchParams.has('fmpf_form')) {
			return;
		}

		url.searchParams.delete('fmpf_status');
		url.searchParams.delete('fmpf_state');
		url.searchParams.delete('fmpf_form');
		window.history.replaceState({}, document.title, url.pathname + url.search + url.hash);
	}

	function initialize() {
		scheduleFocusFirstError();
		cleanStatusParameters();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initialize);
	} else {
		initialize();
	}

	window.addEventListener('load', scheduleFocusFirstError, { once: true });
}());
