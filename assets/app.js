/**
 * DMCA Panel — Frontend interactivity (vanilla JS)
 */

(function () {
    'use strict';

    /* ============================================================
       Toast
       ============================================================ */
    var toastId = 0;
    function toast(msg, type, duration) {
        type = type || 'info';
        duration = duration || 4000;
        var container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            container.id = 'toast-container';
            document.body.appendChild(container);
        }
        var el = document.createElement('div');
        el.className = 'toast toast-' + type;
        el.textContent = msg;
        container.appendChild(el);

        // Trigger show
        requestAnimationFrame(function () { el.classList.add('show'); });

        // Auto dismiss
        var tid = ++toastId;
        setTimeout(function () {
            el.classList.add('toast-hide');
            setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 200);
        }, duration);
        return tid;
    }
    window.__dmcaToast = toast;

    /* ============================================================
       Search
       ============================================================ */
    var searchInput = document.getElementById('search');
    if (searchInput) {
        var debounceTimer;
        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(filterRows, 300);
        });
    }

    function filterRows() {
        var query = searchInput.value.toLowerCase().trim();
        var rows = document.querySelectorAll('tbody tr[data-search]');
        var visibleCount = 0;
        rows.forEach(function (row) {
            var text = row.getAttribute('data-search');
            if (!query || text.indexOf(query) !== -1) {
                row.classList.remove('hidden');
                visibleCount++;
            } else {
                row.classList.add('hidden');
            }
        });
        // Toggle empty state
        var emptyEl = document.getElementById('empty-search');
        var tableWrap = document.querySelector('.table-wrap');
        var pagination = document.querySelector('.pagination');
        if (emptyEl) {
            emptyEl.classList.toggle('hidden', visibleCount > 0);
        }
        if (tableWrap) tableWrap.classList.toggle('hidden', visibleCount === 0 && query !== '');
        if (pagination) pagination.classList.toggle('hidden', query !== '');
    }

    /* ============================================================
       Modal
       ============================================================ */
    function openModal(data) {
        var overlay = document.getElementById('detail-modal');
        if (!overlay) return;
        var t = window.__i18n ? window.__i18n.t : function(k) { return k; };
        document.getElementById('modal-title').textContent = t('admin.modal.title') + ' #' + data.id;
        var html = '';
        html += '<div class="modal-section"><div class="modal-label">' + t('admin.modal.reporter') + '</div><div class="modal-value">' + esc(data.reporter_name) + ' &lt;' + esc(data.reporter_email) + '&gt;</div></div>';
        if (data.company_name) html += '<div class="modal-section"><div class="modal-label">' + t('admin.modal.company') + '</div><div class="modal-value">' + esc(data.company_name) + '</div></div>';
        if (data.address) html += '<div class="modal-section"><div class="modal-label">' + t('admin.modal.address') + '</div><div class="modal-value">' + esc(data.address) + '</div></div>';
        if (data.role_label) html += '<div class="modal-section"><div class="modal-label">' + t('admin.modal.role') + '</div><div class="modal-value">' + esc(data.role_label) + '</div></div>';
        if (data.phone) html += '<div class="modal-section"><div class="modal-label">' + t('admin.modal.phone') + '</div><div class="modal-value">' + esc(data.phone) + '</div></div>';
        html += '<div class="modal-section"><div class="modal-label">' + t('admin.modal.work') + '</div><div class="modal-value">' + esc(data.original_work) + '</div></div>';
        if (data.infringing_url) html += '<div class="modal-section"><div class="modal-label">' + t('admin.modal.url') + '</div><div class="modal-value"><a href="' + esc(data.infringing_url) + '" target="_blank" rel="noopener">' + esc(data.infringing_url) + '</a></div></div>';
        if (data.infringing_location) html += '<div class="modal-section"><div class="modal-label">' + t('admin.modal.location') + '</div><div class="modal-value">' + esc(data.infringing_location) + '</div></div>';
        if (data.info_hash) html += '<div class="modal-section"><div class="modal-label">' + t('admin.modal.hash') + '</div><div class="modal-value"><code class="code-inline">' + esc(data.info_hash) + '</code> <button class="copy-btn" onclick="DMCA.copyHash(\'' + esc(data.info_hash) + '\', this)">' + t('admin.copy') + '</button></div></div>';
        if (data.description) html += '<div class="modal-section"><div class="modal-label">' + t('admin.modal.desc') + '</div><div class="modal-value">' + esc(data.description) + '</div></div>';
        if (data.signature_name) html += '<div class="modal-section"><div class="modal-label">' + t('admin.modal.signature') + '</div><div class="modal-value">' + esc(data.signature_name) + '</div></div>';
        html += '<div class="modal-section modal-value text-sm">' + t('admin.modal.status') + '：' + data.status_label + ' &middot; ' + data.created_at + '</div>';
        if (data.admin_note) html += '<div class="modal-section"><div class="modal-label">' + t('admin.modal.admin_note') + '</div><div class="modal-value">' + esc(data.admin_note) + '</div></div>';

        document.getElementById('modal-content').innerHTML = html;
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        var overlay = document.getElementById('detail-modal');
        if (!overlay) return;
        overlay.classList.remove('open');
        document.body.style.overflow = '';
    }

    window.DMCA = {
        openModal: openModal,
        closeModal: closeModal,
        copyHash: copyHash,
        toggleReject: toggleReject,
        cancelReject: cancelReject,
        toast: toast
    };

    // Close modal on overlay click or Esc
    document.addEventListener('click', function (e) {
        if (e.target.id === 'detail-modal') closeModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeModal();
    });

    /* ============================================================
       Reject form toggle
       ============================================================ */
    function toggleReject(id) {
        // Close all other open reject rows first
        document.querySelectorAll('.reject-row.open').forEach(function (row) {
            if (row.id !== 'reject-row-' + id) {
                row.classList.remove('open');
                var f = row.querySelector('.reject-form');
                if (f) f.classList.remove('open');
            }
        });
        var row = document.getElementById('reject-row-' + id);
        var form = document.getElementById('reject-form-' + id);
        if (row && form) {
            row.classList.add('open');
            form.classList.add('open');
            var textarea = form.querySelector('textarea');
            if (textarea) textarea.focus();
        }
    }
    function cancelReject(id) {
        var row = document.getElementById('reject-row-' + id);
        var form = document.getElementById('reject-form-' + id);
        if (row) row.classList.remove('open');
        if (form) form.classList.remove('open');
    }

    /* ============================================================
       Copy hash
       ============================================================ */
    function copyHash(hash, btn) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(hash).then(function () {
                btn.classList.add('copied');
                btn.textContent = (window.__i18n ? window.__i18n.t('admin.copied') : 'Copied');
                setTimeout(function () {
                    btn.classList.remove('copied');
                    btn.textContent = (window.__i18n ? window.__i18n.t('admin.copy') : 'Copy');
                }, 2000);
            });
        } else {
            // Fallback
            var ta = document.createElement('textarea');
            ta.value = hash;
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            btn.classList.add('copied');
            btn.textContent = '已复制';
            setTimeout(function () {
                btn.classList.remove('copied');
                btn.textContent = '复制';
            }, 2000);
        }
    }

    /* ============================================================
       AJAX review (approve / reject / reopen)
       ============================================================ */
    document.querySelectorAll('.action-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = form.querySelector('button[type="submit"]');
            var originalText = btn.textContent;
            btn.classList.add('btn-loading');
            btn.disabled = true;

            var formData = new FormData(form);

            fetch('', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(function (r) { return r.text(); })
            .then(function (html) {
                // Parse the response to extract message and update
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');

                // Check for alert message
                var alert = doc.querySelector('.alert');
                if (alert) {
                    var isSuccess = alert.classList.contains('alert-success');
                    toast(alert.textContent.trim(), isSuccess ? 'success' : 'info');
                }

                // Update the table body
                var newTbody = doc.querySelector('tbody');
                var oldTbody = document.querySelector('tbody');
                if (newTbody && oldTbody) {
                    oldTbody.innerHTML = newTbody.innerHTML;
                    // Re-attach event handlers to new buttons
                    attachRowEvents();
                }

                // Update tabs/stats
                var newTabs = doc.querySelector('.tabs');
                var oldTabs = document.querySelector('.tabs');
                if (newTabs && oldTabs) {
                    oldTabs.innerHTML = newTabs.innerHTML;
                }
            })
            .catch(function () {
                toast('操作失败，请重试', 'error');
                btn.classList.remove('btn-loading');
                btn.disabled = false;
                btn.textContent = originalText;
            });
        });
    });

    /* ============================================================
       Attach events to dynamic rows
       ============================================================ */
    function attachRowEvents() {
        // Clickable rows → open modal
        document.querySelectorAll('.row-clickable').forEach(function (row) {
            row.addEventListener('click', function (e) {
                // Don't open modal if clicking a button or link
                if (e.target.closest('button, a, .btn, .copy-btn')) return;
                var data = {};
                Object.keys(row.dataset).forEach(function (k) {
                    data[k] = row.dataset[k];
                });
                openModal(data);
            });
        });

        // Re-attach action form handlers
        document.querySelectorAll('.action-form').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var btn = form.querySelector('button[type="submit"]');
                var originalText = btn.textContent;
                btn.classList.add('btn-loading');
                btn.disabled = true;

                var formData = new FormData(form);
                fetch('', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                })
                .then(function (r) { return r.text(); })
                .then(function (html) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(html, 'text/html');
                    var alert = doc.querySelector('.alert');
                    if (alert) {
                        var isSuccess = alert.classList.contains('alert-success');
                        toast(alert.textContent.trim(), isSuccess ? 'success' : 'info');
                    }
                    var newTbody = doc.querySelector('tbody');
                    var oldTbody = document.querySelector('tbody');
                    if (newTbody && oldTbody) {
                        oldTbody.innerHTML = newTbody.innerHTML;
                        attachRowEvents();
                    }
                    var newTabs = doc.querySelector('.tabs');
                    var oldTabs = document.querySelector('.tabs');
                    if (newTabs && oldTabs) {
                        oldTabs.innerHTML = newTabs.innerHTML;
                    }
                })
                .catch(function () {
                    toast('操作失败，请重试', 'error');
                    btn.classList.remove('btn-loading');
                    btn.disabled = false;
                    btn.textContent = originalText;
                });
            });
        });
    }

    // Escape helper
    function esc(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // Initial attach
    attachRowEvents();

})();
