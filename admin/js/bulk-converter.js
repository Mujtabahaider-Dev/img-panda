/**
 * Bulk Converter JavaScript
 *
 * @package Img_Panda
 */

(function ($) {
  "use strict";

  let conversionActive = false;
  let conversionPaused = false;
  let totalImages = 0;

  /**
   * Initialize on document ready
   */
  $(document).ready(function () {
    initBulkConverter();
  });

  /**
   * Initialize bulk converter
   */
  function initBulkConverter() {
    // Start button
    $("#btn-start-conversion").on("click", startConversion);

    // Pause button
    $("#btn-pause-conversion").on("click", pauseConversion);

    // Resume button
    $("#btn-resume-conversion").on("click", resumeConversion);

    // Stop button
    $("#btn-stop-conversion").on("click", stopConversion);

    // Clear log button
    $("#btn-clear-log").on("click", clearLog);

    // Filter toggles
    initFilters();
  }

  /**
   * Initialize filter functionality
   */
  function initFilters() {
    // Toggle filters visibility
    $("#btn-toggle-filters").on("click", function () {
      const $content = $("#filters-content");
      const $toggle = $(this);
      const $text = $toggle.find(".toggle-text");
      const $icon = $toggle.find(".dashicons");

      $content.slideToggle(300, function () {
        if ($content.is(":visible")) {
          $text.text("Hide Filters");
          $icon
            .removeClass("dashicons-arrow-down-alt2")
            .addClass("dashicons-arrow-up-alt2");
        } else {
          $text.text("Show Filters");
          $icon
            .removeClass("dashicons-arrow-up-alt2")
            .addClass("dashicons-arrow-down-alt2");
        }
      });
    });

    // Reset filters
    $("#btn-reset-filters").on("click", function () {
      $("#filter-format").val("all");
      $("#filter-date-from").val("");
      $("#filter-date-to").val("");
      $("#filter-size").val("all");
      updateFilterSummary();
    });

    // Update filter summary on change
    $("#filter-format, #filter-date-from, #filter-date-to, #filter-size").on(
      "change",
      updateFilterSummary,
    );

    // Initialize filter summary
    updateFilterSummary();
  }

  /**
   * Update filter summary display
   */
  function updateFilterSummary() {
    const format = $("#filter-format").val();
    const dateFrom = $("#filter-date-from").val();
    const dateTo = $("#filter-date-to").val();
    const size = $("#filter-size").val();

    const filters = [];

    if (format !== "all") {
      filters.push(format.toUpperCase() + " format");
    }

    if (dateFrom || dateTo) {
      let dateRange = "from ";
      if (dateFrom) {
        dateRange += new Date(dateFrom).toLocaleDateString();
      } else {
        dateRange += "beginning";
      }
      dateRange += " to ";
      if (dateTo) {
        dateRange += new Date(dateTo).toLocaleDateString();
      } else {
        dateRange += "now";
      }
      filters.push(dateRange);
    }

    if (size !== "all") {
      filters.push($("#filter-size option:selected").text());
    }

    const $summary = $("#filter-summary-text");
    if (filters.length > 0) {
      $summary.html("<strong>Active:</strong> " + filters.join(" • "));
      $summary.parent().addClass("has-filters");
    } else {
      $summary.text(imgPandaBulkData.strings.noFilters || "No filters applied");
      $summary.parent().removeClass("has-filters");
    }
  }

  /**
   * Start bulk conversion
   */
  function startConversion() {
    if (conversionActive) {
      return;
    }

    // Get filters
    const gatherFilters = () => {
      return {
        format: $("#filter-format").val(),
        size: $("#filter-size").val(),
        min_size: $("#filter-min-size").val() || 0,
        generate_alt: $("#bulk-ai-alt").is(":checked") ? 1 : 0,
      };
    };

    const filters = gatherFilters();

    // Show progress section
    $("#conversion-progress").slideDown();
    $("#conversion-log-section").slideDown();

    // Update UI
    $("#btn-start-conversion").prop("disabled", true).hide();
    $("#btn-pause-conversion").show();
    $("#btn-stop-conversion").show();

    // Show status
    updateStatus(imgPandaBulkData.strings.startingConversion, "info");

    // AJAX call to start conversion
    $.ajax({
      url: imgPandaBulkData.ajaxUrl,
      type: "POST",
      data: {
        action: "img_panda_start_bulk",
        nonce: imgPandaBulkData.nonce,
        filters: filters,
        generate_alt: filters.generate_alt,
      },
      success: function (response) {
        if (response.success) {
          conversionActive = true;
          totalImages = response.data.total;
          $("#stat-total").text(totalImages);

          updateStatus(
            "Started processing " + totalImages + " images...",
            "success",
          );
          addLog("✓ Bulk conversion started", "success");
          addLog(
            "Processing " + totalImages + " images in batches of 50",
            "info",
          );

          // Start processing batches
          processBatch();
        } else {
          showError(response.data.message || "Failed to start conversion");
          resetUI();
        }
      },
      error: function () {
        showError(imgPandaBulkData.strings.error);
        resetUI();
      },
    });
  }

  /**
   * Process next batch
   */
  function processBatch() {
    if (!conversionActive || conversionPaused) {
      return;
    }

    updateStatus(imgPandaBulkData.strings.processing, "info");

    $.ajax({
      url: imgPandaBulkData.ajaxUrl,
      type: "POST",
      data: {
        action: "img_panda_process_batch",
        nonce: imgPandaBulkData.nonce,
      },
      success: function (response) {
        if (response.success) {
          const data = response.data;

          // Update progress
          updateProgress(data.progress);

          // Add batch logs
          if (data.batch_results && data.batch_results.logs) {
            data.batch_results.logs.forEach(function (log) {
              let logClass = log.status;
              let icon = "•";

              if (log.status === "success") {
                icon = "✓";
              } else if (log.status === "failed") {
                icon = "✗";
                logClass = "error";
              } else if (log.status === "skipped") {
                icon = "○";
                logClass = "warning";
              }

              const savings =
                log.original_size && log.new_size
                  ? formatBytes(log.original_size - log.new_size)
                  : "";

              addLog(
                icon +
                  " Image ID " +
                  log.id +
                  ": " +
                  log.message +
                  (savings ? " (Saved: " + savings + ")" : ""),
                logClass,
              );
            });
          }

          // Check if completed
          if (data.status === "completed") {
            conversionComplete(data.progress);
          } else if (data.status === "processing") {
            // Update estimated time
            if (data.estimated_time) {
              $("#stat-estimated-time").text(formatTime(data.estimated_time));
            }

            // Process next batch - Instant trigger for speed
            setTimeout(processBatch, 0);
          }
        } else {
          if (data.status === "paused") {
            updateStatus(imgPandaBulkData.strings.paused, "warning");
          } else {
            showError(data.message || "Processing error");
            resetUI();
          }
        }
      },
      error: function () {
        showError(imgPandaBulkData.strings.error);
        resetUI();
      },
    });
  }

  /**
   * Pause conversion
   */
  function pauseConversion() {
    conversionPaused = true;

    $.ajax({
      url: imgPandaBulkData.ajaxUrl,
      type: "POST",
      data: {
        action: "img_panda_pause",
        nonce: imgPandaBulkData.nonce,
      },
      success: function (response) {
        if (response.success) {
          $("#btn-pause-conversion").hide();
          $("#btn-resume-conversion").show();
          updateStatus(imgPandaBulkData.strings.paused, "warning");
          addLog("⏸ Conversion paused", "warning");
        }
      },
    });
  }

  /**
   * Resume conversion
   */
  function resumeConversion() {
    conversionPaused = false;

    $.ajax({
      url: imgPandaBulkData.ajaxUrl,
      type: "POST",
      data: {
        action: "img_panda_resume",
        nonce: imgPandaBulkData.nonce,
      },
      success: function (response) {
        if (response.success) {
          $("#btn-resume-conversion").hide();
          $("#btn-pause-conversion").show();
          updateStatus(imgPandaBulkData.strings.processing, "info");
          addLog("▶ Conversion resumed", "success");
          processBatch();
        }
      },
    });
  }

  /**
   * Stop conversion
   */
  function stopConversion() {
    if (!confirm(imgPandaBulkData.strings.confirmStop)) {
      return;
    }

    $.ajax({
      url: imgPandaBulkData.ajaxUrl,
      type: "POST",
      data: {
        action: "img_panda_stop",
        nonce: imgPandaBulkData.nonce,
      },
      success: function (response) {
        if (response.success) {
          conversionActive = false;
          conversionPaused = false;
          updateStatus(imgPandaBulkData.strings.stopped, "error");
          addLog("■ Conversion stopped by user", "error");
          resetUI();
        }
      },
    });
  }

  /**
   * Handle conversion complete
   */
  function conversionComplete(progress) {
    conversionActive = false;

    updateStatus(imgPandaBulkData.strings.completed, "success");
    addLog("✓ Bulk conversion completed!", "success");
    addLog(
      "Processed: " +
        progress.processed +
        " | Successful: " +
        progress.successful +
        " | Failed: " +
        progress.failed,
      "info",
    );

    $("#progress-bar-fill").addClass("complete");
    $("#btn-pause-conversion").hide();
    $("#btn-stop-conversion").hide();

    // Auto-reload after 2 seconds to update stats
    setTimeout(function () {
      addLog("↻ Reloading page to update statistics...", "info");
      setTimeout(function() {
        location.reload();
      }, 1500);
    }, 1000);
  }

  /**
   * Update progress bar and stats
   */
  function updateProgress(progress) {
    const percentage =
      progress.total > 0
        ? Math.round((progress.processed / progress.total) * 100)
        : 0;

    $("#progress-bar-fill").css("width", percentage + "%");
    $("#progress-percentage").text(percentage + "%");

    $("#stat-processed").text(progress.processed);
    $("#stat-successful").text(progress.successful);
    $("#stat-failed").text(progress.failed);
    $("#stat-skipped").text(progress.skipped);

    // Toggle error class for failed stats
    if (progress.failed > 0) {
      $("#stat-failed-container").addClass("error");
    } else {
      $("#stat-failed-container").removeClass("error");
    }
  }

  /**
   * Update status message
   */
  function updateStatus(message, type) {
    const icon =
      type === "success"
        ? "check_circle"
        : type === "error"
          ? "error"
          : type === "warning"
            ? "warning"
            : "info";

    $("#conversion-status")
      .html(
        '<span class="material-symbols-outlined text-[16px] align-middle mr-1">' +
          icon +
          "</span> " +
          message,
      )
      .attr("class", "conversion-status status-" + type);
  }

  /**
   * Add log entry
   */
  function addLog(message, type) {
    const timestamp = new Date().toLocaleTimeString();
    const logClass = type || "info";

    const logEntry = $("<div>", {
      class: "log-entry log-" + logClass,
      html: '<span class="log-time">[' + timestamp + "]</span> " + message,
    });

    $("#conversion-log").prepend(logEntry);

    // Keep only last 100 entries
    $("#conversion-log .log-entry:gt(99)").remove();

    // Auto scroll
    const logContainer = $("#conversion-log");
    logContainer.scrollTop(0);
  }

  /**
   * Clear log
   */
  function clearLog() {
    $("#conversion-log").empty();
    addLog("Log cleared", "info");
  }

  /**
   * Show error message
   */
  function showError(message) {
    updateStatus(message, "error");
    addLog("✗ Error: " + message, "error");
  }

  /**
   * Reset UI to initial state
   */
  function resetUI() {
    conversionActive = false;
    conversionPaused = false;

    $("#btn-start-conversion").prop("disabled", false).show();
    $("#btn-pause-conversion").hide();
    $("#btn-resume-conversion").hide();
    $("#btn-stop-conversion").hide();
  }

  /**
   * Format bytes to human readable
   */
  function formatBytes(bytes) {
    if (bytes === 0) return "0 B";
    const k = 1024;
    const sizes = ["B", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + " " + sizes[i];
  }

  /**
   * Format time in seconds to human readable
   */
  function formatTime(seconds) {
    if (seconds < 60) {
      return Math.round(seconds) + " seconds";
    } else if (seconds < 3600) {
      const minutes = Math.floor(seconds / 60);
      const secs = Math.round(seconds % 60);
      return minutes + "m " + secs + "s";
    } else {
      const hours = Math.floor(seconds / 3600);
      const minutes = Math.floor((seconds % 3600) / 60);
      return hours + "h " + minutes + "m";
    }
  }
})(jQuery);
