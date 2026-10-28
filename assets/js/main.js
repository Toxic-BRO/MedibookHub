
(function () {
    'use strict';

    var sidebar  = document.getElementById('sidebar');
    var backdrop = document.getElementById('sidebarBackdrop');
    var toggle   = document.getElementById('sidebarToggle');

    function closeSidebar() {
        if (sidebar)  sidebar.classList.remove('open');
        if (backdrop) backdrop.classList.remove('show');
    }

    if (toggle && sidebar && backdrop) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            backdrop.classList.toggle('show');
        });
        backdrop.addEventListener('click', closeSidebar);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeSidebar();
        });
    }

    document.querySelectorAll('.alert-dismissible').forEach(function (el) {
        setTimeout(function () {
            if (!el.isConnected || !window.bootstrap) return;
            var inst = bootstrap.Alert.getOrCreateInstance(el);
            inst.close();
        }, 7000);
    });

    document.addEventListener('click', function (e) {
        var el = e.target.closest('[data-confirm]');
        if (!el) return;
        if (!window.confirm(el.getAttribute('data-confirm'))) {
            e.preventDefault();
            e.stopPropagation();
        }
    });

    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.getAttribute('data-confirm'))) e.preventDefault();
        });
    });

    document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.getAttribute('data-toggle-password'));
            if (!input) return;
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    });

    var slotInputs = document.querySelectorAll('.slot-btn input[type="radio"]');
    slotInputs.forEach(function (input) {
        input.addEventListener('change', function () {
            document.querySelectorAll('.slot-btn').forEach(function (b) {
                b.classList.remove('is-selected');
            });
            var label = input.closest('.slot-btn');
            if (label) label.classList.add('is-selected');

            var out = document.getElementById('selectedSlotLabel');
            if (out) out.textContent = input.getAttribute('data-label') || input.value;
            var submit = document.getElementById('bookSubmit');
            if (submit) submit.disabled = false;
        });
    });

    document.querySelectorAll('form[data-once]').forEach(function (form) {
        form.addEventListener('submit', function () {
            var btn = form.querySelector('[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.dataset.originalText = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Working…';
            }
        });
    });

    document.querySelectorAll('input[type="date"][data-min-today]').forEach(function (input) {
        if (!input.value) input.value = new Date().toISOString().slice(0, 10);
        input.min = new Date().toISOString().slice(0, 10);
    });

    document.querySelectorAll('.rating-input').forEach(function (wrap) {
        var out = document.getElementById(wrap.getAttribute('data-output'));
        wrap.querySelectorAll('input').forEach(function (input) {
            input.addEventListener('change', function () {
                if (out) out.textContent = input.value + ' / 5';
            });
        });
    });

    var symptomForm = document.getElementById('symptomForm');
    if (symptomForm) {
        var symptomText = document.getElementById('symptomText');
        var analyzeBtn = document.getElementById('analyzeBtn');
        var clearBtn = document.getElementById('clearBtn');
        var loading = document.getElementById('aiLoading');
        var results = document.getElementById('aiResults');

        document.querySelectorAll('.ai-chip').forEach(function (chip) {
            chip.addEventListener('click', function () {
                if (symptomText) {
                    symptomText.value = chip.getAttribute('data-symptom');
                    symptomText.focus();
                }
            });
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                if (symptomText) symptomText.value = '';
                if (results) results.style.display = 'none';
                if (symptomText) symptomText.focus();
            });
        }

        if (symptomForm) {
            symptomForm.addEventListener('submit', function (e) {
                e.preventDefault();
                if (!symptomText || symptomText.value.trim().length < 3) return;

                var formData = new FormData(symptomForm);
                if (analyzeBtn) {
                    analyzeBtn.disabled = true;
                    analyzeBtn.dataset.originalText = analyzeBtn.innerHTML;
                    analyzeBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Analyzing...';
                }
                if (loading) loading.style.display = 'block';
                if (results) results.style.display = 'none';

                fetch(symptomForm.getAttribute('action'), {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (loading) loading.style.display = 'none';
                    if (analyzeBtn) {
                        analyzeBtn.disabled = false;
                        analyzeBtn.innerHTML = analyzeBtn.dataset.originalText;
                    }
                    if (!data.ok) {
                        alert(data.error || 'Something went wrong. Please try again.');
                        return;
                    }
                    if (results) results.style.display = 'block';

                    var badge = document.getElementById('aiUrgencyBadge');
                    if (badge) badge.innerHTML = data.urgency_badge;

                    var specTag = document.getElementById('aiSpecialtyTag');
                    if (specTag) specTag.innerHTML = '<span class="specialty-tag" style="font-size:.95rem;padding:.5em 1em">Recommended: <strong>' + data.specialty + '</strong></span>';

                    var desc = document.getElementById('aiDescription');
                    if (desc) desc.textContent = data.description;

                    var dosList = document.getElementById('aiDosList');
                    if (dosList) dosList.innerHTML = data.dos;

                    var dontsList = document.getElementById('aiDontsList');
                    if (dontsList) dontsList.innerHTML = data.donts;

                    var selfCareCard = document.getElementById('aiSelfCareCard');
                    var selfCareText = document.getElementById('aiSelfCareText');
                    if (selfCareCard && selfCareText) {
                        if (data.self_care && data.self_care.trim()) {
                            selfCareCard.style.display = 'block';
                            selfCareText.textContent = data.self_care;
                        } else {
                            selfCareCard.style.display = 'none';
                        }
                    }

                    var docsList = document.getElementById('aiDoctorsList');
                    if (docsList) docsList.innerHTML = data.doctors;

                    var emergencyAlert = document.getElementById('aiEmergencyAlert');
                    var emergencyText = document.getElementById('aiEmergencyText');
                    if (emergencyAlert && emergencyText) {
                        if (data.urgency === 'urgent') {
                            emergencyAlert.style.display = 'flex';
                            emergencyText.textContent = 'Your symptoms may require urgent medical attention. If this is an emergency, go to the nearest emergency room or call your local emergency number immediately.';
                        } else {
                            emergencyAlert.style.display = 'none';
                        }
                    }

                    results.scrollIntoView({ behavior: 'smooth', block: 'start' });
                })
                .catch(function () {
                    if (loading) loading.style.display = 'none';
                    if (analyzeBtn) {
                        analyzeBtn.disabled = false;
                        analyzeBtn.innerHTML = analyzeBtn.dataset.originalText;
                    }
                    alert('Network error. Please check your connection and try again.');
                });
            });
        }
    }

    var latenessForm = document.getElementById('latenessForm');
    if (latenessForm) {
        var latenessBtn = document.getElementById('latenessBtn');
        var latenessResult = document.getElementById('latenessResult');
        latenessForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var formData = new FormData(latenessForm);
            if (latenessBtn) {
                latenessBtn.disabled = true;
                latenessBtn.dataset.originalText = latenessBtn.innerHTML;
                latenessBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Checking...';
            }
            fetch(latenessForm.getAttribute('action'), {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (latenessBtn) {
                    latenessBtn.disabled = false;
                    latenessBtn.innerHTML = latenessBtn.dataset.originalText;
                }
                if (latenessResult) {
                    latenessResult.style.display = 'block';
                    if (data.sent > 0) {
                        latenessResult.innerHTML = '<div class="alert alert-success py-2 mb-0" style="font-size:.84rem"><strong>' + data.sent + '</strong> reminder(s) sent to doctors.</div>';
                    } else {
                        latenessResult.innerHTML = '<div class="alert alert-info py-2 mb-0" style="font-size:.84rem">No doctors need reminding right now. All pending appointments are within the normal response time.</div>';
                    }
                }
            })
            .catch(function () {
                if (latenessBtn) {
                    latenessBtn.disabled = false;
                    latenessBtn.innerHTML = latenessBtn.dataset.originalText;
                }
                if (latenessResult) {
                    latenessResult.style.display = 'block';
                    latenessResult.innerHTML = '<div class="alert alert-danger py-2 mb-0" style="font-size:.84rem">Network error. Please try again.</div>';
                }
            });
        });
    }
})();
