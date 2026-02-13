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
          $result.append('<p><a class="action-btn" href="' + dl + '"><i class="ph-bold ph-download-simple"></i> Download PDF</a></p>');
        } else {
          var pdfB64 = (resp.data && resp.data.result && resp.data.result.pdfBase64) || (resp.data && resp.data.pdfBase64);
          if (pdfB64) {
            var url = base64ToBlobUrl(pdfB64);
            if (url) {
              console.log('[SERC] PDF blob URL created:', url);
              $result.append('<p><a class="action-btn" href="' + url + '" download="consulta.pdf"><i class="ph-bold ph-download-simple"></i> Download PDF</a></p>');
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
        '<i class="' + (item.icon || 'ph-puzzle-piece') + '"></i>' +
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
