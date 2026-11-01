/**
 * Sergio AI Studio v2.1 — Studio Controller (Bespoke Craft Edition)
 */

jQuery(document).ready(function($) {
    'use strict';

    function getAjaxUrl() {
        return (typeof sergioAiVars !== 'undefined' && sergioAiVars.ajax_url)
            ? sergioAiVars.ajax_url
            : (window.ajaxurl || '/wp-admin/admin-ajax.php');
    }

    function getNonce() {
        return (typeof sergioAiVars !== 'undefined' && sergioAiVars.nonce)
            ? sergioAiVars.nonce
            : '';
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) { return ''; }
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function safeUrl(url) {
        if (!url || typeof url !== 'string') { return '#'; }
        var trimmed = url.trim();
        if (/^(?:https?:|\/|\.\/)/i.test(trimmed)) {
            return escapeHtml(trimmed);
        }
        return '#';
    }

    // -------------------------------------------------------------------------
    // Studio Modal Popup System (Clean Light Studio)
    // Replaces blocking alert() & confirm() with modern responsive modal popups
    // -------------------------------------------------------------------------
    function studioModal(options) {
        var opt = $.extend({
            title: '',
            message: '',
            type: 'info', // 'info' | 'success' | 'warning' | 'error'
            confirmText: 'Đóng',
            cancelText: 'Hủy Bỏ',
            isConfirm: false,
            onConfirm: null,
            onCancel: null
        }, typeof options === 'string' ? { message: options } : options);

        $('#studio-active-popup').remove();

        var typeConfigs = {
            info: {
                icon: '<span class="dashicons dashicons-info" style="color: #2271b1; font-size: 20px; width: 20px; height: 20px; vertical-align: middle;"></span>',
                defaultTitle: 'Thông Báo'
            },
            success: {
                icon: '<span class="dashicons dashicons-yes-alt" style="color: #00a32a; font-size: 20px; width: 20px; height: 20px; vertical-align: middle;"></span>',
                defaultTitle: 'Thành Công'
            },
            warning: {
                icon: '<span class="dashicons dashicons-warning" style="color: #dba617; font-size: 20px; width: 20px; height: 20px; vertical-align: middle;"></span>',
                defaultTitle: 'Xác Nhận Hành Động'
            },
            error: {
                icon: '<span class="dashicons dashicons-dismiss" style="color: #d63638; font-size: 20px; width: 20px; height: 20px; vertical-align: middle;"></span>',
                defaultTitle: 'Thông Báo Lỗi'
            }
        };

        var config = typeConfigs[opt.type] || typeConfigs.info;
        var modalTitle = opt.title || (opt.isConfirm ? 'Xác Nhận Hành Động' : config.defaultTitle);

        var buttonsHtml = opt.isConfirm
            ? '<button type="button" class="button button-secondary studio-popup-cancel-btn">' + escapeHtml(opt.cancelText) + '</button>' +
              '<button type="button" class="button button-primary studio-popup-action-btn">' + escapeHtml(opt.confirmText) + '</button>'
            : '<button type="button" class="button button-primary studio-popup-action-btn">' + escapeHtml(opt.confirmText) + '</button>';

        var html = '<div id="studio-active-popup" class="studio-popup-backdrop" role="dialog" aria-modal="true">' +
            '<div class="studio-popup-card">' +
                '<div class="studio-popup-header">' +
                    '<div class="studio-popup-title-wrap">' +
                        config.icon +
                        '<h3 class="studio-popup-title">' + escapeHtml(modalTitle) + '</h3>' +
                    '</div>' +
                    '<button type="button" class="studio-popup-close-btn" aria-label="Đóng">&times;</button>' +
                '</div>' +
                '<div class="studio-popup-body">' +
                    '<p class="studio-popup-message">' + escapeHtml(opt.message).replace(/\n/g, '<br>') + '</p>' +
                '</div>' +
                '<div class="studio-popup-footer">' +
                    buttonsHtml +
                '</div>' +
            '</div>' +
        '</div>';

        var $popup = $(html).appendTo('body');

        function closePopup(confirmed) {
            $popup.fadeOut(150, function() {
                $popup.remove();
                if (confirmed) {
                    if (typeof opt.onConfirm === 'function') {
                        opt.onConfirm();
                    }
                } else {
                    if (typeof opt.onCancel === 'function') {
                        opt.onCancel();
                    }
                }
            });
        }

        $popup.on('click', '.studio-popup-action-btn', function(e) {
            e.preventDefault();
            closePopup(true);
        });

        $popup.on('click', '.studio-popup-close-btn, .studio-popup-cancel-btn', function(e) {
            e.preventDefault();
            closePopup(false);
        });

        $popup.on('click', function(e) {
            if ($(e.target).hasClass('studio-popup-backdrop')) {
                closePopup(false);
            }
        });

        $(document).off('keydown.studioPopup').on('keydown.studioPopup', function(e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                $(document).off('keydown.studioPopup');
                closePopup(false);
            }
        });

        setTimeout(function() {
            $popup.find('.studio-popup-action-btn').focus();
        }, 50);
    }

    function studioConfirm(message, onConfirm, onCancel, title) {
        studioModal({
            isConfirm: true,
            title: title || 'Xác Nhận Hành Động',
            message: message,
            type: 'warning',
            confirmText: 'Đồng Ý',
            cancelText: 'Hủy Bỏ',
            onConfirm: onConfirm,
            onCancel: onCancel
        });
    }

    // -------------------------------------------------------------------------
    // 1. WORDPRESS NATIVE TAB SWITCHING (Instant Client SPA + Progressive Sync)
    // -------------------------------------------------------------------------
    function normalizeTabId(rawId) {
        if (!rawId) return '';
        var clean = String(rawId).trim().replace(/^#/, '');
        if (clean.indexOf('tab=') !== -1) {
            var match = clean.match(/tab=([a-z0-9_-]+)/i);
            if (match) clean = match[1];
        }
        if (!clean.startsWith('tab-') && $('#tab-' + clean).length) {
            return 'tab-' + clean;
        }
        return clean;
    }

    function activateTab(rawTabId, updateHistory) {
        var tabId = normalizeTabId(rawTabId);
        if (!tabId || !$('#' + tabId).length) {
            return false;
        }

        var slug = tabId.replace(/^tab-/, '');

        // 1. Update navigation tab active states
        $('.nav-tab, .nav-tree-item, .nav-tab-link').removeClass('nav-tab-active is-active');
        $('[data-tab="' + tabId + '"], [data-tab="' + slug + '"], [href*="tab=' + slug + '"], [href="#' + tabId + '"]').addClass('nav-tab-active is-active');

        // 2. Hide all panes and show target pane
        $('.sergio-tab-pane, .studio-tab-panel').removeClass('is-active').hide();
        $('#' + tabId).addClass('is-active').show();

        // 3. Persist to localStorage
        try {
            localStorage.setItem('sergio_ai_active_tab', tabId);
        } catch(e) {}

        // 4. Update browser URL smoothly without full page refresh
        if (updateHistory !== false && window.history && window.history.replaceState) {
            try {
                var currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('tab', slug);
                window.history.replaceState({ tab: slug }, '', currentUrl.toString());
            } catch(e) {}
        }

        // 5. Trigger SEO overview audit if SEO tab became active
        if (tabId === 'tab-seo') {
            if (typeof seoAuditLoaded !== 'undefined' && !seoAuditLoaded && typeof runSiteSeoAudit === 'function') {
                seoAuditLoaded = true;
                runSiteSeoAudit();
            }
        }

        return true;
    }

    // Unified click handler for tabs and intra-tab links
    $(document).on('click', '.nav-tab, .nav-tree-item, .nav-tab-link, [data-tab]', function(e) {
        var $el = $(this);
        var target = $el.attr('data-tab') || $el.data('tab') || $el.attr('href');
        var normalized = normalizeTabId(target);

        if (normalized && $('#' + normalized).length) {
            e.preventDefault();
            activateTab(normalized, true);
        }
    });

    // Handle browser back and forward navigation
    window.addEventListener('popstate', function() {
        var urlParams = new URLSearchParams(window.location.search);
        var currentTab = urlParams.get('tab') || window.location.hash;
        if (currentTab) {
            activateTab(currentTab, false);
        }
    });

    // On page load, prioritize URL param, then hash, then localStorage
    (function initActiveTab() {
        var urlParams = new URLSearchParams(window.location.search);
        var urlTab = urlParams.get('tab');
        var hashTab = window.location.hash;

        if (urlTab && $('#tab-' + urlTab).length) {
            activateTab('tab-' + urlTab, false);
        } else if (hashTab && $('#' + normalizeTabId(hashTab)).length) {
            activateTab(hashTab, false);
        } else {
            try {
                var saved = localStorage.getItem('sergio_ai_active_tab');
                if (saved && $('#' + normalizeTabId(saved)).length) {
                    activateTab(saved, false);
                }
            } catch(e) {}
        }
    })();

    // -------------------------------------------------------------------------
    // 2. MODEL FETCHER & AUTO-DETECTOR
    // -------------------------------------------------------------------------
    function fetchAndPopulateModels(apiKey, isAuto, callback) {
        var $btn = $('#btn_refresh_models');
        var $feedback = $('#api_conn_feedback');
        var $primary = $('#gemini_model_primary');
        var $fallback = $('#gemini_model_fallback');

        if (!isAuto) {
            $btn.prop('disabled', true).text('Đang dò tìm...');
        } else {
            $feedback.css('color', '#6366f1').text('Đang tự động phát hiện model từ Google AI Studio...');
        }

        var postData = {
            action: 'sergio_ai_refresh_models',
            nonce: getNonce()
        };
        if (apiKey) {
            postData.api_key = apiKey;
        }

        $.post(getAjaxUrl(), postData)
            .done(function(res) {
                if (res.success && res.data.models && res.data.models.length > 0) {
                    var currentPrimary = $primary.val();
                    var currentFallback = $fallback.val();

                    $primary.empty();
                    $fallback.empty();

                    $.each(res.data.models, function(idx, m) {
                        var label = escapeHtml(m.display_name + ' (' + m.name + ')');
                        var val = escapeHtml(m.name);
                        $primary.append($('<option>', { value: val, text: label }));
                        $fallback.append($('<option>', { value: val, text: label }));
                    });

                    if (currentPrimary && $primary.find('option[value="' + currentPrimary + '"]').length) {
                        $primary.val(currentPrimary);
                    }
                    if (currentFallback && $fallback.find('option[value="' + currentFallback + '"]').length) {
                        $fallback.val(currentFallback);
                    } else {
                        var flashLite = $fallback.find('option[value*="flash-lite"]').first().val();
                        if (flashLite) { $fallback.val(flashLite); }
                    }

                    $feedback.css('color', '#10b981').text(res.data.message);
                    $('.status-indicator-pill').removeClass('is-disconnected').addClass('is-connected');
                    $('.status-indicator-pill .status-label').text('Gemini API: Đã kết nối');
                } else {
                    var msg = (res.data && res.data.message) ? res.data.message : 'Không tìm thấy model.';
                    $feedback.css('color', '#f43f5e').text(msg);
                }
            })
            .fail(function(xhr) {
                $feedback.css('color', '#f43f5e').text('Lỗi mạng hoặc server không phản hồi (' + xhr.status + ').');
            })
            .always(function() {
                $btn.prop('disabled', false).html('<svg class="sergio-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg> Làm mới danh sách');
                if (typeof callback === 'function') { callback(); }
            });
    }

    $('#btn_refresh_models').on('click', function() {
        var keyInput = $('#gemini_api_key').val().trim();
        fetchAndPopulateModels(keyInput, false);
    });

    var pasteTimer = null;
    $('#gemini_api_key').on('input paste', function() {
        var val = $(this).val().trim();
        if (val.length >= 35 && val.indexOf('••••') === -1) {
            clearTimeout(pasteTimer);
            pasteTimer = setTimeout(function() {
                fetchAndPopulateModels(val, true);
            }, 500);
        }
    });

    // -------------------------------------------------------------------------
    // 3. TEST API CONNECTION
    // -------------------------------------------------------------------------
    $('#btn_test_conn').on('click', function() {
        var $btn = $(this);
        var $feedback = $('#api_conn_feedback');
        var apiKey = $('#gemini_api_key').val().trim();

        $btn.prop('disabled', true).text('Đang kiểm tra...');
        $feedback.css('color', '#6366f1').text('Đang kết nối Google AI Studio...');

        $.post(getAjaxUrl(), {
            action: 'sergio_ai_test_connection',
            nonce: getNonce(),
            api_key: apiKey
        })
        .done(function(res) {
            if (res.success) {
                $feedback.css('color', '#10b981').text(res.data.message);
                $('.status-indicator-pill').removeClass('is-disconnected').addClass('is-connected');
                $('.status-indicator-pill .status-label').text('Gemini API: Đã kết nối');
            } else {
                var msg = (res.data && res.data.message) ? res.data.message : 'Kết nối thất bại.';
                $feedback.css('color', '#f43f5e').text(msg);
            }
        })
        .fail(function(xhr) {
            $feedback.css('color', '#f43f5e').text('Không thể kết nối (' + xhr.status + ').');
        })
        .always(function() {
            $btn.prop('disabled', false).html('<svg class="sergio-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg> Kiểm tra kết nối');
        });
    });

    // -------------------------------------------------------------------------
    // 4. FACT-BASED WRITER PIPELINE (1-Click & Step-by-Step Wizard)
    // -------------------------------------------------------------------------
    var activeFactPackData = null;

    function getWriterOptions() {
        return {
            seed_query: $('#writer_seed_query').val().trim(),
            category_id: $('#writer_category').val() || 0,
            language: $('#writer_language').val() || 'vi',
            target_length: $('#writer_target_length').val() || 'standard',
            tone: $('#writer_tone').val() || 'journalistic',
            model_override: $('#writer_model_override').val() || 'default',
            grounding_mode: $('#writer_grounding_mode').val() || 'grounding',
            verification_gate: $('#writer_verification_gate').val() || 'standard',
            image_option: $('#writer_image_option').val() || 'reference',
            post_status: $('#writer_post_status').val() || 'draft',
            enable_rank_math: $('#writer_enable_rank_math').is(':checked') ? 1 : 0,
            enable_internal_links: $('#writer_enable_internal_links').is(':checked') ? 1 : 0,
            enable_sources_box: $('#writer_enable_sources_box').is(':checked') ? 1 : 0
        };
    }

    function addPipelineStep(text, status) {
        var $steps = $('#pipeline_steps_list, #crawl_steps_list');
        var iconHtml = status === 'done'
            ? '<span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>'
            : (status === 'fail'
                ? '<span class="dashicons dashicons-dismiss" style="color: #d63638;"></span>'
                : '<span class="spinner is-active" style="float:none; margin:0 4px 0 0; vertical-align:middle;"></span>');

        var itemClass = 'stepper-item-row ' + (status === 'done' ? 'is-done' : (status === 'fail' ? 'is-failed' : 'is-active'));
        $steps.append('<div class="' + itemClass + '">' + iconHtml + '<span>' + escapeHtml(text) + '</span></div>');
    }

    function setStepperHeader($box, status, htmlOrText) {
        var $header = $box.find('.stepper-status-header');
        if (!$header.length) { return; }

        var $icon = $header.find('.stepper-header-icon');
        if (!$icon.length) {
            $header.prepend('<span class="stepper-header-icon"></span>');
            $icon = $header.find('.stepper-header-icon');
        }

        var $msg = $box.find('#pipeline_progress_msg, #crawl_progress_msg, #seo_batch_progress_text, #ai_seo_loading_msg');
        if (!$msg.length) {
            $msg = $header.find('span:not(.stepper-header-icon)');
        }

        if (status === 'running') {
            $icon.html('<span class="spinner is-active" style="float:none; margin:0;"></span>');
            $msg.html('<span>' + escapeHtml(htmlOrText) + '</span>');
        } else if (status === 'success') {
            $icon.html('<span class="dashicons dashicons-yes-alt" style="color: #00a32a; font-size:20px; width:20px; height:20px; vertical-align:middle;"></span>');
            $msg.html('<span style="color: #00a32a; font-weight: 600;">' + htmlOrText + '</span>');
        } else if (status === 'fail' || status === 'error') {
            $icon.html('<span class="dashicons dashicons-dismiss" style="color: #d63638; font-size:20px; width:20px; height:20px; vertical-align:middle;"></span>');
            $msg.html('<span style="color: #d63638; font-weight: 600;">' + htmlOrText + '</span>');
        }
    }

    // 4A. Run 1-Click Pipeline (Full flow)
    $('#btn_run_pipeline').on('click', function() {
        var $btn = $(this);
        var opts = getWriterOptions();

        if (!opts.seed_query) {
            studioModal({ title: 'Thiếu Dữ Liệu', message: 'Vui lòng nhập chủ đề hoặc ý định tìm kiếm!', type: 'warning' });
            $('#writer_seed_query').focus();
            return;
        }

        var $box = $('#pipeline_progress_box');
        var $steps = $('#pipeline_steps_list');
        var $resCard = $('#pipeline_result_card');
        var $previewBox = $('#fact_pack_preview_box');

        $btn.prop('disabled', true);
        $('#btn_step1_fact_pack_only').prop('disabled', true);
        $resCard.hide().empty();
        $previewBox.hide();
        $box.slideDown(150);
        $steps.empty();

        setStepperHeader($box, 'running', 'Đang nghiên cứu dữ kiện thực tế và lập Fact Pack...');
        addPipelineStep('Giai đoạn 1: Tra cứu dữ liệu thực tế từ web & đối chiếu thực thể...', 'active');

        var payload = $.extend({
            action: 'sergio_ai_run_pipeline_stage1',
            nonce: getNonce()
        }, opts);

        $.ajax({
            url: getAjaxUrl(),
            type: 'POST',
            timeout: 300000, // 5 minutes generous timeout
            data: payload
        })
        .done(function(res) {
            if (res.success) {
                var d = res.data;
                setStepperHeader($box, 'success', escapeHtml(d.message));
                $steps.children().last().replaceWith('<div class="stepper-item-row is-done"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><span>Giai đoạn 1: Thu thập & đối chiếu dữ kiện thực tế thành công</span></div>');
                addPipelineStep('Giai đoạn 2: Tách tiêu đề độc bản & phân tích thực thể cốt lõi', 'done');
                addPipelineStep('Giai đoạn 3: Biên tập bài viết chuyên sâu chuẩn Rank Math SEO (~' + escapeHtml(d.word_count) + ' từ)', 'done');
                addPipelineStep('Giai đoạn 4: Thiết lập Focus Keyword "' + escapeHtml(d.focus_keyword) + '" & xử lý ảnh đại diện', 'done');

                var statusBadge = d.status === 'publish'
                    ? '<span class="notice notice-success inline" style="padding: 2px 8px; font-weight: 600;">ĐÃ XUẤT BẢN</span>'
                    : (d.status === 'pending'
                        ? '<span class="notice notice-warning inline" style="padding: 2px 8px; font-weight: 600;">CHỜ DUYỆT</span>'
                        : '<span class="notice notice-info inline" style="padding: 2px 8px; font-weight: 600;">BẢN NHÁP</span>');

                var cardHtml = '<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; gap: 12px; flex-wrap: wrap;">' +
                        '<div style="font-size: 16px; font-weight: 600; color: #1d2327;">' + escapeHtml(d.title) + '</div>' +
                        statusBadge +
                    '</div>' +
                    '<p style="margin: 0 0 12px; font-size: 13px; color: #50575e;">' +
                        '<span>Từ khóa chính: <strong style="color: #1d2327;">' + escapeHtml(d.focus_keyword) + '</strong></span> &bull; ' +
                        '<span>Điểm SEO: <strong style="color: #00a32a;">' + (d.seo_score ? escapeHtml(d.seo_score) + '/100 (Rank Math)' : 'Tối ưu') + '</strong></span> &bull; ' +
                        '<span>Độ dài: <strong style="color: #1d2327;">~' + escapeHtml(d.word_count) + ' từ</strong></span> &bull; ' +
                        '<span>Nguồn kiểm chứng: <strong style="color: #00a32a;">' + escapeHtml(d.sources_count) + ' nguồn uy tín</strong></span>' +
                    '</p>' +
                    '<p style="margin: 0; display: flex; gap: 8px; flex-wrap: wrap;">' +
                        '<a href="' + safeUrl(d.edit_url) + '" target="_blank" class="button button-primary button-large"><span class="dashicons dashicons-edit"></span> Chỉnh Sửa Bài Viết</a>' +
                        '<a href="' + safeUrl(d.view_url) + '" target="_blank" class="button button-secondary button-large"><span class="dashicons dashicons-visibility"></span> Xem Bài Viết</a>' +
                    '</p>';

                $resCard.html(cardHtml).slideDown(200);
            } else {
                var err = (res.data && res.data.message) ? res.data.message : 'Có lỗi xảy ra.';
                setStepperHeader($box, 'fail', 'Thất bại: ' + escapeHtml(err));
                $steps.children().last().replaceWith('<div class="stepper-item-row is-failed"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f43f5e" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg><span>' + escapeHtml(err) + '</span></div>');
                studioModal({ title: 'Lỗi Biên Soạn Bài Viết', message: err, type: 'error' });
            }
        })
        .fail(function(xhr, status) {
            var msg = status === 'timeout'
                ? 'Hết thời gian chờ kết nối máy chủ (300 giây). Vui lòng thử lại hoặc chọn mô hình "Gemini 2.0 Flash" / "Tri thức trực tiếp" để tăng tốc.'
                : 'Lỗi mạng hoặc máy chủ (' + xhr.status + ').';
            setStepperHeader($box, 'fail', escapeHtml(msg));
            addPipelineStep(msg, 'fail');
            studioModal({ title: 'Lỗi Kết Nối', message: msg, type: 'error' });
        })
        .always(function() {
            $btn.prop('disabled', false);
            $('#btn_step1_fact_pack_only').prop('disabled', false);
        });
    });

    // 4B. Run Step 1 Only: Build Fact Pack for Interactive Preview
    $('#btn_step1_fact_pack_only').on('click', function() {
        var $btn = $(this);
        var opts = getWriterOptions();

        if (!opts.seed_query) {
            studioModal({ title: 'Thiếu Dữ Liệu', message: 'Vui lòng nhập chủ đề hoặc ý định tìm kiếm!', type: 'warning' });
            $('#writer_seed_query').focus();
            return;
        }

        var $box = $('#pipeline_progress_box');
        var $msg = $('#pipeline_progress_msg');
        var $steps = $('#pipeline_steps_list');
        var $previewBox = $('#fact_pack_preview_box');
        var $resCard = $('#pipeline_result_card');

        $btn.prop('disabled', true);
        $('#btn_run_pipeline').prop('disabled', true);
        $resCard.hide().empty();
        $previewBox.hide();
        $box.slideDown(150);
        $steps.empty();

        setStepperHeader($box, 'running', 'Đang tra cứu và lập Fact Pack từ web...');
        addPipelineStep('Thu thập dữ kiện thời gian thực & trích xuất thực thể...', 'active');

        $.ajax({
            url: getAjaxUrl(),
            type: 'POST',
            timeout: 180000,
            data: {
                action: 'sergio_ai_generate_fact_pack_only',
                nonce: getNonce(),
                seed_query: opts.seed_query,
                language: opts.language,
                model_override: opts.model_override,
                grounding_mode: opts.grounding_mode,
                verification_gate: opts.verification_gate
            }
        })
        .done(function(res) {
            if (res.success) {
                activeFactPackData = res.data;
                var d = res.data;
                setStepperHeader($box, 'success', escapeHtml(d.message));
                $steps.children().last().replaceWith('<div class="stepper-item-row is-done"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><span>Đã đối chiếu thành công ' + (d.key_facts ? d.key_facts.length : 0) + ' dữ kiện & ' + (d.source_citations ? d.source_citations.length : 0) + ' nguồn tham khảo</span></div>');

                // Render Fact Pack Preview HTML
                var html = '';
                if (d.primary_event) {
                    html += '<div style="margin-bottom: 12px; font-weight: 600; color: #1e293b;">Trọng tâm sự kiện: ' + escapeHtml(d.primary_event) + '</div>';
                }

                if (d.entities && d.entities.length) {
                    html += '<div style="margin-bottom: 12px;"><strong style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b;">Thực thể thật:</strong><div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 6px;">';
                    $.each(d.entities, function(i, ent) {
                        html += '<span class="filter-pill" style="background: #f1f5f9; color: #334155; font-size: 12px;">' + escapeHtml(ent.name) + ' (' + escapeHtml(ent.type || ent.role || '') + ')</span>';
                    });
                    html += '</div></div>';
                }

                if (d.key_facts && d.key_facts.length) {
                    html += '<div style="margin-bottom: 12px;"><strong style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b;">Dữ kiện xác thực:</strong><ul style="margin: 6px 0 0 18px; padding: 0; color: #334155;">';
                    $.each(d.key_facts, function(i, fact) {
                        html += '<li style="margin-bottom: 4px;">' + escapeHtml(fact) + '</li>';
                    });
                    html += '</ul></div>';
                }

                if (d.verified_metrics && d.verified_metrics.length) {
                    html += '<div style="margin-bottom: 12px;"><strong style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #059669;">Số liệu đo lường:</strong><div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 6px;">';
                    $.each(d.verified_metrics, function(i, mtr) {
                        html += '<span class="filter-pill" style="background: #ecfdf5; color: #059669; border-color: #a7f3d0; font-size: 12px;">' + escapeHtml(mtr) + '</span>';
                    });
                    html += '</div></div>';
                }

                if (d.source_citations && d.source_citations.length) {
                    html += '<div><strong style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b;">Nguồn bài viết gốc:</strong><ul style="margin: 6px 0 0 18px; padding: 0;">';
                    $.each(d.source_citations, function(i, src) {
                        var u = typeof src === 'string' ? src : (src.url || '#');
                        var t = typeof src === 'string' ? src : (src.title || src.publisher || u);
                        html += '<li style="margin-bottom: 4px;"><a href="' + safeUrl(u) + '" target="_blank" style="color: var(--sergio-accent); text-decoration: underline;">' + escapeHtml(t) + '</a></li>';
                    });
                    html += '</ul></div>';
                }

                $('#fact_pack_content_area').html(html);
                $previewBox.slideDown(200);
            } else {
                var err = (res.data && res.data.message) ? res.data.message : 'Không thể lập Fact Pack.';
                setStepperHeader($box, 'fail', 'Thất bại: ' + escapeHtml(err));
                $steps.children().last().replaceWith('<div class="stepper-item-row is-failed"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f43f5e" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg><span>' + escapeHtml(err) + '</span></div>');
                studioModal({ title: 'Lỗi Thu Thập Fact Pack', message: err, type: 'error' });
            }
        })
        .fail(function(xhr, status) {
            var msg = status === 'timeout'
                ? 'Hết thời gian chờ kết nối (180 giây). Vui lòng thử lại với chế độ "Tri thức trực tiếp" hoặc chọn model "Gemini 2.0 Flash".'
                : 'Lỗi mạng hoặc máy chủ (' + xhr.status + ').';
            setStepperHeader($box, 'fail', escapeHtml(msg));
            addPipelineStep(msg, 'fail');
            studioModal({ title: 'Lỗi Kết Nối', message: msg, type: 'error' });
        })
        .always(function() {
            $btn.prop('disabled', false);
            $('#btn_run_pipeline').prop('disabled', false);
        });
    });

    // 4C. Step 2: Write Article from Active Fact Pack
    $('#btn_write_from_fact_pack').on('click', function() {
        if (!activeFactPackData || !activeFactPackData.pack_id) {
            studioModal({ title: 'Thiếu Dữ Liệu', message: 'Vui lòng lập Fact Pack trước khi chấp bút!', type: 'warning' });
            return;
        }

        var $btn = $(this);
        var opts = getWriterOptions();
        var $box = $('#pipeline_progress_box');
        var $steps = $('#pipeline_steps_list');
        var $resCard = $('#pipeline_result_card');

        $btn.prop('disabled', true);
        $box.slideDown(150);
        $steps.empty();

        setStepperHeader($box, 'running', 'Đang chấp bút bài viết độc bản từ Fact Pack #' + activeFactPackData.pack_id + '...');
        addPipelineStep('Lập kế hoạch biên tập (Editorial Outline & Rank Math Focus Keyword)...', 'active');

        var payload = $.extend({
            action: 'sergio_ai_write_article_from_fact_pack',
            nonce: getNonce(),
            pack_id: activeFactPackData.pack_id
        }, opts);

        $.ajax({
            url: getAjaxUrl(),
            type: 'POST',
            timeout: 240000,
            data: payload
        })
        .done(function(res) {
            if (res.success) {
                var d = res.data;
                setStepperHeader($box, 'success', escapeHtml(d.message));
                $steps.children().last().replaceWith('<div class="stepper-item-row is-done"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><span>Hoàn thành chấp bút & thiết lập SEO</span></div>');
                addPipelineStep('Lưu bài viết (' + (d.status === 'publish' ? 'Đã xuất bản' : 'Bản nháp') + ') & Xử lý ảnh đại diện', 'done');

                var statusBadge = d.status === 'publish'
                    ? '<span class="filter-pill" style="background: #ecfdf5; color: #059669; border-color: #a7f3d0;">Đã xuất bản</span>'
                    : (d.status === 'pending'
                        ? '<span class="filter-pill" style="background: #fffbeb; color: #d97706; border-color: #fde68a;">Chờ duyệt</span>'
                        : '<span class="filter-pill" style="background: #f1f5f9; color: #475569; border-color: #cbd5e1;">Bản nháp</span>');

                var cardHtml = '<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; gap: 12px; flex-wrap: wrap;">' +
                        '<div style="font-size: 16px; font-weight: 700; color: #0f172a;">' + escapeHtml(d.title) + '</div>' +
                        statusBadge +
                    '</div>' +
                    '<div style="display: flex; gap: 20px; margin-bottom: 16px; font-size: 13px; color: #64748b; flex-wrap: wrap; align-items: center;">' +
                        '<span>Từ khóa: <strong style="color: #0f172a;">' + escapeHtml(d.focus_keyword) + '</strong></span>' +
                        '<span>Điểm SEO: <strong style="color: #059669; background: #ecfdf5; padding: 2px 8px; border-radius: 4px; border: 1px solid #a7f3d0;">' + (d.seo_score ? escapeHtml(d.seo_score) + '/100 (Xanh Rank Math)' : 'Tối ưu') + '</strong></span>' +
                        '<span>Độ dài: <strong style="color: #0f172a;">~' + escapeHtml(d.word_count) + ' từ</strong></span>' +
                        '<span>Nguồn: <strong style="color: #059669;">' + escapeHtml(d.sources_count) + ' nguồn</strong></span>' +
                    '</div>' +
                    '<div style="display: flex; gap: 10px; flex-wrap: wrap;">' +
                        '<a href="' + safeUrl(d.edit_url) + '" target="_blank" class="button button-primary">Chỉnh Sửa Bài Viết</a>' +
                        '<a href="' + safeUrl(d.view_url) + '" target="_blank" class="button button-secondary">Xem Bài Viết</a>' +
                    '</div>';

                $('#fact_pack_preview_box').slideUp(150);
                $resCard.html(cardHtml).slideDown(200);
                studioModal({ title: 'Thành Công', message: d.message, type: 'success' });
            } else {
                var err = (res.data && res.data.message) ? res.data.message : 'Lỗi khi chấp bút.';
                setStepperHeader($box, 'fail', 'Thất bại: ' + escapeHtml(err));
                $steps.children().last().replaceWith('<div class="stepper-item-row is-failed"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f43f5e" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg><span>' + escapeHtml(err) + '</span></div>');
                studioModal({ title: 'Lỗi Chấp Bút Bài Viết', message: err, type: 'error' });
            }
        })
        .fail(function(xhr, status) {
            var msg = status === 'timeout'
                ? 'Hết thời gian chờ kết nối máy chủ (240 giây).'
                : 'Lỗi mạng hoặc máy chủ (' + xhr.status + ').';
            setStepperHeader($box, 'fail', escapeHtml(msg));
            addPipelineStep(msg, 'fail');
            studioModal({ title: 'Lỗi Kết Nối', message: msg, type: 'error' });
        })
        .always(function() {
            $btn.prop('disabled', false);
        });
    });

    $('#btn_cancel_fact_pack').on('click', function() {
        $('#fact_pack_preview_box').slideUp(150);
        activeFactPackData = null;
    });

    // -------------------------------------------------------------------------
    // 5. CRAWL & REWRITE HANDLER
    // -------------------------------------------------------------------------
    $('#btn_crawl_rewrite').on('click', function() {
        var $btn = $(this);
        var rawUrls = $('#crawl_urls').val().trim();
        var catId = $('#crawl_category').val();
        var lang = $('#crawl_language').val();
        var style = $('#crawl_style').val();
        var maxChars = $('#crawl_max_chars').val();

        if (!rawUrls) {
            studioModal({ title: 'Thiếu Dữ Liệu', message: 'Vui lòng nhập ít nhất một URL cần crawl!', type: 'warning' });
            $('#crawl_urls').focus();
            return;
        }

        var $box = $('#crawl_progress_box');
        var $msg = $('#crawl_progress_msg');
        var $steps = $('#crawl_steps_list');
        var $resultsWrap = $('#crawl_results_wrap');
        var $tbody = $('#crawl_results_tbody');

        $btn.prop('disabled', true);
        $resultsWrap.hide();
        $tbody.empty();
        $box.slideDown(150);
        $steps.empty();

        setStepperHeader($box, 'running', 'Đang kết nối và thu thập nội dung từ các URL...');
        $steps.append('<div class="stepper-item-row is-active"><span class="stepper-spinner" style="width:12px; height:12px;"></span><span>Đang tải nội dung trang (wp_safe_remote_get)...</span></div>');

        $.ajax({
            url: getAjaxUrl(),
            type: 'POST',
            timeout: 180000,
            data: {
                action: 'sergio_ai_crawl_rewrite',
                nonce: getNonce(),
                urls: rawUrls,
                category_id: catId,
                language: lang,
                style: style,
                max_chars: maxChars
            }
        })
        .done(function(res) {
            if (res.success) {
                setStepperHeader($box, 'success', escapeHtml(res.data.message));
                $steps.children().last().replaceWith('<div class="stepper-item-row is-done"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><span>Thu thập và tái bút hoàn tất</span></div>');

                if (res.data.results && res.data.results.length > 0) {
                    $.each(res.data.results, function(idx, item) {
                        var statusBadge = item.status === 'success'
                            ? '<span class="status-badge-inline status-success">Thành công</span>'
                            : '<span class="status-badge-inline status-failed">Lỗi</span>';

                        var actionCol = item.status === 'success'
                            ? '<a href="' + safeUrl(item.edit_url) + '" target="_blank" class="button button-secondary button-small">Xem Nháp</a>'
                            : '<span style="color:#f43f5e; font-size:12px;">' + escapeHtml(item.message) + '</span>';

                        var row = '<tr>' +
                            '<td style="max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">' +
                                '<a href="' + safeUrl(item.url) + '" target="_blank" style="color:#94a3b8; font-size:12px;">' + escapeHtml(item.url) + '</a>' +
                            '</td>' +
                            '<td><strong class="item-primary-text">' + escapeHtml(item.title || '(Chưa có tiêu đề)') + '</strong></td>' +
                            '<td>' + statusBadge + '</td>' +
                            '<td>' + actionCol + '</td>' +
                        '</tr>';
                        $tbody.append(row);
                    });
                    $resultsWrap.slideDown(200);
                }
            } else {
                var err = (res.data && res.data.message) ? res.data.message : 'Có lỗi xảy ra khi crawl.';
                setStepperHeader($box, 'fail', 'Thất bại: ' + escapeHtml(err));
                $steps.children().last().replaceWith('<div class="stepper-item-row is-failed"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f43f5e" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg><span>' + escapeHtml(err) + '</span></div>');
                studioModal({ title: 'Lỗi Crawl', message: err, type: 'error' });
            }
        })
        .fail(function(xhr, status) {
            var msg = status === 'timeout'
                ? 'Hết thời gian chờ (180 giây). Các trang web cần crawl phản hồi quá chậm.'
                : 'Lỗi mạng hoặc máy chủ (' + xhr.status + ').';
            setStepperHeader($box, 'fail', escapeHtml(msg));
            studioModal({ title: 'Lỗi Kết Nối', message: msg, type: 'error' });
        })
        .always(function() {
            $btn.prop('disabled', false);
        });
    });

    // -------------------------------------------------------------------------
    // 6. QUEUE MANAGEMENT
    // -------------------------------------------------------------------------
    $('#btn_add_to_queue').on('click', function() {
        var $btn = $(this);
        var $feedback = $('#queue_add_feedback');
        var topics = $('#queue_topics_input').val().trim();
        var catId = $('#queue_category_select').val();

        if (!topics) {
            studioModal({ title: 'Thiếu Dữ Liệu', message: 'Vui lòng nhập ít nhất một chủ đề!', type: 'warning' });
            $('#queue_topics_input').focus();
            return;
        }

        $btn.prop('disabled', true).text('Đang nạp...');
        $feedback.empty();

        $.post(getAjaxUrl(), {
            action: 'sergio_ai_add_queue_topics',
            nonce: getNonce(),
            topics: topics,
            category_id: catId
        })
        .done(function(res) {
            if (res.success) {
                $feedback.css('color', '#10b981').text(res.data.message);
                $('#queue_topics_input').val('');
                setTimeout(function() { window.location.reload(); }, 800);
            } else {
                var msg = (res.data && res.data.message) ? res.data.message : 'Lỗi thêm hàng đợi.';
                $feedback.css('color', '#f43f5e').text(msg);
            }
        })
        .fail(function(xhr) {
            $feedback.css('color', '#f43f5e').text('Lỗi mạng (' + xhr.status + ').');
        })
        .always(function() {
            $btn.prop('disabled', false).text('Nạp Hàng Đợi');
        });
    });

    $(document).on('click', '.sergio-btn-retry-queue', function() {
        var $btn = $(this);
        var itemId = $btn.data('id');
        if (!itemId) { return; }

        $btn.prop('disabled', true);
        $.post(getAjaxUrl(), {
            action: 'sergio_ai_retry_queue_item',
            nonce: getNonce(),
            id: itemId
        })
        .done(function(res) {
            if (res.success) {
                var $row = $('#queue-row-' + itemId);
                $row.find('.sergio-queue-badge')
                    .removeClass('badge-error badge-success')
                    .addClass('badge-info')
                    .text('CHỜ XỬ LÝ');
                $row.find('.item-error-subtext').remove();
                $btn.replaceWith(
                    '<button type="button" class="button button-small sergio-btn-priority-queue" data-id="' + itemId + '" title="Đẩy lên đầu hàng đợi ưu tiên">Ưu tiên</button>'
                );
                studioModal({ title: 'Thành Công', message: res.data.message, type: 'success' });
            } else {
                studioModal({ title: 'Lỗi', message: res.data ? res.data.message : 'Lỗi thử lại.', type: 'error' });
                $btn.prop('disabled', false);
            }
        })
        .fail(function() {
            studioModal({ title: 'Lỗi Kết Nối', message: 'Không thể kết nối đến máy chủ.', type: 'error' });
            $btn.prop('disabled', false);
        });
    });

    $(document).on('click', '.sergio-btn-priority-queue', function() {
        var $btn = $(this);
        var itemId = $btn.data('id');
        if (!itemId) { return; }

        $btn.prop('disabled', true);
        $.post(getAjaxUrl(), {
            action: 'sergio_ai_prioritize_queue_item',
            nonce: getNonce(),
            id: itemId
        })
        .done(function(res) {
            if (res.success) {
                var $row = $('#queue-row-' + itemId);
                $row.css('background', '#f0fdf4');
                setTimeout(function() { $row.css('background', ''); }, 2000);
                studioModal({ title: 'Ưu Tiên', message: res.data.message, type: 'success' });
            } else {
                studioModal({ title: 'Lỗi', message: res.data ? res.data.message : 'Lỗi ưu tiên.', type: 'error' });
            }
        })
        .fail(function() {
            studioModal({ title: 'Lỗi Kết Nối', message: 'Không thể kết nối đến máy chủ.', type: 'error' });
        })
        .always(function() {
            $btn.prop('disabled', false);
        });
    });

    $(document).on('click', '.sergio-btn-del-queue', function() {
        var $btn = $(this);
        var itemId = $btn.data('id');
        if (!itemId) { return; }

        studioConfirm('Xác nhận xóa chủ đề này khỏi hàng đợi?', function() {
            $btn.prop('disabled', true);
            $.post(getAjaxUrl(), {
                action: 'sergio_ai_delete_queue_item',
                nonce: getNonce(),
                id: itemId
            })
            .done(function(res) {
                if (res.success) {
                    $('#queue-row-' + itemId).fadeOut(250, function() { $(this).remove(); });
                } else {
                    studioModal({ title: 'Lỗi', message: res.data ? res.data.message : 'Lỗi khi xóa chủ đề.', type: 'error' });
                    $btn.prop('disabled', false);
                }
            })
            .fail(function() {
                studioModal({ title: 'Lỗi Kết Nối', message: 'Không thể kết nối đến máy chủ.', type: 'error' });
                $btn.prop('disabled', false);
            });
        });
    });

    // -------------------------------------------------------------------------
    // 7. RUN CRON NOW (Manual Trigger)
    // -------------------------------------------------------------------------
    function triggerCronNow($btn, $feedback) {
        $btn.prop('disabled', true);
        $feedback.css('color', '#6366f1').text('Đang lấy chủ đề và khởi chạy pipeline...');

        $.post(getAjaxUrl(), {
            action: 'sergio_ai_run_cron_now',
            nonce: getNonce()
        })
        .done(function(res) {
            if (res.success) {
                $feedback.css('color', '#10b981').text(res.data.message);
            } else {
                var msg = (res.data && res.data.message) ? res.data.message : 'Lỗi chạy cron.';
                $feedback.css('color', '#f43f5e').text(msg);
            }
        })
        .fail(function(xhr) {
            $feedback.css('color', '#f43f5e').text('Không thể kích hoạt (' + xhr.status + ').');
        })
        .always(function() {
            $btn.prop('disabled', false);
        });
    }

    $('#btn_run_cron_now').on('click', function() {
        triggerCronNow($(this), $('#cron_run_feedback'));
    });

    $('#btn_run_cron_overview').on('click', function() {
        triggerCronNow($(this), $('#cron_overview_feedback'));
    });

    // -------------------------------------------------------------------------
    // 8. LOGS: NATIVE WORDPRESS LIST TABLE & FILTERS
    // -------------------------------------------------------------------------
    function fetchLogsLive(callback) {
        var $spinner = $('#log_refresh_spinner');
        $spinner.addClass('is-active');

        var level = $('#filter-by-level').val() || '';
        var module = $('#filter-by-stage').val() || '';
        var search = $('#log-search-input').val() || '';

        $.post(getAjaxUrl(), {
            action: 'sergio_ai_refresh_logs',
            nonce: getNonce(),
            level: level,
            module: module,
            search: search
        })
        .done(function(res) {
            if (res.success && res.data) {
                $('#raw_logs_tbody').html(res.data.html);
                var numText = (res.data.count || 0) + ' mục';
                $('#log_displaying_num, #log_displaying_num_bottom').text(numText);
                $('#cb-select-all-1, #cb-select-all-2').prop('checked', false);
                if (res.data.stats) {
                    $('.log-subsubsub-link[data-level=""] .count').text('(' + res.data.stats.total_logs + ')');
                    $('.log-subsubsub-link[data-level="info"] .count').text('(' + res.data.stats.info_count + ')');
                    $('.log-subsubsub-link[data-level="warning"] .count').text('(' + res.data.stats.warning_count + ')');
                    $('.log-subsubsub-link[data-level="error"] .count').text('(' + res.data.stats.error_count + ')');
                }
                if (typeof callback === 'function') {
                    callback(true);
                }
            } else {
                if (typeof callback === 'function') {
                    callback(false);
                }
            }
        })
        .fail(function() {
            if (typeof callback === 'function') {
                callback(false);
            }
        })
        .always(function() {
            $spinner.removeClass('is-active');
        });
    }

    // Checkbox select all (Header & Footer)
    $(document).on('change', '#cb-select-all-1, #cb-select-all-2', function() {
        var isChecked = $(this).prop('checked');
        $('#cb-select-all-1, #cb-select-all-2').prop('checked', isChecked);
        $('input[name="log_ids[]"]').prop('checked', isChecked);
    });

    // Subsubsub level filter links
    $(document).on('click', '.log-subsubsub-link', function(e) {
        e.preventDefault();
        var lvl = $(this).data('level');
        $('.log-subsubsub-link').removeClass('current').removeAttr('aria-current');
        $(this).addClass('current').attr('aria-current', 'page');
        $('#filter-by-level').val(lvl);
        fetchLogsLive();
    });

    // Filter button [Lọc]
    $(document).on('click', '#post-query-submit', function(e) {
        e.preventDefault();
        var lvl = $('#filter-by-level').val() || '';
        $('.log-subsubsub-link').removeClass('current').removeAttr('aria-current');
        $('.log-subsubsub-link[data-level="' + lvl + '"]').addClass('current').attr('aria-current', 'page');
        fetchLogsLive();
    });

    // Search button and Enter key
    $(document).on('click', '#search-submit', function(e) {
        e.preventDefault();
        fetchLogsLive();
    });

    $('#log-search-input').on('keydown', function(e) {
        if (e.keyCode === 13) {
            e.preventDefault();
            fetchLogsLive();
        }
    });

    // Bulk actions: [Apply]
    $(document).on('click', '#doaction, #doaction2', function(e) {
        e.preventDefault();
        var whichSelect = (this.id === 'doaction2') ? '#bulk-action-selector-bottom' : '#bulk-action-selector-top';
        var action = $(whichSelect).val();

        if (action === '-1') {
            return;
        }

        if (action === 'delete') {
            var selectedIds = [];
            $('input[name="log_ids[]"]:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) {
                studioModal({ title: 'Thông Báo', message: 'Vui lòng chọn ít nhất một mục để xóa.', type: 'warning' });
                return;
            }

            studioConfirm('Xác nhận xóa ' + selectedIds.length + ' mục đã chọn?', function() {
                var $feedback = $('#clear_logs_feedback');
                $feedback.css('color', '#64748b').text('Đang xóa...');
                $.post(getAjaxUrl(), {
                    action: 'sergio_ai_clear_logs',
                    nonce: getNonce(),
                    type: 'delete',
                    ids: selectedIds
                })
                .done(function(res) {
                    if (res.success) {
                        $feedback.css('color', '#00a32a').text(res.data.message);
                        fetchLogsLive();
                        setTimeout(function() { $feedback.fadeOut(400, function() { $(this).text('').show(); }); }, 3000);
                    } else {
                        $feedback.css('color', '#d63638').text(res.data.message || 'Lỗi khi xóa.');
                    }
                })
                .fail(function() {
                    $feedback.css('color', '#d63638').text('Lỗi kết nối.');
                });
            });
        } else if (action === 'clear_all') {
            studioConfirm('CẢNH BÁO: Bạn có chắc chắn muốn xóa TOÀN BỘ nhật ký không?', function() {
                var $feedback = $('#clear_logs_feedback');
                $feedback.css('color', '#64748b').text('Đang xóa...');
                $.post(getAjaxUrl(), {
                    action: 'sergio_ai_clear_logs',
                    nonce: getNonce(),
                    type: 'all'
                })
                .done(function(res) {
                    if (res.success) {
                        $feedback.css('color', '#00a32a').text(res.data.message);
                        fetchLogsLive();
                        setTimeout(function() { $feedback.fadeOut(400, function() { $(this).text('').show(); }); }, 3000);
                    } else {
                        $feedback.css('color', '#d63638').text(res.data.message || 'Lỗi khi xóa.');
                    }
                })
                .fail(function() {
                    $feedback.css('color', '#d63638').text('Lỗi kết nối.');
                });
            });
        }
    });
    // -------------------------------------------------------------------------
    // 9. SCHEDULER DYNAMIC CONTROLS
    // -------------------------------------------------------------------------
    $('#schedule_frequency_select').on('change', function() {
        if ($(this).val() === 'sergio_custom_interval') {
            $('#custom_hours_wrapper').slideDown(150);
        } else {
            $('#custom_hours_wrapper').slideUp(150);
        }
    });

    $('#schedule_topic_mode').on('change', function() {
        if ($(this).val() === 'queue_only') {
            $('#niche_topic_wrapper').slideUp(150);
        } else {
            $('#niche_topic_wrapper').slideDown(150);
        }
    });

    $('#schedule_posts_per_run').on('change', function() {
        var count = $(this).val();
        $('#btn_run_cron_now span').text('Chạy Thử Ngay ' + count + ' Bài (Run Batch Now)');
    });

    // -------------------------------------------------------------------------
    // 10. SEO AUDIT & AI ON-PAGE OPTIMIZER
    // -------------------------------------------------------------------------
    var currentAuditedPostId = 0;

    function renderChecklist(checklist) {
        var $wrap = $('#audit_checklist_wrap');
        $wrap.empty();

        if (!checklist || checklist.length === 0) {
            $wrap.html('<p style="font-size:13px; color:#64748b;">Không có tiêu chí nào được đánh giá.</p>');
            return;
        }

        $.each(checklist, function(i, item) {
            var statusCls = 'item-' + item.status;
            var iconSvg = '';
            if (item.status === 'pass') {
                iconSvg = '<span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>';
            } else if (item.status === 'warning') {
                iconSvg = '<span class="dashicons dashicons-warning" style="color: #dba617;"></span>';
            } else {
                iconSvg = '<span class="dashicons dashicons-dismiss" style="color: #d63638;"></span>';
            }

            var html = '<div class="checklist-item-row ' + statusCls + '">' +
                '<span class="checklist-icon">' + iconSvg + '</span>' +
                '<div class="checklist-body">' +
                    '<div class="checklist-heading">' +
                        '<span class="checklist-title">' + escapeHtml(item.title) + '</span>' +
                        '<span class="checklist-points-pill">+' + (item.points || 0) + ' đ</span>' +
                    '</div>' +
                    '<p class="checklist-msg">' + escapeHtml(item.message) + '</p>' +
                '</div>' +
            '</div>';

            $wrap.append(html);
        });
    }

    function updateAuditUI(audit) {
        if (!audit) { return; }
        currentAuditedPostId = audit.post_id;

        $('#audit_post_id_label').text('#' + audit.post_id);
        $('#audit_post_type_label').text((audit.post_type || 'post').toUpperCase());
        $('#audit_post_status_label').text((audit.post_status || 'draft').toUpperCase());
        $('#audit_post_title_heading').text(audit.post_title || '(Không có tiêu đề)');
        $('#audit_post_edit_btn').attr('href', safeUrl(audit.edit_url));
        $('#audit_post_view_btn').attr('href', safeUrl(audit.view_url));

        // Score disc
        var score = audit.score || 0;
        $('#audit_score_val').text(score);
        var $disc = $('#audit_score_disc');
        $disc.removeClass('meter-great meter-fair meter-poor');
        if (score >= 80) {
            $disc.addClass('meter-great');
            $('#audit_score_status_text').css('color', '#10b981').text('Tuyệt vời — Rank Math Đạt Chuẩn (' + score + '/100)');
        } else if (score >= 50) {
            $disc.addClass('meter-fair');
            $('#audit_score_status_text').css('color', '#f59e0b').text('Khá — Cần Cải Thiện Thêm (' + score + '/100)');
        } else {
            $disc.addClass('meter-poor');
            $('#audit_score_status_text').css('color', '#f43f5e').text('Kém — Cần Tối Ưu Gấp (' + score + '/100)');
        }

        // SERP preview
        var serp = audit.serp_preview || {};
        $('#serp_preview_url .serp-url-text').text(serp.url || 'https://yoursite.com/...');
        $('#serp_preview_title').text(serp.title || audit.seo_title || audit.post_title);
        $('#serp_preview_desc').text(serp.description || audit.meta_desc || 'Chưa có mô tả hiển thị trên Google.');

        // Editable inputs
        $('#edit_seo_focus_kw').val(audit.focus_keyword || '');
        $('#edit_seo_title').val(audit.seo_title || '');
        $('#edit_seo_description').val(audit.meta_desc || '');

        updateCharCounters();
        renderChecklist(audit.checklist);

        // Update pick card badge in left list
        var $card = $('.seo-post-pick-card[data-post-id="' + audit.post_id + '"]');
        if ($card.length) {
            var $badge = $card.find('.pick-score-badge');
            $badge.text(score).removeClass('score-great score-fair score-poor');
            if (score >= 80) {
                $badge.addClass('score-great');
            } else if (score >= 50) {
                $badge.addClass('score-fair');
            } else {
                $badge.addClass('score-poor');
            }
            if (audit.focus_keyword) {
                $card.find('.pick-card-meta').html('<span>KW: ' + escapeHtml(audit.focus_keyword) + '</span>');
            }
        }

        $('#seo_pane_empty').hide();
        $('#seo_pane_active').slideDown(150);
        $('#save_seo_feedback').empty();
    }

    function updateCharCounters() {
        var tLen = ($('#edit_seo_title').val() || '').length;
        var dLen = ($('#edit_seo_description').val() || '').length;
        $('#edit_title_char_counter').text(tLen + ' / 60');
        $('#edit_desc_char_counter').text(dLen + ' / 160');

        if (tLen > 65) {
            $('#edit_title_char_counter').css('color', '#f43f5e');
        } else if (tLen >= 40) {
            $('#edit_title_char_counter').css('color', '#10b981');
        } else {
            $('#edit_title_char_counter').css('color', '#64748b');
        }

        if (dLen > 165) {
            $('#edit_desc_char_counter').css('color', '#f43f5e');
        } else if (dLen >= 120) {
            $('#edit_desc_char_counter').css('color', '#10b981');
        } else {
            $('#edit_desc_char_counter').css('color', '#64748b');
        }
    }

    // Live SERP update on typing
    $('#edit_seo_title').on('input', function() {
        var val = $(this).val();
        $('#serp_preview_title').text(val || $('#audit_post_title_heading').text());
        updateCharCounters();
    });

    $('#edit_seo_description').on('input', function() {
        var val = $(this).val();
        $('#serp_preview_desc').text(val || 'Chưa có mô tả hiển thị trên Google.');
        updateCharCounters();
    });

    // Selecting a post card
    $(document).on('click', '.seo-post-pick-card', function() {
        var $card = $(this);
        var postId = $card.data('post-id');
        if (!postId) { return; }

        $('.seo-post-pick-card').removeClass('is-selected');
        $card.addClass('is-selected');

        $('#seo_pane_empty').hide();
        $('#seo_pane_active').show();
        $('#ai_seo_results_deck').hide();
        $('#audit_score_val').text('--');
        $('#audit_score_status_text').text('Đang phân tích On-Page...');
        $('#audit_checklist_wrap').html('<div class="stepper-status-header" style="padding:16px;"><span class="stepper-spinner"></span><span>Đang quét các chỉ số On-page...</span></div>');

        $.post(getAjaxUrl(), {
            action: 'sergio_ai_audit_single_post',
            nonce: getNonce(),
            post_id: postId
        })
        .done(function(res) {
            if (res.success && res.data && res.data.audit) {
                updateAuditUI(res.data.audit);
            } else {
                var err = (res.data && res.data.message) ? res.data.message : 'Lỗi phân tích bài viết.';
                studioModal({ title: 'Lỗi Phân Tích', message: err, type: 'error' });
            }
        })
        .fail(function() {
            studioModal({ title: 'Lỗi Kết Nối', message: 'Lỗi kết nối khi audit bài viết.', type: 'error' });
        });
    });

    // Filter posts and pages
    $('.seo-type-filter').on('click', function() {
        var type = $(this).data('type');
        $('.seo-type-filter').removeClass('is-active');
        $(this).addClass('is-active');

        var q = $('#seo_search_posts').val().toLowerCase().trim();
        filterSeoCards(type, q);
    });

    $('#seo_search_posts').on('input', function() {
        var q = $(this).val().toLowerCase().trim();
        var type = $('.seo-type-filter.is-active').data('type') || '';
        filterSeoCards(type, q);
    });

    function filterSeoCards(type, q) {
        $('.seo-post-pick-card').each(function() {
            var cardType = $(this).data('post-type') || '';
            var title = $(this).data('title') || '';
            var matchType = !type || cardType === type;
            var matchQuery = !q || title.indexOf(q) !== -1;
            if (matchType && matchQuery) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }

    // Run AI Optimization 1: Suggest Metas & Keyword
    $('#btn_trigger_ai_seo').on('click', function() {
        if (!currentAuditedPostId) { return; }
        var $btn = $(this);
        var $box = $('#ai_seo_loading_box');
        var $deck = $('#ai_seo_results_deck');

        $btn.prop('disabled', true);
        $('#ai_seo_loading_msg').text('Gemini đang phân tích nội dung, ý định tìm kiếm và kiến tạo bộ tiêu đề & mô tả tối ưu...');
        $box.slideDown(150);
        $deck.hide();

        $.post(getAjaxUrl(), {
            action: 'sergio_ai_optimize_post_seo',
            nonce: getNonce(),
            post_id: currentAuditedPostId
        })
        .done(function(res) {
            if (res.success && res.data && res.data.suggestions) {
                var s = res.data.suggestions;
                $('#ai_rec_focus_kw').val(s.recommended_focus_kw || '');

                // Claude-SEO Search Intent
                if (s.search_intent) {
                    $('#ai_rec_intent_tag').text('Ý định tìm kiếm (Claude-SEO): ' + s.search_intent);
                    $('#ai_rec_intent_box').show();
                } else {
                    $('#ai_rec_intent_box').hide();
                }

                // Auto-fill top recommendation immediately!
                if (s.recommended_focus_kw) {
                    $('#edit_seo_focus_kw').val(s.recommended_focus_kw);
                }
                if (s.suggested_titles && s.suggested_titles.length && s.suggested_titles[0].title) {
                    $('#edit_seo_title').val(s.suggested_titles[0].title).trigger('input');
                }
                if (s.suggested_descriptions && s.suggested_descriptions.length && s.suggested_descriptions[0].description) {
                    $('#edit_seo_description').val(s.suggested_descriptions[0].description).trigger('input');
                }

                // Secondary keywords
                var $sec = $('#ai_rec_secondary_kws');
                $sec.empty();
                if (s.secondary_keywords && s.secondary_keywords.length) {
                    $.each(s.secondary_keywords, function(i, kw) {
                        $sec.append('<span class="ai-lsi-pill">' + escapeHtml(kw) + '</span>');
                    });
                } else {
                    $sec.html('<em style="color:#64748b; font-size:12px;">Không có từ khóa phụ</em>');
                }

                // Suggested titles
                var $titles = $('#ai_rec_titles_list');
                $titles.empty();
                if (s.suggested_titles && s.suggested_titles.length) {
                    $.each(s.suggested_titles, function(i, item) {
                        var titleText = item.title || item;
                        var angleText = item.angle ? ' (' + item.angle + ')' : '';
                        var row = '<div class="ai-suggest-option-card btn-apply-ai-title" data-title="' + escapeHtml(titleText) + '">' +
                            '<span class="ai-suggest-option-text">' + escapeHtml(titleText) + '<small style="color:#64748b;">' + escapeHtml(angleText) + '</small></span>' +
                            '<button type="button" class="button button-secondary button-small" style="flex-shrink:0;">Dùng tiêu đề này</button>' +
                        '</div>';
                        $titles.append(row);
                    });
                }

                // Suggested descriptions
                var $descs = $('#ai_rec_descs_list');
                $descs.empty();
                if (s.suggested_descriptions && s.suggested_descriptions.length) {
                    $.each(s.suggested_descriptions, function(i, item) {
                        var descText = item.description || item;
                        var row = '<div class="ai-suggest-option-card btn-apply-ai-desc" data-desc="' + escapeHtml(descText) + '">' +
                            '<span class="ai-suggest-option-text">' + escapeHtml(descText) + '</span>' +
                            '<button type="button" class="button button-secondary button-small" style="flex-shrink:0;">Dùng mô tả này</button>' +
                        '</div>';
                        $descs.append(row);
                    });
                }

                // Enhancements
                var $enh = $('#ai_rec_enhancements_list');
                $enh.empty();
                if (s.content_enhancements && s.content_enhancements.length) {
                    $.each(s.content_enhancements, function(i, txt) {
                        $enh.append('<li>' + escapeHtml(txt) + '</li>');
                    });
                } else {
                    $enh.append('<li>Nội dung đã đủ sâu, tập trung vào liên kết nội bộ.</li>');
                }

                $deck.slideDown(200);
            } else {
                var err = (res.data && res.data.message) ? res.data.message : 'Lỗi khi tối ưu bằng AI.';
                studioModal({ title: 'Lỗi AI Tối Ưu', message: err, type: 'error' });
            }
        })
        .fail(function(xhr) {
            var msg = 'Lỗi kết nối khi gửi yêu cầu AI tối ưu.';
            if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                msg = xhr.responseJSON.data.message;
            } else if (xhr.responseText) {
                try {
                    var parsed = JSON.parse(xhr.responseText);
                    if (parsed && parsed.data && parsed.data.message) { msg = parsed.data.message; }
                } catch(e) {}
            }
            studioModal({ title: 'Lỗi Kết Nối', message: msg, type: 'error' });
        })
        .always(function() {
            $btn.prop('disabled', false);
            $box.slideUp(150);
        });
    });

    // Run AI Optimization 2: Deep Content Optimization (85+ Rank Math score)
    $('#btn_trigger_deep_content_seo').on('click', function() {
        if (!currentAuditedPostId) { return; }
        var $btn = $(this);
        var kw = $('#edit_seo_focus_kw').val().trim();
        if (!kw) {
            studioModal({ title: 'Thiếu Focus Keyword', message: 'Vui lòng nhập Focus Keyword (hoặc bấm nút "1. Gemini Gợi Ý SEO" trước) để AI có từ khóa mục tiêu tối ưu nội dung!', type: 'warning' });
            $('#edit_seo_focus_kw').focus();
            return;
        }

        studioConfirm('AI sẽ tự động tối ưu hóa nội dung: đưa từ khóa vào mở đầu, tối ưu các thẻ H2/H3, chèn thẻ alt hình ảnh và mở rộng chiều sâu đạt chuẩn On-Page Studio. Bạn có muốn thực hiện ngay?', function() {
            var $box = $('#ai_seo_loading_box');
            $btn.prop('disabled', true);
            $('#ai_seo_loading_msg').text('Gemini đang tái cấu trúc nội dung HTML: chèn từ khóa vào đoạn mở đầu, tối ưu H2/H3 và bổ sung thẻ ALT ảnh...');
            $box.slideDown(150);

            $.post(getAjaxUrl(), {
                action: 'sergio_ai_deep_optimize_content',
                nonce: getNonce(),
                post_id: currentAuditedPostId,
                focus_keyword: kw
            })
            .done(function(res) {
                if (res.success && res.data && res.data.audit) {
                    updateAuditUI(res.data.audit);
                    studioModal({ title: 'Tối Ưu Thành Công', message: 'Tuyệt vời! Nội dung đã được tái cấu trúc đạt chuẩn On-Page Studio. Điểm On-page mới: ' + res.data.audit.score + ' / 100', type: 'success' });
                } else {
                    var err = (res.data && res.data.message) ? res.data.message : 'Lỗi khi nâng cấp nội dung.';
                    studioModal({ title: 'Lỗi Nâng Cấp', message: err, type: 'error' });
                }
            })
            .fail(function(xhr) {
                var msg = 'Lỗi kết nối khi tối ưu nội dung.';
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    msg = xhr.responseJSON.data.message;
                }
                studioModal({ title: 'Lỗi Kết Nối', message: msg, type: 'error' });
            })
            .always(function() {
                $btn.prop('disabled', false);
                $box.slideUp(150);
            });
        });
    });

    // Click to use AI suggested title
    $(document).on('click', '.btn-apply-ai-title', function(e) {
        e.preventDefault();
        var title = $(this).data('title');
        if (title) {
            $('#edit_seo_title').val(title).trigger('input');
        }
    });

    // Click to use AI suggested description
    $(document).on('click', '.btn-apply-ai-desc', function(e) {
        e.preventDefault();
        var desc = $(this).data('desc');
        if (desc) {
            $('#edit_seo_description').val(desc).trigger('input');
        }
    });

    // Click to use recommended focus keyword
    $('#btn_apply_ai_kw').on('click', function() {
        var kw = $('#ai_rec_focus_kw').val();
        if (kw) {
            $('#edit_seo_focus_kw').val(kw);
        }
    });

    // Save to Rank Math
    $('#btn_save_seo_to_rankmath').on('click', function() {
        if (!currentAuditedPostId) { return; }
        var $btn = $(this);
        var $fb = $('#save_seo_feedback');

        var kw = $('#edit_seo_focus_kw').val().trim();
        var title = $('#edit_seo_title').val().trim();
        var desc = $('#edit_seo_description').val().trim();

        $btn.prop('disabled', true);
        $fb.css('color', '#6366f1').text('Đang lưu và tính lại điểm On-page...');

        $.post(getAjaxUrl(), {
            action: 'sergio_ai_apply_seo_changes',
            nonce: getNonce(),
            post_id: currentAuditedPostId,
            focus_keyword: kw,
            seo_title: title,
            description: desc
        })
        .done(function(res) {
            if (res.success) {
                $fb.css('color', '#10b981').text(res.data.message);
                if (res.data.audit) {
                    updateAuditUI(res.data.audit);
                }
            } else {
                var err = (res.data && res.data.message) ? res.data.message : 'Lỗi khi lưu Rank Math.';
                $fb.css('color', '#f43f5e').text(err);
            }
        })
        .fail(function() {
            $fb.css('color', '#f43f5e').text('Lỗi kết nối.');
        })
        .always(function() {
            $btn.prop('disabled', false);
        });
    });

    // Site SEO Health scan & Action Plan
    var siteMissingPostIds = [];
    var siteLowScoreIds = [];

    function renderActionPlan(plan, missingKwIds, missingDescIds, lowScoreIds) {
        var $box = $('#seo_site_action_plan_box');
        var $wrap = $('#seo_action_plan_items');
        $wrap.empty();

        siteMissingPostIds = Array.from(new Set([].concat(missingKwIds || [], missingDescIds || [])));
        siteLowScoreIds = Array.from(new Set(lowScoreIds || []));

        if (!plan || plan.length === 0) {
            $box.hide();
            return;
        }

        $.each(plan, function(i, p) {
            var actionBtnHtml = '';
            if (p.action_btn && p.affected > 0) {
                actionBtnHtml = '<button type="button" class="studio-btn studio-btn-secondary btn-sm btn-plan-action" data-type="' + escapeHtml(p.type) + '" style="margin-top:8px;">' +
                    escapeHtml(p.action_btn) + ' (' + p.affected + ' trang)' +
                '</button>';
            }

            var card = '<div class="seo-action-plan-card severity-' + p.severity + '" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">' +
                '<div style="max-width: 680px;">' +
                    '<h4 class="action-plan-title">' + escapeHtml(p.title) + '</h4>' +
                    '<p class="action-plan-desc">' + escapeHtml(p.description) + '</p>' +
                    '<p class="action-plan-solution"><strong>Giải pháp:</strong> ' + escapeHtml(p.solution) + '</p>' +
                '</div>' +
                (actionBtnHtml ? '<div>' + actionBtnHtml + '</div>' : '') +
            '</div>';
            $wrap.append(card);
        });

        if (siteMissingPostIds.length > 0) {
            $('#btn_batch_fix_all_missing').show().find('span').text('⚡ Tự Động Tối Ưu Bằng AI Cho ' + siteMissingPostIds.length + ' Trang Đang Thiếu');
        } else {
            $('#btn_batch_fix_all_missing').hide();
        }

        $box.slideDown(200);
    }

    $(document).on('click', '.btn-plan-action', function(e) {
        e.preventDefault();
        var type = $(this).data('type');
        var isDeep = (type === 'low_score');
        var targetIds = isDeep ? siteLowScoreIds : siteMissingPostIds;

        if (!targetIds || targetIds.length === 0) {
            studioModal({ title: 'Thông Báo', message: 'Không có bài viết hoặc trang nào cần xử lý.', type: 'info' });
            return;
        }

        var confirmMsg = isDeep
            ? 'Hệ thống sẽ dùng Claude-SEO Framework tái cấu trúc nội dung và metadata cho ' + targetIds.length + ' trang đạt chuẩn On-Page Studio. Quá trình có thể mất từ 30-60 giây. Bắt đầu ngay?'
            : 'Hệ thống sẽ dùng AI tạo Focus Keyword, Tiêu đề SEO, Mô tả Meta cho ' + targetIds.length + ' trang. Bắt đầu ngay?';

        studioConfirm(confirmMsg, function() {
            var $prog = $('#seo_batch_progress_box');
            $prog.slideDown(150);
            $('#seo_batch_progress_text').text('Gemini đang tiến hành tối ưu tự động (' + targetIds.length + ' trang)...');

            $.post(getAjaxUrl(), {
                action: 'sergio_ai_batch_fix_seo',
                nonce: getNonce(),
                post_ids: targetIds,
                deep_content: isDeep ? 1 : 0
            })
            .done(function(res) {
                if (res.success) {
                    studioModal({ title: 'Thành Công', message: res.data.message || 'Tối ưu hoàn tất!', type: 'success' });
                    runSiteSeoAudit();
                } else {
                    studioModal({ title: 'Lỗi', message: res.data ? res.data.message : 'Lỗi khi tối ưu hàng loạt.', type: 'error' });
                }
            })
            .fail(function() {
                studioModal({ title: 'Lỗi Kết Nối', message: 'Lỗi kết nối khi tối ưu hàng loạt.', type: 'error' });
            })
            .always(function() {
                $prog.slideUp(150);
            });
        });
    });

    $('#btn_batch_fix_all_missing').on('click', function() {
        if (!siteMissingPostIds || siteMissingPostIds.length === 0) {
            studioModal({ title: 'Thông Báo', message: 'Không có bài viết hoặc trang nào cần tối ưu.', type: 'info' });
            return;
        }

        var confirmMsg = 'Hệ thống sẽ dùng Gemini AI tự động phân tích và tạo Focus Keyword, Tiêu đề SEO, Mô tả Meta cho ' + siteMissingPostIds.length + ' trang đang thiếu. Quá trình này có thể mất từ 15-45 giây. Bắt đầu ngay?';
        studioConfirm(confirmMsg, function() {
            var $btn = $('#btn_batch_fix_all_missing');
            var $prog = $('#seo_batch_progress_box');
            $btn.prop('disabled', true);
            $prog.slideDown(150);
            $('#seo_batch_progress_text').text('Gemini đang tiến hành tối ưu tự động hàng loạt...');

            $.post(getAjaxUrl(), {
                action: 'sergio_ai_batch_fix_seo',
                nonce: getNonce(),
                post_ids: siteMissingPostIds,
                deep_content: 0
            })
            .done(function(res) {
                if (res.success) {
                    studioModal({ title: 'Thành Công', message: res.data.message || 'Tối ưu hàng loạt thành công!', type: 'success' });
                    runSiteSeoAudit();
                } else {
                    studioModal({ title: 'Lỗi', message: res.data ? res.data.message : 'Lỗi khi tối ưu hàng loạt.', type: 'error' });
                }
            })
            .fail(function() {
                studioModal({ title: 'Lỗi Kết Nối', message: 'Lỗi kết nối khi tối ưu hàng loạt.', type: 'error' });
            })
            .always(function() {
                $btn.prop('disabled', false);
                $prog.slideUp(150);
            });
        });
    });

    function runSiteSeoAudit() {
        var $btn = $('#btn_run_site_seo_audit');
        $btn.prop('disabled', true);
        $('#seo_stat_avg_score').text('...');

        $.post(getAjaxUrl(), {
            action: 'sergio_ai_site_seo_audit',
            nonce: getNonce(),
            limit: 60
        })
        .done(function(res) {
            if (res.success && res.data && res.data.data) {
                var d = res.data.data;
                $('#seo_stat_avg_score').text(d.average_score || 0);
                $('#seo_stat_good_count').text(d.good_count || 0);
                $('#seo_stat_warning_count').text(d.warning_count || 0);
                $('#seo_stat_missing_kw').text(d.missing_focus_kw || 0);

                var avg = d.average_score || 0;
                if (avg >= 80) {
                    $('#seo_stat_avg_label').text('Sức khỏe: Rất Tốt (Xanh)');
                } else if (avg >= 50) {
                    $('#seo_stat_avg_label').text('Sức khỏe: Khá (Cần tối ưu)');
                } else {
                    $('#seo_stat_avg_label').text('Sức khỏe: Kém (Nhiều trang thiếu chuẩn)');
                }

                // Dynamically re-render left post list so scores reflect immediately!
                if (d.posts && d.posts.length > 0) {
                    var $scrollBench = $('#seo_posts_list_scroll');
                    var html = '<div class="seo-post-cards-wrap">';
                    $.each(d.posts, function(i, p) {
                        var isSel = (currentAuditedPostId == p.id) ? ' is-selected' : '';
                        var sc = p.score > 0 ? p.score : '--';
                        var kwText = p.focus_keyword ? ('KW: ' + escapeHtml(p.focus_keyword)) : '<em style="color:#94a3b8;">Chưa có từ khóa</em>';
                        var bClass = p.score_badge ? p.score_badge.class : 'score-poor';
                        html += '<div class="seo-post-pick-card' + isSel + '" data-post-id="' + p.id + '" data-post-type="' + escapeHtml(p.post_type) + '" data-title="' + escapeHtml((p.title || '').toLowerCase()) + '">' +
                            '<div class="pick-card-head">' +
                                '<span class="pick-score-badge ' + bClass + '">' + sc + '</span>' +
                                '<div style="display:flex; gap:4px; align-items:center;">' +
                                    '<span class="pick-type-tag type-' + escapeHtml(p.post_type) + '">' + escapeHtml((p.post_type || 'post').toUpperCase()) + '</span>' +
                                    '<span class="pick-post-status">' + escapeHtml((p.status || 'draft').toUpperCase()) + '</span>' +
                                '</div>' +
                            '</div>' +
                            '<h4 class="pick-card-title">' + escapeHtml(p.title || '(Không có tiêu đề)') + '</h4>' +
                            '<div class="pick-card-meta"><span>' + kwText + '</span></div>' +
                        '</div>';
                    });
                    html += '</div>';
                    $scrollBench.html(html);

                    var curType = $('.seo-type-filter.is-active').data('type') || '';
                    var curQuery = $('#seo_search_posts').val().toLowerCase().trim();
                    filterSeoCards(curType, curQuery);
                }

                // Render Action Plan & Batch Optimizer
                renderActionPlan(d.action_plan, d.missing_kw_ids, d.missing_desc_ids, d.low_score_ids);
            }
        })
        .always(function() {
            $btn.prop('disabled', false);
        });
    }

    $('#btn_run_site_seo_audit').on('click', function() {
        runSiteSeoAudit();
    });

    // Auto-trigger overview audit when switching to SEO tab if not yet loaded
    var seoAuditLoaded = false;
    $(document).on('click', '[data-tab="tab-seo"]', function() {
        if (!seoAuditLoaded) {
            seoAuditLoaded = true;
            runSiteSeoAudit();
        }
    });

    if ($('#tab-seo').hasClass('is-active')) {
        seoAuditLoaded = true;
        runSiteSeoAudit();
    }

});
