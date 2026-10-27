
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

    /* ---- star rating: mirror selection into a text label ---- */
    document.querySelectorAll('.rating-input').forEach(function (wrap) {
        var out = document.getElementById(wrap.getAttribute('data-output'));
        wrap.querySelectorAll('input').forEach(function (input) {
            input.addEventListener('change', function () {
                if (out) out.textContent = input.value + ' / 5';
            });
        });
    });
})();
