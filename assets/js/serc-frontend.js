jQuery(function ($) {
  function cleanDigits(str) { return (str || '').replace(/\D+/g, ''); }
  function cleanPlate(str) { return (str || '').toUpperCase().replace(/[^A-Z0-9]/g, ''); }
  function cleanChassi(str) { return (str || '').toUpperCase().replace(/[^A-HJ-NPR-Z0-9]/g, ''); }
  function base64ToBlobUrl(dataUrlOrBase64) {
    var s = dataUrlOrBase64 || '';
    if (s.indexOf('data:') === 0) return s;
    var b64 = s.replace(/^data:.*;base64,/, '');
    try { var bstr = atob(b64); } catch (e) { return null; }
    var n = bstr.length, u8 = new Uint8Array(n);
    for (var i = 0; i < n; i++) u8[i] = bstr.charCodeAt(i);
    var blob = new Blob([u8], { type: 'application/pdf' });
    return URL.createObjectURL(blob);
  }
  function showConfirm($form) {
    var $ov = $('<div class="serc-modal-overlay" />').attr('style', 'position:fixed;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;z-index:9999');
    var $md = $('<div class="serc-modal" />').attr('style', 'background:#fff;border-radius:8px;padding:20px;max-width:360px;width:90%;box-shadow:0 10px 30px rgba(0,0,0,.2);text-align:center');
    var $txt = $('<div>Deseja realmente realizar a consulta?</div>').attr('style', 'margin-bottom:16px;font-size:14px;color:#333');
    var $ok = $('<button type="button">Confirmar</button>').attr('style', 'margin-right:8px;padding:8px 12px;background:#2271b1;color:#fff;border:none;border-radius:4px;cursor:pointer');
    var $cancel = $('<button type="button">Cancelar</button>').attr('style', 'padding:8px 12px;background:#eee;color:#333;border:none;border-radius:4px;cursor:pointer');
    $md.append($txt, $('<div />').append($ok, $cancel));
    $ov.append($md);
    $('body').append($ov);
    function close() { $ov.remove(); }
    $cancel.on('click', function () { close(); });
    $ov.on('click', function (e) { if (e.target === this) close(); });
    $(document).on('keydown.sercModal', function (e) { if (e.key === 'Escape') { $(document).off('keydown.sercModal'); close(); } });
    $ok.on('click', function () { $(document).off('keydown.sercModal'); close(); handleSubmit($form); });
  }

  function handleSubmit($form) {
    var type = $form.data('type');
    var payload = { action: 'serc_lookup', nonce: serc_ajax.nonce, type: type };
    if (type === 'cnpj') {
      payload.cnpj = cleanDigits($form.find('[name="cnpj"]').val());
    } else if (type === 'cpf' || type === 'cpf_renda' || type === 'ic_cnh' || type === 'bin_nacional' || type === 'serasa_premium' || type === 'ic_basico_score' || type === 'scpc_boa_vista' || type === 'bacen' || type === 'quod' || type === 'spc_brasil_cenprot' || type === 'spc_brasil_serasa' || type === 'dividas_bancrias_cpf' || type === 'cadastrais_score_dividas' || type === 'cadastrais_score_dividas_cp' || type === 'scr_bacen_score' || type === 'protesto_nacional_cenprot' || type === 'r_acoes_e_processos_judiciais' || type === 'dossie_juridico_cpf' || type === 'certidao_nacional_debitos_trabalhistas') {
      payload.cpf = cleanDigits($form.find('[name="cpf"]').val());
    } else if (type === 'dossie_juridico') {
      var docType = $form.find('[name="doc_type"]:checked').val();
      var docVal = $form.find('[name="document"]').val();
      if (docType === 'cpf') payload.cpf = cleanDigits(docVal);
      else payload.cnpj = cleanDigits(docVal);
      payload.doc_type = docType;
    } else if (type === 'ic_nome') {
      payload.name = $form.find('[name="name"]').val();
      payload.state = ($form.find('[name="state"]').val() || '').toUpperCase().replace(/[^A-Z]/g, '');
    } else if (type === 'ic_telefone') {
      payload.ddd = cleanDigits($form.find('[name="ddd"]').val());
      payload.telefone = cleanDigits($form.find('[name="telefone"]').val());
      payload.state = ($form.find('[name="state"]').val() || '').toUpperCase().replace(/[^A-Z]/g, '');
    } else if (type === 'crlv' || type === 'proprietario_placa') {
      payload.placa = cleanPlate($form.find('[name="placa"]').val());
    } else if (type === 'renainf') {
      payload.placa = cleanPlate($form.find('[name="placa"]').val());
      payload.renavam = cleanDigits($form.find('[name="renavam"]').val());
    } else if (type === 'ic_placa' || type === 'leilao_score_perda_total' || type === 'historico_roubo_furto' || type === 'indice_risco_veicular' || type === 'licenciamento_anterior' || type === 'ic_proprietario_atual' || type === 'gravame_detalhamento' || type === 'renajud' || type === 'renainf_placa' || type === 'sinistro') {
      payload.placa = cleanPlate($form.find('[name="placa"]').val());
    } else if (type === 'gravame') {
      payload.chassi = cleanChassi($form.find('[name="chassi"]').val());
    } else if (type === 'laudo_veicular') {
      var chassi = cleanChassi($form.find('[name="chassi"]').val());
      var placa = cleanPlate($form.find('[name="placa"]').val());
      if (chassi) payload.chassi = chassi;
      if (placa) payload.placa = placa;
    } else if (type === 'scpc_bv_plus_v2' || type === 'srs_premium') {
      payload.cpf = cleanDigits($form.find('[name="cpf"]').val());
    } else if (type === 'agregados_basica_propria') {
      payload.param = $form.find('[name="param"]').val();
    } else if (type === 'bin_estadual') {
      payload.estado = ($form.find('[name="estado"]').val() || '').toUpperCase().replace(/[^A-Z]/g, '');
    } else if (type === 'foto_leilao') {
      payload.leilaoId = cleanDigits($form.find('[name="leilaoId"]').val());
    } else if (type === 'leilao') {
      payload.filtro = $form.find('[name="filtro"]').val();
    } else if (type === 'recall') {
      payload.modelo = $form.find('[name="modelo"]').val();
    } else if (type === 'fipe') {
      payload.marca = $form.find('[name="marca"]').val();
      payload.modelo = $form.find('[name="modelo"]').val();
      payload.ano = cleanDigits($form.find('[name="ano"]').val());
    }
    var $result = $form.find('.serc-result');
    var $submit = $form.find('button[type="submit"]');
    $submit.prop('disabled', true).hide();
    $result.html('<span style="color:#555">Consultando...</span>');

    console.log('[SERC] Sending AJAX request to:', serc_ajax.ajax_url);
    console.log('[SERC] Payload:', payload);

    $.post(serc_ajax.ajax_url, payload).done(function (resp) {
      console.log('[SERC] Response received:', resp);

      if (resp && resp.success) {
        console.log('[SERC] Success! Data:', resp.data);
        var saldo = (typeof resp.data.quota !== 'undefined') ? parseFloat(resp.data.quota).toFixed(2) : '0.00';
        var deb = (typeof resp.data.debited !== 'undefined') ? parseFloat(resp.data.debited).toFixed(2) : null;
        var msg = '<span style="color:green;font-weight:bold;">Consulta realizada com sucesso.</span>';
        var det = '<small>' + (deb !== null ? ('Crédito debitado: ' + deb + ' | ') : '') + 'Saldo restante: ' + saldo + '</small>';
        $result.html(msg + '<br>' + det);
        var dl = resp.data && resp.data.result && resp.data.result.download_url;
        if (dl) {
          $result.append('<p><a class="button" href="' + dl + '">download</a></p>');
        } else {
          var pdfB64 = (resp.data && resp.data.result && resp.data.result.pdfBase64) || (resp.data && resp.data.pdfBase64);
          if (pdfB64) {
            var url = base64ToBlobUrl(pdfB64);
            if (url) {
              console.log('[SERC] PDF blob URL created:', url);
              $result.append('<p><a class="button" href="' + url + '" download="consulta.pdf">download</a></p>');
            }
          }
        }
        var ul = resp.data && resp.data.result && resp.data.result.upload_log;
        if (ul && ul.meta) {
          console.group('APIFull Storage Upload');
          console.log('start', new Date(ul.meta.start_ms).toISOString());
          console.log('end', new Date(ul.meta.end_ms).toISOString());
          console.log('duration_ms', ul.meta.duration_ms);
          console.log('size_bytes', ul.meta.size_bytes);
          console.log('status', ul.status);
          if (ul.code) console.log('code', ul.code);
          if (ul.message) console.warn('message', ul.message);
          console.groupEnd();
        } else {
          console.warn('APIFull Storage Upload: no upload_log or no PDF present.');
        }
      } else {
        console.error('[SERC] Error response:', resp);
        var d = resp && resp.data;
        var noQuota = (d === 'no_quota') || (d && d.code === 'no_quota');
        if (noQuota) {
          var url = (d && d.purchase_url) ? d.purchase_url : '';
          var link = url ? (' <a href="' + url + '" target="_blank">Adquira um plano</a>.') : ' Adquira um plano.';
          $result.html('<span style="color:red;font-weight:bold;">Você não possui créditos para esta consulta.</span><br>' + link);
        } else {
          $result.html('<span style="color:red;font-weight:bold;">Erro ao processar. Tente novamente.</span>');
        }
      }
      $submit.prop('disabled', false).show();
    }).fail(function (xhr, status, error) {
      console.error('[SERC] AJAX request failed!');
      console.error('[SERC] Status:', status);
      console.error('[SERC] Error:', error);
      console.error('[SERC] Response:', xhr.responseText);
      $result.html('<span style="color:red;font-weight:bold;">Erro ao processar. Tente novamente.</span>');
      $submit.prop('disabled', false).show();
    });
  }

  $(document).on('submit', '.serc-form', function (e) {
    e.preventDefault();
    showConfirm($(this));
  });

  // Compat: formulário antigo CNPJ
  $('#serc-cnpj-form').on('submit', function (e) {
    e.preventDefault();
    showConfirm($(this).addClass('serc-form').attr('data-type', 'cnpj'));
  });

  // Apply masks if plugin is available
  if ($.fn && $.fn.mask) {
    // CPF and CNPJ masks
    $('input.cpf').mask('000.000.000-00', { reverse: true });
    $('input.cnpj').mask('00.000.000/0000-00', { reverse: true });

    // Placa (formato Mercosul ABC1D23 ou antigo ABC-1234)
    $('input[name="placa"]').mask('SSS0S00', {
      translation: {
        'S': { pattern: /[A-Za-z]/ },
        '0': { pattern: /[0-9]/ }
      },
      onKeyPress: function (val, e, field, options) {
        field.val(val.toUpperCase());
      }
    });

    // Chassi (17 caracteres alfanuméricos, sem I, O, Q)
    $('input[name="chassi"]').mask('AAAAAAAAAAAAAAAAA', {
      translation: {
        'A': { pattern: /[A-HJ-NPR-Z0-9]/ }
      },
      onKeyPress: function (val, e, field, options) {
        field.val(val.toUpperCase());
      }
    });

    // RENAVAM (9 obrigatórios + 2 opcionais)
    $('input[name="renavam"]').mask('000000000ZZ', {
      translation: { 'Z': { pattern: /[0-9]/, optional: true } }
    });

    // Telefone (8 ou 9 dígitos)
    $('input[name="telefone"]').mask('00000-0000');

    // DDD (2 dígitos)
    $('input[name="ddd"]').mask('00');

    // UF/Estado (2 letras)
    $('input[name="state"], input[name="estado"]').mask('SS', {
      translation: {
        'S': { pattern: /[A-Za-z]/ }
      },
      onKeyPress: function (val, e, field, options) {
        field.val(val.toUpperCase());
      }
    });

    // Ano (4 dígitos)
    $('input[name="ano"]').mask('0000');

    // ID Leilão (números)
    $('input[name="leilaoId"]').mask('000000000');

    // Adaptive CPF/CNPJ mask for fields that accept both formats
    // Automatically switches based on input length: CPF (11 digits) or CNPJ (14 digits)
    var $dualFormatInputs = $('input[name="document"]');
    if ($dualFormatInputs.length) {
      $dualFormatInputs.each(function () {
        var $input = $(this);

        // Always set maxlength to 18 (CNPJ formatted length) to allow switching
        $input.attr('maxlength', '18');

        // Function to apply the correct mask based on input length
        var applyAdaptiveMask = function () {
          var cleanValue = $input.val().replace(/\D/g, '');
          $input.unmask();

          // If it has 11 digits or less, apply CPF mask
          // If it has more than 11 digits, apply CNPJ mask
          if (cleanValue.length <= 11) {
            $input.mask('000.000.000-00', { reverse: true });
          } else {
            $input.mask('00.000.000/0000-00', { reverse: true });
          }

          // Ensure maxlength stays at 18 (masks can override this)
          $input.attr('maxlength', '18');
        };

        // Apply mask on input
        $input.on('input', applyAdaptiveMask);

        // Apply initial mask
        applyAdaptiveMask();
      });
    }
  }

  // AJAX-based Dashboard Navigation
  // Load views dynamically without full page reload
  $(document).ready(function () {
    if ($('.dashboard-wrapper').length > 0) {

      console.log('[SERC Navigation] Dashboard detected, AJAX navigation enabled');

      // Function to update main sidebar and mobile nav active state
      function updateSidebarActiveState(view) {
        // Map views to sidebar link selectors
        var viewMap = {
          'dashboard': 'dashboard',
          'category': 'category',
          'query': 'category',  // Query is part of Consultas
          'consulta': 'category',
          'history': 'history'
        };

        var targetView = viewMap[view] || 'dashboard';

        // Remove active from all nav links and mobile nav items
        $('.nav-menu .nav-link, .mobile-nav-item').removeClass('active');

        // Add active to correct link based on view
        $('.nav-menu .nav-link, .mobile-nav-item').each(function () {
          var href = $(this).attr('href') || '';

          // Skip placeholder links (href="#" or empty)
          if (href === '#' || href === '' || href === window.location.href + '#') {
            return;
          }

          // Match dashboard specifically
          if (targetView === 'dashboard') {
            // Only match links with explicit view=dashboard or the base consultas URL without view param
            if (href.indexOf('view=dashboard') !== -1 ||
              (href.indexOf('/consultas') !== -1 && href.indexOf('view=') === -1)) {
              $(this).addClass('active');
            }
          } else if (href.indexOf('view=' + targetView) !== -1) {
            $(this).addClass('active');
          }
        });
      }

      // Function to load view via AJAX
      function loadView(url) {
        console.log('[SERC Navigation] Loading view:', url);

        var urlObj = new URL(url, window.location.origin);
        var params = new URLSearchParams(urlObj.search);

        // Show loading state
        $('.area-content').css('opacity', '0.5');

        // Make AJAX request
        $.ajax({
          url: serc_ajax.ajax_url,
          type: 'GET',
          data: {
            action: 'serc_load_view',
            view: params.get('view') || 'dashboard',
            type: params.get('type') || '',
            integration: params.get('integration') || ''
          },
          success: function (response) {
            console.log('[SERC Navigation] Response received:', response);

            if (response.success && response.data && response.data.html) {
              // Replace content
              $('.area-content').html(response.data.html);

              // Adjust grid layout based on view type
              if (response.data.view === 'category') {
                $('.area-content').css('grid-template-columns', '1fr');
              } else {
                $('.area-content').css('grid-template-columns', '');
              }

              // Update sidebar active state
              updateSidebarActiveState(response.data.view);

              // Update URL without reload
              window.history.pushState({ view: response.data.view }, '', url);

              // Restore opacity
              $('.area-content').css('opacity', '1');

              console.log('[SERC Navigation] Content updated successfully');
            } else {
              console.warn('[SERC Navigation] Invalid response, falling back to page reload');
              // Fallback to full page load
              window.location.href = url;
            }
          },
          error: function (xhr, status, error) {
            console.error('[SERC Navigation] AJAX error:', status, error);
            // Fallback to full page load on error
            window.location.href = url;
          }
        });
      }

      // Intercept navigation clicks
      $(document).on('click', 'a[href*="view="], .nav-link[href*="?"], .sidebar-tab[href*="?"], .action-card[href*="?"], .mobile-nav-item[href*="?"]', function (e) {
        var href = $(this).attr('href');

        console.log('[SERC Navigation] Link clicked:', href);

        // Only intercept if it's a dashboard navigation link
        if (href && href !== '#' && href !== 'javascript:void(0)' &&
          (href.indexOf('view=') !== -1 || href.indexOf('?') === 0)) {
          e.preventDefault();
          loadView(href);
          return false;
        }
      });

      // Handle browser back/forward buttons
      window.addEventListener('popstate', function (event) {
        console.log('[SERC Navigation] Browser navigation detected');
        if (event.state && event.state.view) {
          loadView(window.location.href);
        }
      });
    }
  });

});
