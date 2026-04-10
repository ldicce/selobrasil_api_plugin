window.sercToggleAccountSheet = function() {
  var sheet = document.getElementById('sercAccountSheet');
  var overlay = document.getElementById('sercAccountSheetOverlay');
  if (sheet && overlay) {
    sheet.classList.toggle('active');
    overlay.classList.toggle('active');
  }
};

jQuery(function ($) {
  function cleanDigits(str) { return (str || '').replace(/\D+/g, ''); }
  function cleanPlate(str) { return (str || '').toUpperCase().replace(/[^A-Z0-9]/g, ''); }
  function cleanChassi(str) { return (str || '').toUpperCase().replace(/[^A-HJ-NPR-Z0-9]/g, ''); }
  function base64ToBlobUrl(dataUrlOrBase64) {
    var s = dataUrlOrBase64 || '';
    if (s.indexOf('data:') === 0) return s;
    // Remove data URI prefix if present, then strip whitespace/newlines
    var b64 = s.replace(/^data:.*;base64,/, '').replace(/[\s\r\n]+/g, '');
    // Fix padding if missing
    var pad = b64.length % 4;
    if (pad > 0) {
      b64 += '===='.slice(pad);
    }
    try { var bstr = atob(b64); } catch (e) { console.error('[SERC] base64 decode error:', e); return null; }
    var n = bstr.length, u8 = new Uint8Array(n);
    for (var i = 0; i < n; i++) u8[i] = bstr.charCodeAt(i);
    var blob = new Blob([u8], { type: 'application/pdf' });
    return URL.createObjectURL(blob);
  }
  function showConfirm($form) {
    var isDark = $('body').hasClass('dark-theme') || $('html').hasClass('dark') || document.documentElement.getAttribute('data-theme') === 'dark';
    var bgCard = isDark ? '#1E1E1E' : '#FFFFFF';
    var textColor = isDark ? '#FFFFFF' : '#111111';
    var textMuted = isDark ? '#AAAAAA' : '#666666';
    var borderColor = isDark ? '#333333' : '#E5E7EB';
    var btnBg = isDark ? '#2A2A2A' : '#F3F4F6';
    
    var $ov = $('<div class="serc-modal-overlay" />').attr('style', 'position:fixed;inset:0;background:rgba(0,0,0,.6);display:flex;align-items:center;justify-content:center;z-index:9999;backdrop-filter:blur(4px);');
    var $md = $('<div class="serc-modal" />').attr('style', 'background:' + bgCard + ';border-radius:16px;padding:30px 24px;max-width:380px;width:92%;box-shadow:0 20px 40px rgba(0,0,0,.3);text-align:center;border:1px solid ' + borderColor);
    var $iconContainer = $('<div />').attr('style', 'background:rgba(0,156,59,.1);width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;color:var(--primary-green, #009c3b)');
    var $icon = $('<i data-lucide="help-circle" style="width:32px;height:32px;stroke-width:2px;"></i>');
    $iconContainer.append($icon);
    
    var $txt = $('<div><h3 style="margin:0 0 12px;font-size:20px;font-weight:600;color:' + textColor + ';">Confirmar Consulta</h3><p style="margin:0 0 24px;font-size:14px;color:' + textMuted + ';line-height:1.5;">Deseja realmente realizar esta consulta? Seu saldo de créditos será atualizado ao concluir.</p></div>');
    
    var $ok = $('<button type="button" class="serc-modal-btn">Sim, Consultar</button>').attr('style', 'display:inline-flex;align-items:center;justify-content:center;gap:8px;flex:1;padding:12px 16px;background:var(--primary-green, #009c3b);color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:500;font-size:14px;transition:background 0.2s;height:44px;');
    var $cancel = $('<button type="button" class="serc-modal-btn">Cancelar</button>').attr('style', 'display:inline-flex;align-items:center;justify-content:center;gap:8px;flex:1;padding:12px 16px;background:' + btnBg + ';color:' + textColor + ';border:1px solid ' + borderColor + ';border-radius:8px;cursor:pointer;font-weight:500;font-size:14px;transition:background 0.2s;height:44px;');
    var $btnWrapper = $('<div />').attr('style', 'display:flex;justify-content:center;gap:12px;width:100%').append($cancel, $ok);
    
    $md.append($iconContainer, $txt, $btnWrapper);
    $ov.append($md);
    $('body').append($ov);
    if (window.lucide) { window.lucide.createIcons({root: $md[0]}); }
    
    function close() { $ov.remove(); }
    $cancel.on('click', function () { close(); });
    // Disable click-outside to avoid accidental dismiss during loading
    $(document).on('keydown.sercModal', function (e) { if (e.key === 'Escape') { $(document).off('keydown.sercModal'); close(); } });
    
    $ok.on('click', function () {
      $(document).off('keydown.sercModal');
      // Set loading state on modal button
      $ok.prop('disabled', true).html('<i data-lucide="loader-2" class="lucide-spin" style="width:18px;height:18px;"></i> Processando...');
      $cancel.prop('disabled', true).css('opacity', '0.5');
      if (window.lucide) { window.lucide.createIcons({root: $ok[0]}); }
      
      // Call handleSubmit with completion callback
      handleSubmit($form, function() {
        close();
      });
    });
  }

  // ── User Avatar Dropdown ──────────────────────────────────────────────────
  const $userBtn      = $('#serc-user-menu-btn');
  const $userDropdown = $('#serc-user-dropdown');

  $userBtn.on('click', function(e) {
    e.stopPropagation();
    const isOpen = $userDropdown.hasClass('is-open');
    $userDropdown.toggleClass('is-open', !isOpen);
    $userBtn.attr('aria-expanded', String(!isOpen));
    // Re-render Lucide icons inside the freshly visible dropdown
    if (!isOpen && typeof lucide !== 'undefined') {
      lucide.createIcons();
    }
  });

  // Close on outside click
  $(document).on('click.userMenu', function() {
    $userDropdown.removeClass('is-open');
    $userBtn.attr('aria-expanded', 'false');
  });

  // Close on Escape
  $(document).on('keydown.userMenu', function(e) {
    if (e.key === 'Escape') {
      $userDropdown.removeClass('is-open');
      $userBtn.attr('aria-expanded', 'false');
    }
  });
  // ─────────────────────────────────────────────────────────────────────────

  function handleSubmit($form, onComplete) {
    var type = $form.data('type');
    var payload = { action: 'serc_lookup', nonce: serc_ajax.nonce, type: type };
    // Generic Payload Builder - maps all form inputs to payload
    $form.find('input, select, textarea').each(function () {
      var $input = $(this);
      var name = $input.attr('name');
      var val = $input.val();

      if (!name || $input.attr('type') === 'submit') return;

      // Handle checkbox/radio
      if (($input.attr('type') === 'radio' || $input.attr('type') === 'checkbox') && !$input.is(':checked')) {
        return;
      }

      // Apply cleaning logic based on field names
      if (name === 'cpf' || name === 'cnpj' || name === 'document' || name === 'phone' || name === 'telefone' || name === 'ddd' || name === 'renavam' || name === 'cep' || name === 'ano' || name === 'leilaoId') {
        payload[name] = cleanDigits(val);
      } else if (name === 'placa') {
        payload[name] = cleanPlate(val);
      } else if (name === 'chassi') {
        payload[name] = cleanChassi(val);
      } else if (name === 'state' || name === 'estado' || name === 'uf') {
        payload[name] = (val || '').toUpperCase().replace(/[^A-Z]/g, '');
      } else {
        // Default: raw value (name, model, brand, etc)
        payload[name] = val;
      }
    });
    var $result = $form.find('.serc-result');
    var $submit = $form.find('button[type="submit"]');
    
    // Clear previous results and buttons
    $result.empty();
    $form.find('.serc-btn-download-wrapper').remove();
    
    // Save original button text and show loading
    var originalBtnText = $submit.html();
    $submit.prop('disabled', true).html('<i data-lucide="loader-2" class="lucide-spin"></i> Processando...');
    if (window.lucide) { window.lucide.createIcons(); }

    console.log('[SERC] Sending AJAX request to:', serc_ajax.ajax_url);
    console.log('[SERC] Payload:', payload);

    $.post(serc_ajax.ajax_url, payload).done(function (resp) {
      console.log('[SERC] Response received:', resp);

      if (resp && resp.success) {
        console.log('[SERC] Success! Data:', resp.data);
        
        // Invalidate view cache since a query was performed (balances/history changed)
        window.sercViewCache = {};

        // Remove text confirmation as requested by user
        $result.empty();
        
        var dl = resp.data && resp.data.result && resp.data.result.download_url;
        var btnHtml = '';
        if (dl) {
          btnHtml = '<a class="action-btn" href="' + dl + '" style="margin-left: 10px; display: inline-flex; align-items: center; justify-content: center; height: 44px; padding: 0 20px; background: var(--primary-green); color: #fff; border-radius: 6px; text-decoration: none; font-weight: 500;"><i data-lucide="download" style="margin-right:5px;"></i> Baixar PDF</a>';
        } else {
          // Fallback: check for pdfBase64 directly from the server response
          var pdfB64 = (resp.data && resp.data.result && resp.data.result.pdfBase64) || (resp.data && resp.data.pdfBase64);
          if (pdfB64) {
            var url = base64ToBlobUrl(pdfB64);
            if (url) {
              console.log('[SERC] PDF blob URL created:', url);
              btnHtml = '<a class="action-btn" href="' + url + '" download="consulta.pdf" style="margin-left: 10px; display: inline-flex; align-items: center; justify-content: center; height: 44px; padding: 0 20px; background: var(--primary-green); color: #fff; border-radius: 6px; text-decoration: none; font-weight: 500;"><i data-lucide="download" style="margin-right:5px;"></i> Baixar PDF</a>';
            }
          }
        }
        
        if (btnHtml !== '') {
            $submit.after('<span class="serc-btn-download-wrapper">' + btnHtml + '</span>');
            if (window.lucide) { window.lucide.createIcons(); }
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
        var errorCode = (typeof d === 'string') ? d : (d && d.code ? d.code : '');
        if (errorCode === 'no_quota') {
          var url = (d && d.purchase_url) ? d.purchase_url : '';
          var link = url ? (' <a href="' + url + '" target="_blank">Adquira um plano</a>.') : ' Adquira um plano.';
          $result.html('<span style="color:red;font-weight:bold;">Você não possui créditos para esta consulta.</span><br>' + link);
        } else if (errorCode === 'api_error' || errorCode === 'api_timeout') {
          $result.html('<span style="color:red;font-weight:bold;">Houve um problema ao consultar. Tente novamente mais tarde.</span>');
        } else if (errorCode === 'invalid_input') {
          $result.html('<span style="color:red;font-weight:bold;">Dados de entrada inválidos. Verifique e tente novamente.</span>');
        } else {
          $result.html('<span style="color:red;font-weight:bold;">Erro ao processar. Tente novamente.</span>');
        }
      }
      $submit.prop('disabled', false).html(originalBtnText);
      if (typeof onComplete === 'function') { onComplete(); }
    }).fail(function (xhr, status, error) {
      console.error('[SERC] AJAX request failed!');
      console.error('[SERC] Status:', status);
      console.error('[SERC] Error:', error);
      console.error('[SERC] Response:', xhr.responseText);
      $result.html('<span style="color:red;font-weight:bold;">Erro ao processar. Tente novamente.</span>');
      $submit.prop('disabled', false).html(originalBtnText);
      if (typeof onComplete === 'function') { onComplete(); }
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

  // Logout confirmation
  $(document).on('click', '.serc-logout-link', function(e) {
    e.preventDefault();
    var logoutUrl = $(this).attr('href');
    
    var $ov = $('<div class="serc-modal-overlay" />').attr('style', 'position:fixed;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;z-index:9999');
    var $md = $('<div class="serc-modal" />').attr('style', 'background:#fff;border-radius:8px;padding:20px;max-width:360px;width:90%;box-shadow:0 10px 30px rgba(0,0,0,.2);text-align:center');
    var $txt = $('<div>Tem certeza que deseja sair?</div>').attr('style', 'margin-bottom:16px;font-size:16px;color:#333;font-weight:500;');
    var $ok = $('<a href="' + logoutUrl + '" class="btn-confirm">Confirmar</a>').attr('style', 'display:inline-block;margin-right:8px;padding:8px 16px;background:#e74c3c;color:#fff;text-decoration:none;border-radius:4px;cursor:pointer;');
    var $cancel = $('<button type="button">Cancelar</button>').attr('style', 'padding:8px 16px;background:#eee;color:#333;border:none;border-radius:4px;cursor:pointer');
    
    $md.append($txt, $('<div style="margin-top:20px;" />').append($ok, $cancel));
    $ov.append($md);
    $('body').append($ov);
    
    function close() { $ov.remove(); }
    $cancel.on('click', function () { close(); });
    $ov.on('click', function (e) { if (e.target === this) close(); });
    $(document).on('keydown.sercLogoutModal', function (e) { if (e.key === 'Escape') { $(document).off('keydown.sercLogoutModal'); close(); } });
    $ok.on('click', function() { $(document).off('keydown.sercLogoutModal'); });
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

      // Global View Cache
      window.sercViewCache = {};

      // =============================================
      // Dark / Light Mode Toggle
      // =============================================
      (function () {
        var $wrapper = $('.dashboard-wrapper');
        var $btn = $('#serc-theme-toggle');
        var $icon = $('#serc-theme-icon');
        var STORAGE_KEY = 'serc-theme';

        function applyTheme(theme, save) {
          if (theme === 'dark') {
            $wrapper.addClass('dark-mode');
            $icon.removeClass('ph-moon').addClass('ph-sun');
            $btn.attr('title', 'Alternar para modo claro').attr('aria-label', 'Alternar para modo claro');
          } else {
            $wrapper.removeClass('dark-mode');
            $icon.removeClass('ph-sun').addClass('ph-moon');
            $btn.attr('title', 'Alternar para modo escuro').attr('aria-label', 'Alternar para modo escuro');
          }
          if (save) {
            try { localStorage.setItem(STORAGE_KEY, theme); } catch (e) {}
          }
        }

        // On load: check localStorage then system preference
        var saved;
        try { saved = localStorage.getItem(STORAGE_KEY); } catch (e) {}
        if (saved === 'dark' || saved === 'light') {
          applyTheme(saved, false);
        } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
          applyTheme('dark', false);
        }

        // Toggle on click
        $btn.on('click', function () {
          var isDark = $wrapper.hasClass('dark-mode');
          applyTheme(isDark ? 'light' : 'dark', true);
        });
      })();

      // Function to update main sidebar and mobile nav active state
      function updateSidebarActiveState(view) {
        // Map views to sidebar link selectors
        var viewMap = {
          'dashboard': 'dashboard',
          'category': 'category',
          'query': 'category',  // Query is part of Consultas
          'consulta': 'category',
          'history': 'history',
          'reports': 'reports',
          'shop': 'shop',
          'orders': 'orders',
          'settings': 'settings'
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

      function generateCacheKey(params) {
          var key = (params.get('view') || 'dashboard');
          if (params.get('type')) key += '_' + params.get('type');
          if (params.get('integration')) key += '_' + params.get('integration');
          if (params.get('category')) key += '_' + params.get('category');
          if (params.get('paged')) key += '_' + params.get('paged');
          if (params.get('order_id')) key += '_' + params.get('order_id');
          return key;
      }

      function renderViewHtml(html, viewType, url) {
          var $content = $('.area-content');
          $content.css({ transition: 'opacity 0.15s ease, transform 0.15s ease', opacity: 0, transform: 'translateY(10px)' });
          
          setTimeout(function() {
              $content.html(html);
              if (typeof serc_initLucide === 'function') serc_initLucide();
              if (viewType === 'category') {
                  $content.css('grid-template-columns', '1fr');
              } else {
                  $content.css('grid-template-columns', '');
              }
              updateSidebarActiveState(viewType);
              window.history.pushState({ view: viewType }, '', url);
              
              // Force reflow
              $content[0].offsetHeight;
              $content.css({ opacity: 1, transform: 'translateY(0)' });
          }, 150);
      }

      // Function to load view via AJAX
      function loadView(url) {
        console.log('[SERC Navigation] Loading view:', url);

        var urlObj = new URL(url, window.location.origin);
        var params = new URLSearchParams(urlObj.search);
        var cacheKey = generateCacheKey(params);

        if (window.sercViewCache[cacheKey]) {
            console.log('[SERC Navigation] Fast booting from cache for key:', cacheKey);
            renderViewHtml(window.sercViewCache[cacheKey].html, window.sercViewCache[cacheKey].view, url);
            return;
        }

        // Show Progress loader
        if ($('.serc-nprogress').length === 0) {
            $('body').append('<div class="serc-nprogress" style="position:fixed;top:0;left:0;width:100%;height:3px;z-index:99999;background:linear-gradient(to right, #10B981, #34D399);transform-origin:left;transform:scaleX(0);opacity:0;"></div>');
        }
        var $loader = $('.serc-nprogress');
        $loader.css({ transition: 'none', opacity: 1, transform: 'scaleX(0)' });
        
        // Force reflow
        $loader[0].offsetHeight;
        $loader.css({ transition: 'transform 0.4s ease', transform: 'scaleX(0.4)' });

        // Make AJAX request
        $.ajax({
          url: serc_ajax.ajax_url,
          type: 'GET',
          data: {
            action: 'serc_load_view',
            view: params.get('view') || 'dashboard',
            type: params.get('type') || '',
            integration: params.get('integration') || '',
            category: params.get('category') || '',
            paged: params.get('paged') || 1,
            order_id: params.get('order_id') || 0
          },
          success: function (response) {
            $loader.css({ transition: 'transform 0.2s ease', transform: 'scaleX(0.8)' });

            if (response.success && response.data && response.data.html) {
              // Cache it
              window.sercViewCache[cacheKey] = {
                  html: response.data.html,
                  view: response.data.view
              };
              
              setTimeout(function() {
                  $loader.css({ transition: 'transform 0.2s ease', transform: 'scaleX(1)' });
                  setTimeout(function() { $loader.css({ transition: 'opacity 0.2s ease', opacity: 0 }); }, 200);
              }, 50);

              renderViewHtml(response.data.html, response.data.view, url);
            } else {
              window.location.href = url;
            }
          },
          error: function (xhr, status, error) {
            console.error('[SERC Navigation] AJAX error:', status, error);
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
      // Global AJAX Search Logic
      var searchTimeout;

      // Generic search handler function
      function handleSearchInput(inputSelector, resultsContainerSelector, customRenderer) {
        $(document).on('keyup focus', inputSelector, function () {
          var $input = $(this);
          var term = $input.val().trim();
          var $container = $(resultsContainerSelector);

          // Create container if it doesn't exist equivalent (for global header search)
          if (resultsContainerSelector === '.global-search-results' && $container.length === 0) {
            $container = $('<div class="global-search-results"></div>');
            $input.parent().append($container);
          }

          // Create container if it doesn't exist (for category/sidebar search)
          if (resultsContainerSelector === '.category-search-results' && $container.length === 0) {
            $container = $('<div class="category-search-results"></div>');
            $input.parent().append($container);
          }


          if (term.length < 2) {
            $container.hide().empty();
            return;
          }

          clearTimeout(searchTimeout);
          searchTimeout = setTimeout(function () {
            $container.show().html('<div class="search-loading">Buscando...</div>');

            $.get(serc_ajax.ajax_url, {
              action: 'serc_search_integrations',
              term: term
            }).done(function (response) {
              if (response.success && response.data && response.data.length > 0) {
                var html = '';
                response.data.forEach(function (item) {
                  html += '<a href="' + item.url + '" class="search-result-item">';
                  html += '<i class="' + item.icon + '"></i>';
                  html += '<div class="search-result-info">';
                  html += '<div class="search-result-name">' + item.name + '</div>';
                  html += '</div>';
                  html += '</a>';
                });
                $container.html(html);
              } else {
                $container.html('<div class="search-no-results">Nenhuma consulta encontrada.</div>');
              }
            }).fail(function (xhr, status, error) {
              console.error('[SERC Search] AJAX error:', status, error);
              $container.html('<div class="search-no-results">Erro na busca. Tente novamente.</div>');
            });
          }, 300); // 300ms debounce
        });

        // Hide results when clicking outside
        $(document).on('click', function (e) {
          if (!$(e.target).closest(inputSelector).length && !$(e.target).closest(resultsContainerSelector).length) {
            $(resultsContainerSelector).hide();
          }
        });
      }

      // Initialize Search handlers
      handleSearchInput('.global-search input', '.global-search-results');
      handleSearchInput('.category-search input', '.category-search-results');
      handleSearchInput('.sidebar-search-input', '.sidebar-search .global-search-results'); // Sidebar search

      // =============================================
      // Favorites Management
      // =============================================
      initFavorites();

      // =============================================
      // Dashboard Usage Filter Buttons
      // =============================================
      $(document).on('click', '.usage-filter-btn', function() {
        $('.usage-filter-btn').removeClass('active');
        $(this).addClass('active');

        var period = $(this).data('period');
        $('#usage-query-count').text('...');
        $('#usage-credits-value').text('...');

        $.ajax({
          url: serc_ajax.ajax_url,
          type: 'POST',
          data: {
            action: 'serc_get_usage_count',
            nonce: serc_ajax.nonce,
            period: period
          },
          success: function(response) {
            if (response.success && response.data) {
              $('#usage-query-count').text(response.data.query_count);
              $('#usage-credits-value').text(parseFloat(response.data.credit_total).toFixed(2));
            }
          },
          error: function() {
            $('#usage-query-count').text('—');
            $('#usage-credits-value').text('—');
          }
        });
      });

      // Auto-load today's usage data on dashboard load
      setTimeout(function() {
        if ($('.usage-filter-btn.active').length && $('#usage-credits-value').length) {
          $('.usage-filter-btn.active').trigger('click');
        }
      }, 300);

      // =============================================
      // PDF Model Preview Modal
      // =============================================
      $(document).on('click', '#btn-view-model, .btn-view-model', function() {
        $('#pdf-modal-overlay').addClass('active');
        $('body').css('overflow', 'hidden');
      });

      $(document).on('click', '#pdf-modal-close', function() {
        $('#pdf-modal-overlay').removeClass('active');
        $('body').css('overflow', '');
      });

      $(document).on('click', '#pdf-modal-overlay', function(e) {
        if ($(e.target).is('#pdf-modal-overlay')) {
          $('#pdf-modal-overlay').removeClass('active');
          $('body').css('overflow', '');
        }
      });

      $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#pdf-modal-overlay').hasClass('active')) {
          $('#pdf-modal-overlay').removeClass('active');
          $('body').css('overflow', '');
        }
      });
    }
  });

  // Favorites functionality
  var favoritesData = null;
  var allIntegrations = {};
  var activeCategory = null;

  function initFavorites() {
    // Create modal if doesn't exist
    if ($('#fav-selector-modal').length === 0) {
      createFavoriteSelectorModal();
    }
  }

  function createFavoriteSelectorModal() {
    var modal = `
      <div id="fav-selector-modal" class="fav-selector-modal">
        <div class="fav-selector-content">
          <div class="fav-selector-header">
            <h3>Selecionar consulta favorita</h3>
            <button class="fav-selector-close" onclick="closeFavoriteSelector()">&times;</button>
          </div>
          <div class="fav-selector-search">
            <input type="text" id="fav-selector-search" placeholder="Buscar consulta...">
          </div>
          <div class="fav-selector-tabs" id="fav-selector-tabs"></div>
          <div class="fav-selector-list" id="fav-selector-list">
            <div class="fav-selector-empty">Carregando...</div>
          </div>
        </div>
      </div>
    `;
    $('body').append(modal);

    // Close on overlay click
    $('#fav-selector-modal').on('click', function (e) {
      if (e.target === this) {
        closeFavoriteSelector();
      }
    });

    // Search filter - AJAX-based like other search inputs
    var searchTimeout;
    $('#fav-selector-search').on('input', function () {
      var term = $(this).val().trim();
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(function () {
        if (term) {
          // Use AJAX search for better results
          $.ajax({
            url: serc_ajax.ajax_url,
            type: 'POST',
            data: {
              action: 'serc_search_integrations',
              term: term
            },
            success: function (response) {
              if (response.success && response.data) {
                // Flatten results from all categories
                var results = [];
                Object.keys(response.data).forEach(function (category) {
                  results = results.concat(response.data[category]);
                });
                renderSelectorList(results);
              }
            }
          });
        } else {
          // Show current category items when search is cleared
          if (activeCategory && allIntegrations[activeCategory]) {
            renderSelectorList(allIntegrations[activeCategory]);
          }
        }
      }, 300);
    });
  }

  function loadIntegrationsForSelector() {
    // Load all integrations grouped by category
    $.ajax({
      url: serc_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'serc_get_all_integrations'
      },
      success: function (response) {
        if (response.success && response.data) {
          allIntegrations = response.data;
          renderCategoryTabs();
          // Select first category
          var categories = Object.keys(allIntegrations);
          if (categories.length > 0) {
            selectCategory(categories[0]);
          }
        }
      },
      error: function () {
        // Fallback: use search integrations with empty term (returns grouped data when empty)
        $.ajax({
          url: serc_ajax.ajax_url,
          type: 'POST',
          data: {
            action: 'serc_search_integrations',
            term: ''
          },
          success: function (response) {
            if (response.success && response.data) {
              // serc_search_integrations returns grouped data when term is empty
              allIntegrations = response.data;
              renderCategoryTabs();
              var categories = Object.keys(allIntegrations);
              if (categories.length > 0) {
                selectCategory(categories[0]);
              }
            }
          }
        });
      }
    });
  }

  function renderCategoryTabs() {
    var $tabs = $('#fav-selector-tabs');
    $tabs.empty();

    var categories = Object.keys(allIntegrations);
    categories.forEach(function (category) {
      var $tab = $('<button class="fav-selector-tab" data-category="' + category + '">' + category + '</button>');
      $tab.on('click', function () {
        selectCategory(category);
        $('#fav-selector-search').val('');
      });
      $tabs.append($tab);
    });
  }

  function selectCategory(category) {
    activeCategory = category;

    // Update active tab
    $('.fav-selector-tab').removeClass('active');
    $('.fav-selector-tab[data-category="' + category + '"]').addClass('active');

    // Render list
    renderSelectorList(allIntegrations[category] || []);
  }

  function filterIntegrationsList(term) {
    if (!term) {
      // Show current category items
      if (activeCategory && allIntegrations[activeCategory]) {
        renderSelectorList(allIntegrations[activeCategory]);
      }
      return;
    }

    // Search across all categories
    var results = [];
    Object.keys(allIntegrations).forEach(function (category) {
      allIntegrations[category].forEach(function (item) {
        var name = (item.name || '').toLowerCase();
        var desc = (item.description || '').toLowerCase();
        if (name.indexOf(term) > -1 || desc.indexOf(term) > -1) {
          results.push(item);
        }
      });
    });

    renderSelectorList(results);
  }

  function renderSelectorList(integrations) {
    var $list = $('#fav-selector-list');
    $list.empty();

    if (!integrations || !integrations.length) {
      $list.html('<div class="fav-selector-empty">Nenhuma consulta encontrada.</div>');
      return;
    }

    integrations.forEach(function (item) {
      var $item = $('<div class="fav-selector-item" data-id="' + item.id + '">' +
        '<i data-lucide="' + (item.icon || 'puzzle') + '"></i>' +
        '<span>' + item.name + '</span>' +
        '</div>');

      $list.append($item);
    });
  }

  // Use event delegation for selector items (more reliable for dynamically created elements)
  $(document).on('click', '.fav-selector-item', function () {
    var integrationId = $(this).data('id');
    if (integrationId) {
      addFavorite(integrationId);
    }
  });

  // Track which slot is being edited (if any)
  var editingSlot = null;
  var editingIntegrationId = null;

  // Global function to open selector
  window.openFavoriteSelector = function (element, slotIndex) {
    editingSlot = slotIndex !== undefined ? slotIndex : null;

    // If editing an existing favorite, get its current integration ID
    if (editingSlot !== null && element) {
      var $card = $(element).closest('.fav-card--filled');
      if ($card.length) {
        editingIntegrationId = $card.data('integration-id');
      }
    } else {
      editingIntegrationId = null;
    }

    $('#fav-selector-modal').addClass('active');
    $('#fav-selector-search').val('');

    // Always reload data
    loadIntegrationsForSelector();

    setTimeout(function () {
      $('#fav-selector-search').focus();
    }, 100);
  };

  // Global function to close selector
  window.closeFavoriteSelector = function () {
    $('#fav-selector-modal').removeClass('active');
    editingSlot = null;
    editingIntegrationId = null;
  };

  // Global function to remove favorite
  window.removeFavorite = function (integrationId) {
    $.ajax({
      url: serc_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'serc_toggle_favorite',
        integration_id: integrationId
      },
      success: function (response) {
        if (response.success) {
          // Reload dashboard to reflect changes
          location.reload();
        } else {
          alert(response.data.message || 'Erro ao remover favorito');
        }
      }
    });
  };

  function addFavorite(integrationId) {
    // If we're editing (replacing) an existing favorite at a specific slot
    if (editingSlot !== null && editingSlot !== undefined) {
      $.ajax({
        url: serc_ajax.ajax_url,
        type: 'POST',
        data: {
          action: 'serc_replace_favorite',
          new_id: integrationId,
          slot_index: editingSlot
        },
        success: function (response) {
          if (response.success) {
            location.reload();
          } else {
            alert('Erro ao atualizar favorito: ' + (response.data.message || 'Erro desconhecido'));
          }
        },
        error: function () {
          alert('Erro na requisição. Tente novamente.');
        }
      });
      return;
    }

    // Default processing for adding a new favorite
    // If we're editing (replacing) an existing favorite but without slot index (legacy logic, shouldn't happen now)
    if (editingIntegrationId && editingIntegrationId !== integrationId) {
      $.ajax({
        url: serc_ajax.ajax_url,
        type: 'POST',
        data: {
          action: 'serc_toggle_favorite',
          integration_id: editingIntegrationId
        },
        success: function (response) {
          // After removing old, add new
          doAddFavorite(integrationId);
        },
        error: function () {
          // Even if remove fails, try to add new
          doAddFavorite(integrationId);
        }
      });
    } else {
      doAddFavorite(integrationId);
    }
  }

  function doAddFavorite(integrationId) {
    $.ajax({
      url: serc_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'serc_toggle_favorite',
        integration_id: integrationId
      },
      success: function (response) {
        if (response.success) {
          closeFavoriteSelector();
          // Reload dashboard to reflect changes
          location.reload();
        } else {
          alert(response.data.message || 'Erro ao adicionar favorito');
        }
      }
    });
  }

});

/* ========== Settings Tab Switching & Form Save ========== */
jQuery(document).ready(function ($) {
  // Settings internal tabs
  $(document).on('click', '.settings-tab', function () {
    var tab = $(this).data('tab');
    $('.settings-tab').removeClass('active');
    $(this).addClass('active');
    $('.settings-panel').removeClass('active');
    $('#settings-panel-' + tab).addClass('active');
  });

  // Save account details
  $(document).on('submit', '#serc-account-form', function (e) {
    e.preventDefault();
    var $status = $('#account-save-status');
    $status.html('<span style="color:#f0ad4e;">Salvando...</span>');

    $.ajax({
      url: serc_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'serc_save_account_settings',
        nonce: serc_ajax.nonce,
        section: 'account',
        first_name: $('#account_first_name').val(),
        last_name: $('#account_last_name').val(),
        display_name: $('#account_display_name').val(),
        email: $('#account_email').val(),
        password_current: $('#password_current').val(),
        password_new: $('#password_new').val(),
        password_confirm: $('#password_confirm').val()
      },
      success: function (response) {
        if (response.success) {
          $status.html('<span style="color:#10B981;">✓ ' + response.data + '</span>');
          // Clear password fields
          $('#password_current, #password_new, #password_confirm').val('');
        } else {
          $status.html('<span style="color:#e74c3c;">✗ ' + response.data + '</span>');
        }
        setTimeout(function () { $status.html(''); }, 4000);
      },
      error: function () {
        $status.html('<span style="color:#e74c3c;">Erro de conexão</span>');
      }
    });
  });

  // Save address
  $(document).on('submit', '#serc-address-form', function (e) {
    e.preventDefault();
    var $status = $('#address-save-status');
    $status.html('<span style="color:#f0ad4e;">Salvando...</span>');

    var data = {
      action: 'serc_save_account_settings',
      nonce: serc_ajax.nonce,
      section: 'address'
    };
    // Collect all billing fields
    $(this).find('input').each(function () {
      data[$(this).attr('name')] = $(this).val();
    });

    $.ajax({
      url: serc_ajax.ajax_url,
      type: 'POST',
      data: data,
      success: function (response) {
        if (response.success) {
          $status.html('<span style="color:#10B981;">✓ ' + response.data + '</span>');
        } else {
          $status.html('<span style="color:#e74c3c;">✗ ' + response.data + '</span>');
        }
        setTimeout(function () { $status.html(''); }, 4000);
      },
      error: function () {
        $status.html('<span style="color:#e74c3c;">Erro de conexão</span>');
      }
    });
  });

  // ==========================================
  // Sidebar Expand / Collapse Logic
  // ==========================================
  var $sidebar = $('.area-sidebar');
  var $wrapper = $('.dashboard-wrapper');
  var $closeBtn = $('.sidebar-close-btn');

  // Load saved state (default is collapsed initially, but localStorage overrides)
  var savedSidebarState = localStorage.getItem('serc_sidebar_collapsed');
  var isSidebarCollapsed = true; // Default to collapsed as requested in Layout e Estilo
  
  if (savedSidebarState === 'false') {
    isSidebarCollapsed = false;
  } else if (savedSidebarState === 'true') {
    isSidebarCollapsed = true;
  }

  if (isSidebarCollapsed) {
    $sidebar.addClass('is-collapsed');
    $wrapper.addClass('sidebar-collapsed');
  } else {
    $sidebar.removeClass('is-collapsed');
    $wrapper.removeClass('sidebar-collapsed');
  }

  // Click on 'X' button to collapse
  $closeBtn.on('click', function(e) {
    e.preventDefault();
    e.stopPropagation();
    $sidebar.addClass('is-collapsed');
    $wrapper.addClass('sidebar-collapsed');
    localStorage.setItem('serc_sidebar_collapsed', 'true');
  });

  // Click anywhere on the collapsed sidebar to expand it (like clicking tabs)
  $sidebar.on('click', function(e) {
    if ($sidebar.hasClass('is-collapsed')) {
      if ($(e.target).closest('.serc-logout-link').length > 0) return;
      
      $sidebar.removeClass('is-collapsed');
      $wrapper.removeClass('sidebar-collapsed');
      localStorage.setItem('serc_sidebar_collapsed', 'false');
    }
  });

  // Drag-to-scroll for metrics grid - Smooth incremental, safe click detection
  let isDown = false;
  let lastX;
  let startClientX; // Track origin for total distance check
  let hasDragged = false;
  let activeGrid = null;

  $(document).on('mousedown', '.dash-metrics-grid', function(e) {
    if (e.button !== 0) return;
    isDown = true;
    hasDragged = false;
    activeGrid = this;
    lastX = e.clientX;
    startClientX = e.clientX;
    $(activeGrid).addClass('is-dragging');
  });

  // Remove is-dragging when mouse leaves or releases
  $(document).on('mouseup mouseleave', '.dash-metrics-grid', function() {
    if (isDown && activeGrid === this) {
      $(activeGrid).removeClass('is-dragging');
      isDown = false;
      activeGrid = null;
    }
  });

  // Also catch mouseup anywhere in doc (e.g. user releases outside carousel)
  $(document).on('mouseup', function() {
    if (activeGrid) {
      $(activeGrid).removeClass('is-dragging');
    }
    isDown = false;
    // Reset hasDragged after a tick so click handler runs first
    setTimeout(() => { hasDragged = false; }, 50);
    activeGrid = null;
  });

  $(document).on('mousemove', '.dash-metrics-grid', function(e) {
    if (!isDown || activeGrid !== this) return;

    e.preventDefault();

    const dx = lastX - e.clientX;
    lastX = e.clientX;

    activeGrid.scrollLeft += dx;

    // Only consider it a "drag" if total displacement from origin >= 8px
    if (Math.abs(e.clientX - startClientX) >= 8) {
      hasDragged = true;
    }
  });

  // Block navigation only when user actually dragged (>= 8px total)
  $(document).on('click', '.dash-metrics-grid a', function(e) {
    if (hasDragged) {
      e.preventDefault();
      e.stopImmediatePropagation();
    }
  });

  // Prevent native browser ghost-image drag on links/images
  $(document).on('dragstart', '.dash-metrics-grid a, .dash-metrics-grid img', function(e) {
    e.preventDefault();
  });

});
