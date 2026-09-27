/**
 * Blog Automático — Admin JavaScript v1.1.0
 * Suporte a múltiplos provedores, bulk add de ideias, progresso e fila
 */
(function ($) {
    'use strict';

    const BA = {
        init: function () {
            this.bindGeneratePost();
            this.bindBulkAdd();
            this.bindQueueActions();
            this.bindLogActions();
            this.bindTestConnection();
            this.bindProviderTabs();
            this.bindProviderSelect();
            this.bindRangeSlider();
            this.bindApiKeyToggle();
            this.bindDiagnoseApi();
        },

        /**
         * Geração individual de post via IA
         */
        bindGeneratePost: function () {
            $(document).on('submit', '#ba-generate-form', function (e) {
                e.preventDefault();

                const $form = $(this);
                const $btn = $form.find('.ba-btn-generate');
                const $progress = $('#ba-progress');
                const $result = $('#ba-result');
                const idea = $form.find('#ba-idea').val().trim();

                if (!idea) {
                    BA.showNotice('error', 'Por favor, insira uma ideia para o post.');
                    return;
                }

                $btn.addClass('loading').prop('disabled', true);
                $btn.find('.ba-btn-text').text(baAdmin.strings.generating);
                $result.removeClass('active success error');

                BA.showProgress(baAdmin.strings.generatingText, 15);
                BA.simulateProgress();

                $.ajax({
                    url: baAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ba_generate_post',
                        nonce: baAdmin.nonce,
                        idea: idea,
                        tone: $form.find('#ba-tone').val(),
                        length: $form.find('#ba-length').val(),
                        template: $form.find('#ba-template').val(),
                        status: $form.find('#ba-status').val()
                    },
                    success: function (response) {
                        BA.hideProgress();

                        if (response.success) {
                            BA.showResult('success', response.data);
                        } else {
                            BA.showResult('error', {
                                message: (response.data && response.data.message) ? response.data.message : baAdmin.strings.error
                            });
                        }
                    },
                    error: function (xhr, status, error) {
                        BA.hideProgress();
                        BA.showResult('error', {
                            message: 'Erro na requisição: ' + error
                        });
                    },
                    complete: function () {
                        $btn.removeClass('loading').prop('disabled', false);
                        $btn.find('.ba-btn-text').text('Gerar Post Completo Agora');
                    }
                });
            });
        },

        simulateProgress: function () {
            const steps = [
                { text: baAdmin.strings.generatingText, progress: 30, delay: 2500 },
                { text: baAdmin.strings.generatingImages, progress: 60, delay: 9000 },
                { text: baAdmin.strings.applyingTemplate, progress: 80, delay: 17000 },
                { text: baAdmin.strings.optimizingSeo, progress: 95, delay: 23000 }
            ];

            steps.forEach(function (step) {
                setTimeout(function () {
                    if ($('#ba-progress').hasClass('active')) {
                        BA.updateProgress(step.text, step.progress);
                    }
                }, step.delay);
            });
        },

        showProgress: function (text, progress) {
            const $progress = $('#ba-progress');
            $progress.addClass('active');
            this.updateProgress(text, progress);
        },

        updateProgress: function (text, progress) {
            $('#ba-progress .ba-progress-step').text(text);
            $('#ba-progress .ba-progress-fill').css('width', progress + '%');
            $('#ba-progress .ba-progress-text').text(progress + '%');
        },

        hideProgress: function () {
            const $progress = $('#ba-progress');
            this.updateProgress('Concluído!', 100);
            setTimeout(function () {
                $progress.removeClass('active');
            }, 600);
        },

        showResult: function (type, data) {
            const $result = $('#ba-result');
            $result.removeClass('success error').addClass('active ' + type);

            if (type === 'success') {
                $result.find('.ba-result-icon').html('<span class="dashicons dashicons-yes-alt" style="font-size:32px;width:32px;height:32px;color:#34d399;"></span>');
                $result.find('.ba-result-title').text(data.title || baAdmin.strings.success);
                $result.find('.ba-result-meta').show();

                $result.find('.meta-tokens').text(data.tokens_used ? Number(data.tokens_used).toLocaleString() : '0');
                $result.find('.meta-images').text(data.images_count || '0');
                $result.find('.meta-time').text((data.generation_time || '0') + 's');
                $result.find('.meta-status').text(data.status || 'draft');

                if (data.edit_url) {
                    $result.find('.ba-link-edit').attr('href', data.edit_url).show();
                }
                if (data.post_url) {
                    $result.find('.ba-link-view').attr('href', data.post_url).show();
                }
            } else {
                $result.find('.ba-result-icon').html('<span class="dashicons dashicons-dismiss" style="font-size:32px;width:32px;height:32px;color:#ef4444;"></span>');
                $result.find('.ba-result-title').html(
                    '<div style="color:#ffffff; font-weight:600; font-size:15px; margin-bottom:6px;">Falha na geração do artigo:</div>' +
                    '<div style="color:#fca5a5; font-size:13.5px; line-height:1.5;">' + (data.message || baAdmin.strings.error) + '</div>' +
                    '<div style="margin-top:14px;">' +
                        '<button type="button" class="ba-btn ba-btn-secondary ba-btn-sm ba-btn-diagnose" style="background:rgba(239,68,68,0.2) !important; border:1px solid #ef4444 !important; color:#ffffff !important; cursor:pointer;">' +
                            '<span class="ba-spinner"></span>' +
                            '<span class="dashicons dashicons-chart-bar" style="margin-right:4px;"></span>' +
                            '<span class="ba-btn-text">Analisar Cota & Limites da API Agora</span>' +
                        '</button>' +
                    '</div>'
                );
                $result.find('.ba-result-meta').hide();
                $result.find('.ba-link-edit, .ba-link-view').hide();
            }

            $('html, body').animate({
                scrollTop: $result.offset().top - 80
            }, 400);
        },

        /**
         * Bulk Add de Ideias (100+ tópicos)
         */
        bindBulkAdd: function () {
            // Contador de linhas em tempo real
            $(document).on('input', '#ba-bulk-ideas', function () {
                const text = $(this).val();
                const lines = text.split('\n').map(l => l.trim()).filter(l => l.length > 0);
                $('#ba-bulk-line-count').text(lines.length);
            });

            // Envio do formulário em massa
            $(document).on('submit', '#ba-bulk-form', function (e) {
                e.preventDefault();

                const $form = $(this);
                const $btn = $form.find('#ba-btn-bulk-add');
                const ideas = $form.find('#ba-bulk-ideas').val().trim();
                const template = $form.find('#ba-bulk-template').val();

                if (!ideas) {
                    BA.showNotice('error', 'Por favor, insira pelo menos um tema.');
                    return;
                }

                $btn.addClass('loading').prop('disabled', true);
                $btn.find('.ba-btn-text').text(baAdmin.strings.adding);

                $.ajax({
                    url: baAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ba_bulk_add_ideas',
                        nonce: baAdmin.nonce,
                        ideas: ideas,
                        template: template
                    },
                    success: function (response) {
                        if (response.success) {
                            BA.showNotice('success', response.data.message);
                            $form.find('#ba-bulk-ideas').val('');
                            $('#ba-bulk-line-count').text('0');

                            setTimeout(function () {
                                location.reload();
                            }, 1200);
                        } else {
                            BA.showNotice('error', (response.data && response.data.message) ? response.data.message : 'Erro ao adicionar ideias.');
                        }
                    },
                    error: function () {
                        BA.showNotice('error', 'Erro ao conectar ao servidor WordPress.');
                    },
                    complete: function () {
                        $btn.removeClass('loading').prop('disabled', false);
                        $btn.find('.ba-btn-text').text('Inserir Ideias na Fila');
                    }
                });
            });
        },

        /**
         * Ações da Fila (Remover, Limpar, Filtrar)
         */
        bindQueueActions: function () {
            // Remover item
            $(document).on('click', '.ba-remove-scheduled', function (e) {
                e.preventDefault();
                if (!confirm(baAdmin.strings.confirm)) {
                    return;
                }

                const $btn = $(this);
                const id = $btn.data('id');

                $.ajax({
                    url: baAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ba_remove_scheduled',
                        nonce: baAdmin.nonce,
                        id: id
                    },
                    success: function (response) {
                        if (response.success) {
                            $btn.closest('tr').fadeOut(300, function () {
                                $(this).remove();
                            });
                        }
                    }
                });
            });

            // Limpar concluídos
            $(document).on('click', '#ba-clear-done', function (e) {
                e.preventDefault();
                const status = $(this).data('status') || 'done';

                if (!confirm('Deseja limpar todos os itens concluídos da fila?')) {
                    return;
                }

                $.ajax({
                    url: baAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ba_clear_queue',
                        nonce: baAdmin.nonce,
                        status: status
                    },
                    success: function (response) {
                        if (response.success) {
                            location.reload();
                        }
                    }
                });
            });

            // Filtro por status
            $(document).on('change', '#ba-filter-queue', function () {
                const val = $(this).val();
                let url = new URL(window.location.href);
                if (val) {
                    url.searchParams.set('status_filter', val);
                } else {
                    url.searchParams.delete('status_filter');
                }
                url.searchParams.delete('paged');
                window.location.href = url.toString();
            });
        },

        /**
         * Teste de Conexão com a IA
         */
        bindTestConnection: function () {
            $(document).on('click', '#ba-test-connection', function (e) {
                e.preventDefault();

                const $btn = $(this);
                $btn.addClass('loading').prop('disabled', true);
                $btn.find('.ba-btn-text').text(baAdmin.strings.testing);

                const activeProvider = $('#ba_ai_provider').val() || 'gemini';
                const apiKey = $('#ba_api_key_' + activeProvider).val();
                const model = $('#ba_text_model').val();

                $.ajax({
                    url: baAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ba_test_connection',
                        nonce: baAdmin.nonce,
                        provider: activeProvider,
                        api_key: apiKey,
                        model: model
                    },
                    success: function (response) {
                        if (response.success) {
                            BA.showNotice('success', baAdmin.strings.testSuccess + ' ' + (response.data.message || ''));
                        } else {
                            BA.showNotice('error', baAdmin.strings.testError + ' ' + (response.data.message || ''));
                        }
                    },
                    error: function () {
                        BA.showNotice('error', 'Erro de resposta do servidor.');
                    },
                    complete: function () {
                        $btn.removeClass('loading').prop('disabled', false);
                        $btn.find('.ba-btn-text').text('Testar Conexão com Provedor Ativo');
                    }
                });
            });
        },

        /**
         * Abas de Provedores em Configurações
         */
        bindProviderTabs: function () {
            $(document).on('click', '.ba-provider-tab', function () {
                const target = $(this).data('target');
                $('.ba-provider-tab').removeClass('active');
                $(this).addClass('active');

                $('.ba-provider-content').hide();
                $('#' + target).fadeIn(200);
            });
        },

        /**
         * Atualização dinâmica de modelos ao mudar de provedor
         */
        bindProviderSelect: function () {
            $(document).on('change', '#ba_ai_provider', function () {
                const provider = $(this).val();

                // Sincroniza a aba ativa
                $('.ba-provider-tab').removeClass('active');
                $('.ba-provider-tab[data-target="tab-' + provider + '"]').addClass('active');
                $('.ba-provider-content').hide();
                $('#tab-' + provider).fadeIn(200);

                // Busca modelos do provedor via AJAX
                $.ajax({
                    url: baAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ba_get_provider_models',
                        nonce: baAdmin.nonce,
                        provider: provider
                    },
                    success: function (response) {
                        if (response.success && response.data.models) {
                            const $modelSelect = $('#ba_text_model');
                            $modelSelect.empty();
                            $.each(response.data.models, function (val, lbl) {
                                $modelSelect.append($('<option>', {
                                    value: val,
                                    text: lbl
                                }));
                            });
                        }
                    }
                });
            });
        },

        /**
         * Range slider
         */
        bindRangeSlider: function () {
            $(document).on('input', '.ba-range-input', function () {
                const val = $(this).val();
                $(this).siblings('.ba-range-value').text(val + ' palavras');
            });
        },

        /**
         * Toggle de visibilidade da API Key
         */
        bindApiKeyToggle: function () {
            $(document).on('click', '.ba-toggle-visibility', function () {
                const $input = $(this).siblings('input');
                const isPassword = $input.attr('type') === 'password';
                $input.attr('type', isPassword ? 'text' : 'password');
                const $icon = $(this).find('.dashicons');
                if (isPassword) {
                    $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
                } else {
                    $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
                }
            });
        },

        /**
         * Ações de logs e histórico (exclusão individual e limpeza)
         */
        bindLogActions: function () {
            // Excluir log individual
            $(document).on('click', '.ba-delete-log', function (e) {
                e.preventDefault();
                const $btn = $(this);
                const logId = $btn.data('id');
                const $row = $('#ba-log-row-' + logId);

                if (!confirm('Deseja realmente apagar este registro do histórico?')) {
                    return;
                }

                $btn.prop('disabled', true);

                $.ajax({
                    url: baAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ba_delete_log',
                        nonce: baAdmin.nonce,
                        id: logId
                    },
                    success: function (response) {
                        if (response.success) {
                            $row.fadeOut(300, function () {
                                $(this).remove();
                            });
                        } else {
                            alert((response.data && response.data.message) ? response.data.message : 'Erro ao excluir.');
                            $btn.prop('disabled', false);
                        }
                    },
                    error: function () {
                        alert('Erro de conexão ao excluir o registro.');
                        $btn.prop('disabled', false);
                    }
                });
            });

            // Limpar logs em massa (por status ou tudo)
            $(document).on('click', '.ba-clear-logs', function (e) {
                e.preventDefault();
                const $btn = $(this);
                const status = $btn.data('status') || 'all';
                const msg = ('error' === status)
                    ? 'Deseja realmente apagar todos os registros de falha do histórico?'
                    : 'Deseja realmente limpar todo o histórico de gerações?';

                if (!confirm(msg)) {
                    return;
                }

                $btn.prop('disabled', true);

                $.ajax({
                    url: baAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ba_clear_logs',
                        nonce: baAdmin.nonce,
                        status: status
                    },
                    success: function (response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert((response.data && response.data.message) ? response.data.message : 'Erro ao limpar histórico.');
                            $btn.prop('disabled', false);
                        }
                    },
                    error: function () {
                        alert('Erro de conexão ao limpar o histórico.');
                        $btn.prop('disabled', false);
                    }
                });
            });
        },

        /**
         * Diagnóstico completo de Limites e Cota da API
         */
        bindDiagnoseApi: function () {
            $(document).on('click', '.ba-btn-diagnose', function (e) {
                e.preventDefault();
                const $btn = $(this);
                const isSettings = $btn.closest('#ba-settings-form').length > 0;
                const $report = isSettings ? $('#ba-settings-diagnostic-report') : $('#ba-diagnostic-report');

                $btn.addClass('loading').prop('disabled', true);
                $btn.find('.ba-btn-text').text('Consultando API & Limites...');

                let provider = 'gemini';
                let apiKey = '';
                let model = '';

                if (isSettings) {
                    provider = $('#ba_ai_provider').val() || 'gemini';
                    apiKey = $('#ba_api_key_' + provider).val() || '';
                    model = $('#ba_text_model').val() || '';
                } else {
                    // Na tela de Novo Post, podemos pegar das configurações salvas
                    provider = $('#ba_ai_provider').val() || '';
                    apiKey = '';
                    model = $('#ba-tone').val() ? '' : '';
                }

                $report.slideUp(200);

                $.ajax({
                    url: baAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ba_diagnose_api',
                        nonce: baAdmin.nonce,
                        provider: provider,
                        api_key: apiKey,
                        model: model
                    },
                    success: function (response) {
                        if (response.success && response.data) {
                            BA.renderDiagnosticReport($report, response.data);
                        } else {
                            const err = (response.data && response.data.message) ? response.data.message : 'Falha ao executar diagnóstico.';
                            BA.showNotice('error', err);
                        }
                    },
                    error: function () {
                        BA.showNotice('error', 'Erro ao conectar ao servidor WordPress.');
                    },
                    complete: function () {
                        $btn.removeClass('loading').prop('disabled', false);
                        $btn.find('.ba-btn-text').text(isSettings ? 'Analisar Limites & Cota da API' : 'Analisar Limites da API');
                    }
                });
            });

            // Toggle para ver retorno JSON bruto da API
            $(document).on('click', '.ba-toggle-raw-json', function (e) {
                e.preventDefault();
                const $raw = $(this).siblings('.ba-raw-json-content');
                $raw.slideToggle(200);
            });
        },

        /**
         * Renderiza o relatório visual do diagnóstico
         */
        renderDiagnosticReport: function ($container, data) {
            let badgeClass = 'ok';
            let badgeText = 'Cota Liberada';

            if (data.quota_status === 'zero_quota') {
                badgeClass = 'zero_quota';
                badgeText = 'Cota ZERO (limit: 0)';
            } else if (data.quota_status === 'rate_limited' || data.quota_status === 'limited') {
                badgeClass = 'rate_limited';
                badgeText = 'Limite Atingido';
            } else if (data.quota_status === 'blocked' || data.auth_status === 'invalid') {
                badgeClass = 'invalid';
                badgeText = 'Chave Inválida / Bloqueada';
            }

            let modelsHtml = '';
            if (data.models && data.models.length > 0) {
                modelsHtml += '<div class="ba-diag-models-title">Modelos Disponíveis no seu Projeto (' + data.models.length + '):</div>';
                modelsHtml += '<div class="ba-diag-chips">';
                data.models.forEach(function (m) {
                    const isTested = (data.tested_model && m.id === data.tested_model);
                    modelsHtml += '<span class="ba-diag-chip ' + (isTested ? 'active-model' : '') + '">' + m.id + '</span>';
                });
                modelsHtml += '</div>';
            }

            let rawHtml = '';
            if (data.raw) {
                rawHtml = '<div class="ba-diag-raw">' +
                    '<a href="#" class="ba-toggle-raw-json">🔍 Ver Retorno Técnico Original da API (JSON) ▾</a>' +
                    '<pre class="ba-raw-json-content">' + $('<div>').text(JSON.stringify(data.raw, null, 2)).html() + '</pre>' +
                    '</div>';
            }

            const html = '' +
                '<div class="ba-diag-header">' +
                    '<div class="ba-diag-title-wrap">' +
                        '<span class="ba-diag-badge ' + badgeClass + '">' + badgeText + '</span>' +
                        '<strong>' + (data.provider_name || 'IA') + '</strong>' +
                    '</div>' +
                    '<div class="ba-diag-meta">' +
                        '<span>Chave: <code>' + (data.api_key || '***') + '</code></span>' +
                        '<span>Latência: <strong>' + (data.latency_ms || 0) + 'ms</strong></span>' +
                    '</div>' +
                '</div>' +
                '<div class="ba-diag-body">' +
                    '<h4 class="ba-diag-headline">' + (data.title || 'Resultado do Diagnóstico') + '</h4>' +
                    '<p class="ba-diag-text">' + (data.message || '') + '</p>' +
                    modelsHtml +
                    rawHtml +
                '</div>';

            $container.html(html).slideDown(300);
            $('html, body').animate({
                scrollTop: $container.offset().top - 80
            }, 400);
        },

        /**
         * Exibe notificação temporária
         */
        showNotice: function (type, message) {
            const iconClass = type === 'success' ? 'dashicons-yes-alt' : 'dashicons-warning';
            const alertClass = type === 'success' ? 'ba-alert-success' : (type === 'error' ? 'ba-alert-error' : 'ba-alert-warning');
            const inlineStyle = type === 'success'
                ? 'background-color: #0d2215 !important; border: 1px solid #065f46 !important; border-left: 5px solid #B4D443 !important; color: #ffffff !important; padding: 14px 18px !important; display: flex !important; align-items: center !important; gap: 12px !important; border-radius: 6px !important; margin-bottom: 20px !important;'
                : (type === 'error'
                    ? 'background-color: #281215 !important; border: 1px solid #7f1d1d !important; border-left: 5px solid #ef4444 !important; color: #ffffff !important; padding: 14px 18px !important; display: flex !important; align-items: center !important; gap: 12px !important; border-radius: 6px !important; margin-bottom: 20px !important;'
                    : 'background-color: #271805 !important; border: 1px solid #78350f !important; border-left: 5px solid #f59e0b !important; color: #ffffff !important; padding: 14px 18px !important; display: flex !important; align-items: center !important; gap: 12px !important; border-radius: 6px !important; margin-bottom: 20px !important;');
            const $notice = $('<div class="ba-alert ' + alertClass + '" style="' + inlineStyle + '">' +
                '<span class="dashicons ' + iconClass + '"></span>' +
                '<span style="color: #ffffff !important; font-size: 13.5px !important; line-height: 1.5 !important;">' + message + '</span>' +
                '</div>');

            $('.ba-notices-area').prepend($notice);

            setTimeout(function () {
                $notice.fadeOut(300, function () {
                    $(this).remove();
                });
            }, 6000);
        }
    };

    $(document).ready(function () {
        BA.init();
    });

})(jQuery);
