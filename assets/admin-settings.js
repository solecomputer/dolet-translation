(function () {
  var config = window.doletAdminSettings || {};
  var ajaxUrl = config.ajaxUrl || '';

  function encode(params) {
    var parts = [];
    for (var key in params) {
      if (Object.prototype.hasOwnProperty.call(params, key)) {
        parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(params[key]));
      }
    }
    return parts.join('&');
  }

  function post(params, callback) {
    if (!ajaxUrl) {
      callback({ success: false, data: { error: 'Missing AJAX URL' } });
      return;
    }
    var xhr = new XMLHttpRequest();
    xhr.open('POST', ajaxUrl);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function () {
      try {
        callback(JSON.parse(xhr.responseText));
      } catch (error) {
        callback({ success: false, data: { error: 'Invalid JSON' } });
      }
    };
    xhr.onerror = function () {
      callback({ success: false, data: { error: 'HTTP ' + xhr.status } });
    };
    xhr.send(encode(params));
  }

  function postConsole(action, data, callback) {
    data.action = action;
    data._wpnonce = config.consoleNonce || '';
    post(data, callback);
  }

  function postDiagnostics(action, callback) {
    post({ action: action, _ajax_nonce: config.diagnosticsNonce || '' }, callback);
  }

  var tabLinks = document.querySelectorAll('#dolet_tabs .nav-tab');
  var tabPanels = document.querySelectorAll('.dolet-tab-panel');
  function switchTab(tabId) {
    var index;
    for (index = 0; index < tabLinks.length; index++) {
      tabLinks[index].classList.toggle('nav-tab-active', tabLinks[index].getAttribute('data-tab') === tabId);
    }
    for (index = 0; index < tabPanels.length; index++) {
      tabPanels[index].style.display = tabPanels[index].id === 'dolet_tab_' + tabId ? '' : 'none';
    }
  }
  for (var tabIndex = 0; tabIndex < tabLinks.length; tabIndex++) {
    (function (link) {
      link.addEventListener('click', function (event) {
        event.preventDefault();
        var tabId = link.getAttribute('data-tab');
        switchTab(tabId);
        window.location.hash = tabId;
      });
    })(tabLinks[tabIndex]);
  }
  var hash = window.location.hash.replace('#', '');
  if (hash === 'console' || hash === 'diagnostics') {
    switchTab(hash);
  }

  function appendOutput(element, value) {
    element.value += value + '\n';
    element.scrollTop = element.scrollHeight;
  }

  var statsTable = document.getElementById('dolet_stats_table');
  var statsBody = document.getElementById('dolet_stats_body');
  var statsNone = document.getElementById('dolet_stats_none');
  var statsButton = document.getElementById('dolet_stats_refresh');
  var processingPending = document.getElementById('dolet_processing_pending');
  function refreshStats() {
    if (!statsButton) {
      return;
    }
    statsButton.disabled = true;
    postConsole('dolet_console_stats', {}, function (response) {
      statsButton.disabled = false;
      if (!response.success || !response.data || !response.data.length) {
        if (statsTable) {
          statsTable.style.display = 'none';
        }
        if (statsNone) {
          statsNone.style.display = '';
        }
        if (processingPending && response.success) {
          processingPending.textContent = '0';
        }
        return;
      }
      if (statsTable) {
        statsTable.style.display = '';
      }
      if (statsNone) {
        statsNone.style.display = 'none';
      }
      while (statsBody.firstChild) {
        statsBody.removeChild(statsBody.firstChild);
      }
      var pendingTotal = 0;
      for (var index = 0; index < response.data.length; index++) {
        var stats = response.data[index];
        pendingTotal += Number(stats.pending) || 0;
        var row = document.createElement('tr');
        var fields = [stats.src, stats.tgt, stats.total, stats.translated, stats.pending, stats.human_locked, stats.word_count];
        for (var fieldIndex = 0; fieldIndex < fields.length; fieldIndex++) {
          var cell = document.createElement('td');
          cell.textContent = String(fields[fieldIndex]);
          row.appendChild(cell);
        }
        statsBody.appendChild(row);
      }
      if (processingPending) {
        processingPending.textContent = String(pendingTotal);
      }
    });
  }
  if (statsButton) {
    statsButton.addEventListener('click', function (event) {
      event.preventDefault();
      refreshStats();
    });
  }

  var workerButton = document.getElementById('dolet_worker_trigger');
  var workerOutput = document.getElementById('dolet_worker_output');
  if (workerButton) {
    workerButton.addEventListener('click', function (event) {
      event.preventDefault();
      workerButton.disabled = true;
      workerOutput.textContent = config.workerRunning || '';
      postConsole('dolet_console_worker_trigger', {}, function (response) {
        workerButton.disabled = false;
        if (response.success) {
          workerOutput.textContent = config.workerDone || '';
          refreshStats();
        } else {
          workerOutput.textContent = 'Error: ' + (response.data && response.data.error ? response.data.error : 'unknown');
        }
      });
    });
  }

  var testButton = document.getElementById('dolet_test_submit');
  var testOutput = document.getElementById('dolet_test_output');
  if (testButton) {
    testButton.addEventListener('click', function (event) {
      event.preventDefault();
      var source = document.getElementById('dolet_test_src').value;
      var target = document.getElementById('dolet_test_tgt').value;
      var text = document.getElementById('dolet_test_text').value;
      if (!text) {
        return;
      }
      testButton.disabled = true;
      testOutput.value = '';
      postConsole('dolet_console_test_submit', { source_lang: source, target_lang: target, text: text }, function (response) {
        if (!response.success) {
          appendOutput(testOutput, 'Error: ' + (response.data && response.data.error ? response.data.error : 'unknown'));
          testButton.disabled = false;
          return;
        }
        var jobId = response.data.job_id;
        appendOutput(testOutput, 'Job submitted: ' + jobId);
        appendOutput(testOutput, config.testPolling || '');
        var pollCount = 0;
        var maxPolls = 30;
        function poll() {
          pollCount++;
          postConsole('dolet_console_test_poll', { job_id: jobId }, function (pollResponse) {
            if (!pollResponse.success) {
              appendOutput(testOutput, 'Error: ' + (pollResponse.data && pollResponse.data.error ? pollResponse.data.error : 'unknown'));
              testButton.disabled = false;
              return;
            }
            if (pollResponse.data.status === 'pending') {
              appendOutput(testOutput, '[Poll ' + pollCount + '] Pending...');
              if (pollCount < maxPolls) {
                window.setTimeout(poll, 1000);
              } else {
                appendOutput(testOutput, 'Timeout after ' + maxPolls + ' polls.');
                testButton.disabled = false;
              }
              return;
            }
            if (pollResponse.data.status === 'success') {
              appendOutput(testOutput, '[Result] ' + pollResponse.data.reply);
            } else {
              appendOutput(testOutput, '[Failed] ' + (pollResponse.data.error || 'unknown'));
            }
            testButton.disabled = false;
          });
        }
        window.setTimeout(poll, 500);
      });
    });
  }

  var refreshButton = document.getElementById('dolet_error_log_refresh');
  var clearButton = document.getElementById('dolet_error_log_clear');
  var logArea = document.getElementById('dolet_error_log_textarea');
  var lastSuccess = document.getElementById('dolet_diag_last_success');
  var lastError = document.getElementById('dolet_diag_last_error');
  var lastErrorAt = document.getElementById('dolet_diag_last_error_at');
  if (refreshButton) {
    refreshButton.addEventListener('click', function () {
      refreshButton.disabled = true;
      postDiagnostics('dolet_refresh_error_log', function (response) {
        refreshButton.disabled = false;
        if (response.success) {
          logArea.value = response.data.log || config.errorLogEmpty || '';
          lastSuccess.textContent = response.data.lastSuccess || '\u2014';
          lastError.textContent = response.data.lastError || '\u2014';
          lastErrorAt.textContent = response.data.lastErrorAt || '\u2014';
        }
      });
    });
  }
  if (clearButton) {
    clearButton.addEventListener('click', function () {
      if (!window.confirm(config.errorLogClearConfirm || '')) {
        return;
      }
      clearButton.disabled = true;
      postDiagnostics('dolet_clear_error_log', function (response) {
        clearButton.disabled = false;
        if (response.success) {
          logArea.value = config.errorLogEmpty || '';
          lastSuccess.textContent = '\u2014';
          lastError.textContent = '\u2014';
          lastErrorAt.textContent = '\u2014';
        }
      });
    });
  }

  function runDiagnostic(testId, callback) {
    var result = document.getElementById('dolet_test_result_' + testId);
    var detail = document.getElementById('dolet_test_detail_' + testId);
    if (!result || !detail) {
      if (callback) {
        callback();
      }
      return;
    }
    result.textContent = '...';
    result.style.color = '';
    detail.textContent = '';
    postDiagnostics('dolet_test_' + testId, function (response) {
      if (response.success) {
        var passed = response.data.result === 'pass';
        result.textContent = response.data.result;
        result.style.color = passed ? '#46b450' : '#dc3232';
        detail.textContent = response.data.detail || '';
      } else {
        result.textContent = 'error';
        result.style.color = '#dc3232';
        detail.textContent = response.data && response.data.error ? response.data.error : 'AJAX error';
      }
      if (callback) {
        callback();
      }
    });
  }

  var runButtons = document.querySelectorAll('.dolet-test-run');
  for (var runIndex = 0; runIndex < runButtons.length; runIndex++) {
    (function (button) {
      button.addEventListener('click', function () {
        button.disabled = true;
        runDiagnostic(button.getAttribute('data-test'), function () {
          button.disabled = false;
        });
      });
    })(runButtons[runIndex]);
  }

  var runAllButton = document.getElementById('dolet_test_run_all');
  if (runAllButton) {
    runAllButton.addEventListener('click', function () {
      runAllButton.disabled = true;
      var testIds = config.testIds || [];
      var index = 0;
      function next() {
        if (index >= testIds.length) {
          runAllButton.disabled = false;
          return;
        }
        runDiagnostic(testIds[index], function () {
          index++;
          next();
        });
      }
      next();
    });
  }
})();
