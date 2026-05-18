/**
 * Admin JavaScript for Img Panda
 *
 * @package Img_Panda
 */

(function ($) {
  "use strict";

  /**
   * Initialize when DOM is ready
   */
  $(document).ready(function () {
    initQualitySlider();
    initRescanButton();
    initSettingsSave();
    initAiControls();
  });

  /**
   * Initialize quality slider
   */
  function initQualitySlider() {
    $('input[name="Img_Panda_settings[quality]"]').on('input', function() {
        $('#quality-val-display').text($(this).val() + '%');
    });
  }

  /**
   * Handle AJAX settings save
   */
  function initSettingsSave() {
    // Single source of truth: the form's submit event
    $('#img-panda-main-form').on('submit', function(e) {
        e.preventDefault();
        saveAllSettings();
    });
  }

  function saveAllSettings() {
    var $form = $('#img-panda-main-form');
    var $btn = $('button[type="submit"]');
    
    $btn.prop('disabled', true).css('opacity', '0.7');
    
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'img_panda_save_settings',
            nonce: imgPandaAdminData.nonce,
            form_data: $form.serialize()
        },
        success: function(response) {
            if (response.success) {
                showToast('✓ ' + response.data.message, 'success');
            } else {
                showToast('✗ ' + response.data.message, 'error');
            }
        },
        error: function() {
            showToast('✗ Connection error', 'error');
        },
        complete: function() {
            $btn.prop('disabled', false).css('opacity', '1');
        }
    });
  }

  /**
   * AI Controls: Testing & Model switching
   */
  function initAiControls() {
    var $provider = $('#ai-provider-select');
    var $model = $('#ai-model-select');
    var $testBtn = $('#btn-test-ai');
    
    var models = {
        gemini: [
            { id: 'gemini-flash-latest', name: 'Gemini 1.5 Flash (Free & Fast)' },
            { id: 'gemini-pro-latest', name: 'Gemini 1.5 Pro (Free Tier available)' },
            { id: 'gemini-pro-vision', name: 'Gemini Pro Vision (Legacy)' }
        ],
        openai: [
            { id: 'gpt-4o-mini', name: 'GPT-4o Mini (Paid)' },
            { id: 'gpt-4o', name: 'GPT-4o (High Cost)' },
            { id: 'gpt-4-turbo', name: 'GPT-4 Turbo' }
        ]
    };

    function updateModels() {
        var selected = $provider.val();
        var list = models[selected] || [];
        $model.empty();
        
        $.each(list, function(i, m) {
            $model.append($('<option>', { value: m.id, text: m.name }));
        });
        
        // Priority 1: Match saved value from settings
        if (typeof imgPandaAdminData !== 'undefined' && imgPandaAdminData.settings && imgPandaAdminData.settings.ai_model) {
            $model.val(imgPandaAdminData.settings.ai_model);
        }
        
        // Priority 2: If no value is selected (first run or invalid), default to the first one in list
        if (!$model.val() && list.length > 0) {
            $model.val(list[0].id);
        }
    }

    $provider.on('change', updateModels);
    updateModels();

    // Test API Connection
    $testBtn.on('click', function() {
        var $btn = $(this);
        var originalText = $btn.html();
        var key = $('input[name="Img_Panda_settings[ai_api_key]"]').val();
        
        if (!key) {
            showToast('Please enter an API key first', 'error');
            return;
        }

        // Stage 1: Testing
        $btn.prop('disabled', true).addClass('opacity-50').text('Testing...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'img_panda_test_ai_connection',
                nonce: imgPandaAdminData.nonce,
                key: key,
                provider: $provider.val(),
                model: $model.val()
            },
            success: function(response) {
                if (response.success) {
                    showToast(response.data.message, 'success');
                    // Stage 2: Success State
                    $btn.text('✓ Connected!').css('background', '#10b981').css('color', 'white');
                } else {
                    showToast('Error: ' + response.data.message, 'error');
                    // Stage 2: Error State
                    $btn.text('✗ Failed').css('background', '#ef4444').css('color', 'white');
                }
            },
            error: function() {
                showToast('API Connection failed', 'error');
                $btn.text('✗ Error').css('background', '#ef4444').css('color', 'white');
            },
            complete: function() {
                // Stage 3: Graceful Reset
                setTimeout(function() {
                    $btn.prop('disabled', false).removeClass('opacity-50').html(originalText).css('background', '').css('color', '');
                }, 2000);
            }
        });
    });
  }

  function showToast(msg, type) {
    // Remove existing toast if any
    $('.img-panda-toast-js').remove();
    
    const bgColor = type === 'success' ? 'bg-success' : 'bg-red-500';
    const icon = type === 'success' ? 'check_circle' : 'error';
    
    const $toast = $(`
        <div class="img-panda-toast-js fixed bottom-8 right-8 z-[9999] flex items-center gap-3 ${bgColor} text-white px-6 py-4 rounded-2xl shadow-2xl transition-all duration-500 translate-y-20 opacity-0 cursor-pointer">
            <span class="material-symbols-outlined">${icon}</span>
            <span class="font-bold text-sm tracking-tight">${msg}</span>
        </div>
    `);
    
    $('body').append($toast);
    
    // Animate in
    setTimeout(() => {
        $toast.removeClass('translate-y-20 opacity-0');
    }, 10);
    
    // Auto-remove after 4s
    const timer = setTimeout(() => {
        $toast.addClass('translate-y-20 opacity-0');
        setTimeout(() => $toast.remove(), 500);
    }, 2000);

    // Dismiss on click
    $toast.on('click', function() {
        clearTimeout(timer);
        $toast.addClass('translate-y-20 opacity-0');
        setTimeout(() => $toast.remove(), 500);
    });
  }

  /**
   * Initialize re-scan button functionality
   */
  function initRescanButton() {
    $("#btn-rescan-server").on("click", function () {
      var $btn = $(this);
      var $icon = $btn.find(".material-symbols-outlined");
      var $text = $btn.find(".btn-text");
      var $status = $("#rescan-status");

      // Add loading state
      $btn.prop("disabled", true);
      $icon.addClass("animate-spin");
      $text.text("Scanning...");
      $status.text("").removeClass("text-success text-red-500");

      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
          action: "img_panda_check_server",
          nonce:
            typeof imgPandaAdminData !== "undefined" ? imgPandaAdminData.nonce : "",
        },
        success: function (response) {
          if (response.success) {
            $status
              .text("✓ Server scanned successfully!")
              .addClass("text-success");
            setTimeout(function () {
              location.reload();
            }, 1000);
          } else {
            $status.text("✗ Scan failed").addClass("text-red-500");
          }
        },
        error: function () {
          $status.text("✗ Server error").addClass("text-red-500");
        },
        complete: function () {
          $btn.prop("disabled", false);
          $icon.removeClass("animate-spin");
          $text.text("Re-scan Server");
        },
      });
    });
  }


  /**
   * Initialize Dashboard Chart
   */
  function initDashboardChart() {
    var ctx = document.getElementById("img-panda-stats-chart");
    if (
      !ctx ||
      typeof Chart === "undefined" ||
      typeof imgPandaAdminData === "undefined"
    ) {
      return;
    }

    var total = parseInt(imgPandaAdminData.stats.total);
    var converted = parseInt(imgPandaAdminData.stats.converted);
    var pending = parseInt(imgPandaAdminData.stats.pending);

    new Chart(ctx, {
      type: "doughnut",
      data: {
        labels: ["Converted", "Pending"],
        datasets: [
          {
            data: [converted, pending],
            backgroundColor: ["#7c3bed", "#f2f0f4"],
            hoverOffset: 4,
            borderWidth: 0,
          },
        ],
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            display: false,
          },
        },
        cutout: "80%",
      },
    });
  }

  // Final check for chart on load
  if ($("#img-panda-stats-chart").length) {
    initDashboardChart();
  }
})(jQuery);
