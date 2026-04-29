import './bootstrap';
import Chart from 'chart.js/auto';

document.documentElement.classList.add('js-enabled');

const toNumber = (value) => Number.parseInt(String(value ?? 0), 10) || 0;

const animateCounter = (element) => {
	const target = toNumber(element.dataset.counter);
	const duration = 900;
	const start = performance.now();

	const frame = (now) => {
		const progress = Math.min((now - start) / duration, 1);
		element.textContent = Math.round(target * progress).toLocaleString('id-ID');

		if (progress < 1) {
			requestAnimationFrame(frame);
		}
	};

	requestAnimationFrame(frame);
};

const parseJsonDataset = (value, fallback = []) => {
	try {
		return JSON.parse(value ?? '');
	} catch (_error) {
		return fallback;
	}
};

const openModal = (modal) => {
	if (!modal) {
		return;
	}

	modal.dataset.open = 'true';
	document.body.classList.add('overflow-hidden');
};

const closeModal = (modal) => {
	if (!modal) {
		return;
	}

	modal.dataset.open = 'false';

	const hasOpenedModal = [...document.querySelectorAll('[data-modal]')]
		.some((item) => item.dataset.open === 'true');

	if (!hasOpenedModal) {
		document.body.classList.remove('overflow-hidden');
	}
};

const updateAttendanceMode = (form, mode) => {
	const normalized = mode === 'keluar' ? 'keluar' : 'masuk';

	form.querySelectorAll('[data-jenis-target]').forEach((target) => {
		const isMatch = target.dataset.jenisTarget === normalized;
		target.classList.toggle('is-hidden', !isMatch);

		target.querySelectorAll('input, select, textarea').forEach((field) => {
			field.disabled = !isMatch;
		});
	});
};

document.addEventListener('DOMContentLoaded', () => {
	const mobileToggle = document.querySelector('[data-mobile-toggle]');
	const mobileNav = document.querySelector('[data-mobile-nav]');

	if (mobileToggle && mobileNav) {
		mobileToggle.addEventListener('click', () => {
			const isOpen = mobileNav.dataset.open === 'true';
			mobileNav.dataset.open = String(!isOpen);
		});
	}

	document.querySelectorAll('[data-dismiss]').forEach((button) => {
		button.addEventListener('click', () => {
			button.closest('[data-flash]')?.remove();
		});
	});

	document.querySelectorAll('[data-open-modal]').forEach((button) => {
		button.addEventListener('click', () => {
			openModal(document.getElementById(button.dataset.openModal));
		});
	});

	document.querySelectorAll('[data-close-modal]').forEach((button) => {
		button.addEventListener('click', () => {
			closeModal(button.closest('[data-modal]'));
		});
	});

	document.querySelectorAll('[data-modal]').forEach((modal) => {
		modal.dataset.open = modal.dataset.open || 'false';

		modal.addEventListener('click', (event) => {
			if (event.target === modal) {
				closeModal(modal);
			}
		});
	});

	let pendingDeleteForm = null;
	const deleteModal = document.getElementById('delete-confirm-modal');
	const deleteMessage = document.getElementById('delete-confirm-message');
	const deleteSubmit = document.getElementById('delete-confirm-submit');

	document.querySelectorAll('form[data-confirm-delete]').forEach((form) => {
		form.addEventListener('submit', (event) => {
			event.preventDefault();

			pendingDeleteForm = form;

			if (deleteMessage) {
				deleteMessage.textContent = form.dataset.confirmDelete || 'Data akan dihapus permanen. Lanjutkan?';
			}

			openModal(deleteModal);
		});
	});

	if (deleteSubmit) {
		deleteSubmit.addEventListener('click', () => {
			if (!pendingDeleteForm) {
				return;
			}

			pendingDeleteForm.submit();
		});
	}

	document.querySelectorAll('[data-jenis-form]').forEach((form) => {
		const radioTriggers = form.querySelectorAll('[data-jenis-trigger]');
		const selectSwitch = form.querySelector('[data-jenis-switch]');

		if (radioTriggers.length > 0) {
			const current = [...radioTriggers].find((item) => item.checked)?.value || 'masuk';
			updateAttendanceMode(form, current);

			radioTriggers.forEach((item) => {
				item.addEventListener('change', () => updateAttendanceMode(form, item.value));
			});
		}

		if (selectSwitch) {
			updateAttendanceMode(form, selectSwitch.value);
			selectSwitch.addEventListener('change', () => updateAttendanceMode(form, selectSwitch.value));
		}
	});

	const counterObserver = new IntersectionObserver((entries, observer) => {
		entries.forEach((entry) => {
			if (!entry.isIntersecting) {
				return;
			}

			animateCounter(entry.target);
			observer.unobserve(entry.target);
		});
	}, { threshold: 0.45 });

	document.querySelectorAll('[data-counter]').forEach((counter) => {
		counterObserver.observe(counter);
	});

	const revealObserver = new IntersectionObserver((entries) => {
		entries.forEach((entry) => {
			if (entry.isIntersecting) {
				entry.target.classList.add('in-view');
			}
		});
	}, { threshold: 0.01 });

	document.querySelectorAll('.reveal').forEach((item) => {
		revealObserver.observe(item);
	});

	const attendanceChartEl = document.querySelector('[data-attendance-chart]');

	if (attendanceChartEl) {
		const labels = parseJsonDataset(attendanceChartEl.dataset.labels);
		const checkIn = parseJsonDataset(attendanceChartEl.dataset.checkin);
		const checkOut = parseJsonDataset(attendanceChartEl.dataset.checkout);

		new Chart(attendanceChartEl, {
			type: 'line',
			data: {
				labels,
				datasets: [
					{
						label: 'Absen Masuk',
						data: checkIn,
						borderColor: '#0f62fe',
						backgroundColor: 'rgba(15,98,254,0.16)',
						tension: 0.35,
						fill: true,
						pointRadius: 3,
					},
					{
						label: 'Absen Keluar',
						data: checkOut,
						borderColor: '#f97316',
						backgroundColor: 'rgba(249,115,22,0.12)',
						tension: 0.35,
						fill: true,
						pointRadius: 3,
					},
				],
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: {
						position: 'bottom',
					},
				},
				scales: {
					y: {
						beginAtZero: true,
						ticks: {
							precision: 0,
						},
					},
				},
			},
		});
	}

	const statusChartEl = document.querySelector('[data-status-chart]');

	if (statusChartEl) {
		const labels = parseJsonDataset(statusChartEl.dataset.labels);
		const values = parseJsonDataset(statusChartEl.dataset.values);

		new Chart(statusChartEl, {
			type: 'doughnut',
			data: {
				labels,
				datasets: [
					{
						data: values,
						backgroundColor: ['#0f62fe', '#f97316', '#16a34a', '#7c3aed', '#dc2626', '#0284c7'],
						borderWidth: 1,
						borderColor: '#ffffff',
					},
				],
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: {
						position: 'bottom',
					},
				},
			},
		});
	}
});
