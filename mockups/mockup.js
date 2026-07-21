/* Shared stubs for the EM Billing mockups — no backend, demo interactions only.
   Loaded after jQuery + Bootstrap 5 (bundle). */

/* ---------- Badge legend footer ---------- */
var LEGEND = {
    request: [
        ['b-pending', 'fa-clock', 'pending'],
        ['b-approved', 'fa-check', 'approved'],
        ['b-enabled', 'fa-check-circle', 'enabled'],
        ['b-grace', 'fa-seedling', 'dev grace (free 90 days)'],
        ['b-awaiting', 'fa-ticket-alt', 'awaiting_super_user'],
        ['b-error', 'fa-exclamation-triangle', 'approval_error'],
        ['b-rejected', 'fa-times-circle', 'rejected'],
        ['b-deactivated', 'fa-ban', 'deactivated']
    ],
    billing: [
        ['b-bpending', 'fa-hourglass-half', 'pending'],
        ['b-charged', 'fa-check', 'charged'],
        ['b-brejected', 'fa-times-circle', 'rejected'],
        ['b-waived', 'fa-hand-holding-usd', 'waived'],
        ['b-hold', 'fa-pause-circle', 'hold'],
        ['b-grace', 'fa-seedling', 'grace ($0 — dev 90 days)']
    ],
    run: [
        ['b-draft', 'fa-pencil-alt', 'draft'],
        ['b-exported', 'fa-file-export', 'exported'],
        ['b-finalized', 'fa-lock', 'finalized']
    ],
    pta: [
        ['b-enabled', 'fa-check', 'active'],
        ['b-expiring', 'fa-bell', 'expiring soon — alert sent'],
        ['b-expired', 'fa-ban', 'expired — EMs disabled']
    ]
};

function badgeHtml(cls, icon, label) {
    return '<span class="b ' + cls + '"><i class="fas ' + icon + '"></i>' + label + '</span>';
}

function renderLegend(sel, kinds) {
    var names = { request: 'Request line', billing: 'Billing line', run: 'Billing run', pta: 'PTA account' };
    var html = '';
    (kinds || ['request', 'billing', 'run']).forEach(function (k) {
        html += '<div class="legend-row"><span class="legend-title">' + names[k] + ':</span>';
        LEGEND[k].forEach(function (b) { html += badgeHtml(b[0], b[1], b[2]) + ' '; });
        html += '</div>';
    });
    $(sel).addClass('legend-footer').html(html);
}

/* ---------- Toast ---------- */
function mockToast(msg) {
    var $t = $('#mock-toast');
    if (!$t.length) $t = $('<div id="mock-toast"></div>').appendTo('body');
    $t.stop(true, true).text(msg).fadeIn(150).delay(2600).fadeOut(400);
}

/* ---------- Simple client-side table filter (DataTables-style search) ---------- */
function bindTableFilter(inputSel, tableSel) {
    $(inputSel).on('keyup', function () {
        var q = $(this).val().toLowerCase();
        var shown = 0;
        $(tableSel + ' tbody tr').each(function () {
            var hit = $(this).text().toLowerCase().indexOf(q) !== -1;
            $(this).toggle(hit);
            if (hit) shown++;
        });
        $(tableSel).closest('.panel-box, .tab-pane, body').find('.dt-info').first()
            .text('Showing ' + shown + ' entries' + (q ? ' (filtered)' : ''));
    });
}

/* ---------- Collapsible PTA → project → line hierarchy ---------- */
function bindCollapsibles(scope) {
    $(scope || document).on('click', '.pta-head', function () {
        $(this).toggleClass('collapsed-caret').nextAll('.proj-block').toggle();
    });
    $(scope || document).on('click', '.proj-head', function (e) {
        e.stopPropagation();
        $(this).toggleClass('collapsed-caret').next('.line-table').toggle();
    });
}

/* ---------- Audited status-override modal (shared by run detail + ledger) ---------- */
function openOverrideModal(opts) {
    // opts: {title, context, presetStatus}
    $('#ovr-context').html(opts.context || '');
    $('#ovr-modal .modal-title').text(opts.title || 'Override status');
    $('#ovr-status').val(opts.presetStatus || 'charged');
    $('#ovr-note').val('').removeClass('is-invalid');
    $('#ovr-modal').modal('show');
}

$(function () {
    // Override modal save = required-note validation + toast
    $(document).on('click', '#ovr-save', function () {
        var note = $('#ovr-note').val().trim();
        if (!note) { $('#ovr-note').addClass('is-invalid'); return; }
        $('#ovr-modal').modal('hide');
        mockToast('Status set to "' + $('#ovr-status').val() +
            '" — audited (user: rssd_admin, source: manual).');
    });

    // Open tab from URL hash, e.g. billing_admin.html#import
    if (location.hash) {
        var $tab = $('.nav-tabs a[href="' + location.hash + '"]');
        if ($tab.length) $tab.tab('show');
    }
    $('.nav-tabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        history.replaceState(null, '', e.target.hash);
    });

    bindCollapsibles();
});
