var form;
var choices;
var processed = 0;
var data_collected = {};
var all_instances = [];
var all_instances_selected = false;
var lang = '';
var lang_tags = {};

/**
 * On load window
 * @param {*} e 
 */
window.onload = function (e) {
  form = document.querySelector('#form');
  getLanguage(initialize);
};

/**
 * Get language tags
 */
var getLanguage = function(callback) {
    lang = 'en'; //Set the default lang, in case there is no one configured
    axios.get('./index.php', {params: {action: 'language'}}).then(function(response) {
        if (response.data !== undefined) {
            if (Object.keys(response.data).length > 0) {
                lang = response.data.lang;
                lang_tags = response.data.tags;
                Object.keys(lang_tags).forEach(key => {
                    if (key.indexOf('select_') == -1 && key.indexOf('placeholder_') == -1 && key.indexOf('table_') == -1) {
                        document.getElementById(key).innerHTML = lang_tags[key];
                    }
                    else if (key.indexOf('placeholder_') >= 0) {
                        document.getElementById(key.replace('placeholder_', '')).placeholder = lang_tags[key];
                    }
                });
                callback();
                return;
            }
        }
        callback();
    }).catch(function() {
        callback();
    });
}

/**
 * Initialize the starting values
 */
function initialize() {
    if (lang !== 'en') {
        getCalendarLang(setDatePicker, "#date-range");
    }
    else {
        setDatePicker("#date-range");
    }
    setFormEvents();
    getInstances();
}

/**
 * Set the date picker
 * @param {String} target 
 */
function setDatePicker(target) {
    window.flatpickrObject = flatpickr(target, {
        mode: "range",
        maxDate: "today",
        conjunction: ", ",
        dateFormat: "Y-m-d",
        locale: lang
    });
}

/**
 * Get the calendar language (if is different from 'en')
 * @param {*} callback 
 * @param {*} param 
 */
var getCalendarLang = function(callback, param) {
    var script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = 'https://npmcdn.com/flatpickr/dist/l10n/'+lang+'.js';    
    document.getElementsByTagName('head')[0].appendChild(script);
    setTimeout(function() {
        callback(param);
    }, 300);
}

/**
 * Set form events
 */
function setFormEvents() {
  form.addEventListener('submit', sendData);
}

/**
 * Start the search, show loader, disable buttons, clean table
 */
function startSearch() {
    processed = 0;
    data_collected = {};
    document.getElementById("btn_submit").classList.remove("btn-primary");
    document.getElementById("btn_submit").classList.add("btn-secondary");
    document.getElementById("btn_submit").disabled = true;

    document.getElementById("div_loader").style.display = "";
    document.getElementById("div_table").style.display = "none";

    var tbody = document.getElementById('data_table');
    tbody.innerHTML = '';
    var tfooter = document.getElementById('data_table_footer');
    tfooter.innerHTML = '';
    
    disableDownload();
}

/**
 * End search, enable buttons, hide loader
 */
function endSearch() {
    document.getElementById("btn_submit").classList.remove("btn-secondary");
    document.getElementById("btn_submit").classList.add("btn-primary");
    document.getElementById("btn_submit").disabled = false;

    document.getElementById("div_loader").style.display = "none";
    enableDownload();
}

/**
 * Disable download buttons
 */
function disableDownload() {
    var elements = document.getElementsByClassName("btn_download");
    for (var i = 0; i < elements.length; i++) {
        elements[i].classList.remove('btn-primary');
        elements[i].classList.add('btn-secondary');
        elements[i].disabled = true;
    }
}

/**
 * Enable download buttons
 */
function enableDownload() {
    var elements = document.getElementsByClassName("btn_download");
    for (var i = 0; i < elements.length; i++) {
        elements[i].classList.remove('btn-secondary');
        elements[i].classList.add('btn-primary');
        elements[i].disabled = false;
    }
}

/**
 * Send data from the form
 * @param {event} e 
 */
function sendData(e) {
  e.preventDefault();
  
  var dates = window.flatpickrObject.selectedDates;
  var instances = choices.getValue(true);
  var data = {};
  
  if (instances.length === 0) {
    document.getElementById("select-instances").click();
    return false;
  }
  if (dates[0] === undefined || dates[0] === '' || dates[1] === undefined || dates[1] === '') {
    document.getElementById("date-range").focus();
    return false;
  }
  if (all_instances_selected) {
    instances = all_instances;
  }
  
  startSearch();
  for (var i=0; i < instances.length; i++) {
    data = {
        action: 'reports',
        dateFrom: dates[0],
        dateTo: dates[1],
        instances: [instances[i]]
    };
    getData(data);
  }
}

/**
 * 
 * @param {string} action 
 * @param {FormData} data 
 */
function getData(data) {
  axios.get('../index.php', { params:data })
  .then((response) => {
    if (response.data !== undefined) {
        document.getElementById("div_table").style.display = "";
        insertIntoTable(response.data);
        Object.assign(data_collected, response.data);
        processed++;

        var count_instances = all_instances_selected ? all_instances.length : choices.getValue(true).length;
        if (processed === count_instances) {
            endSearch();
            
            if (processed > 1) {
                insertIntoFooter();
            }
        }
    }
  }, (error) => {
    console.log(error);
  });
}

/**
 * Insert the incoming data in a new row of the table
 * @param {Object} data 
 */
function insertIntoTable(data) {
    var columns = [
        'date',
        'sessions_total',
        'sessions_found',
        'sessions_no_clicks',
        'sessions_not_found',
        'uqs_total',
        'uqs_found',
        'uqs_without_click',
        'uqs_without_answer'
    ];
    var tbody = document.getElementById('data_table');
    var new_row = '<tr>';
    var class_cell = '';
    Object.keys(data).forEach(key => {
        new_row += '<td>'+key+'</td>';
        for (var i=0; i < columns.length; i++) {
            class_cell = columns[i].indexOf('uqs') >= 0 ? 'userQstnCol' : (columns[i] === 'date' ? '' : ' sessionCol');
            new_row += '<td class="' + class_cell + '">'+data[key][columns[i]]+'</td>';
        }
    });
    new_row += '</tr>';
    tbody.innerHTML += new_row;
}

/**
 * Insert totals on the table footer (only when there are more than one inscance selected)
 */
function insertIntoFooter() {
    var columns = {
        sessions_total: 0,
        sessions_found: 0,
        sessions_no_clicks: 0,
        sessions_not_found: 0,
        uqs_total: 0,
        uqs_found: 0,
        uqs_without_click: 0,
        uqs_without_answer: 0
    };
    var totals_info = {
        totals: columns
    };

    Object.keys(data_collected).forEach(key => {
        Object.keys(columns).forEach(key2 => {
            columns[key2] += data_collected[key][key2+'_raw'];
        });
    });
    var session_total = columns.sessions_total;
    var uqs_total = columns.uqs_total;
    var total_to_process = 0;

    var tfooter = document.getElementById('data_table_footer');
    var row = '<tr>';
    row += '<th colspan="2" class="text-right">'+lang_tags.table_total+' </th>';
    Object.keys(columns).forEach(key => {
        total_to_process = key.indexOf('uqs') >= 0 ? uqs_total : session_total;
        if (total_to_process > 0) {
            if (key.indexOf('total') >= 0) {
                row += '<th class="text-center">100%<br>('+new Intl.NumberFormat('en-US').format(columns[key])+')</th>';
                totals_info.totals[key] = '100%<br>('+new Intl.NumberFormat('en-US').format(columns[key])+')';
            }
            else {
                row += '<th class="text-center">'+((columns[key] * 100) / total_to_process).toFixed(2)+'%<br>('+new Intl.NumberFormat('en-US').format(columns[key])+')</th>';
                totals_info.totals[key] = ((columns[key] * 100) / total_to_process).toFixed(2)+'%<br>('+new Intl.NumberFormat('en-US').format(columns[key])+')';
            }
        }
        else {
            row += '<th></th>';
            totals_info.totals[key] = '';
        }
    });
    row += '</tr>';
    tfooter.innerHTML = row;
    Object.assign(data_collected, totals_info);
}

/**
 * Get a list of configured instances
 */
function getInstances() {
  axios.get('./index.php', {params: {action: 'instances'}}).then(function(res) {
    var instances = res.data;
    all_instances = instances;
    var dataArray = [];
    var tag_all_instances = lang_tags.select_all_instances !== undefined ? lang_tags.select_all_instances : 'All instances';
    dataArray.push({ label: tag_all_instances, value: 'all_instances'});
    for (var index = 0; index < instances.length; index++) {
      dataArray.push({ label: instances[index], value: instances[index]}); 
    }
    choices = new Choices(document.querySelector('#select-instances'), {
      choices: dataArray,
      removeItemButton: true,
      shouldSort: false,
    });
  });
}

/**
 * Download the XLS report
 */
function downloadReportXLS() {
    document.getElementById("div_loader").style.display = "";
    disableDownload();

    var data = {
        action: 'downloadXLS',
        data: data_collected
    };
    axios({
        url: '../index.php',
        method: 'POST',
        data: data,
        responseType: 'blob',
    }).then((response) => {
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'report.xls');
        document.body.appendChild(link);
        link.click();
        document.getElementById("div_loader").style.display = "none";
        enableDownload();
    });
}


/**
 * Download the CSV report
 */
function downloadReportCSV() {
    document.getElementById("div_loader").style.display = "";
    disableDownload();

    var data = {
        action: 'downloadCSV',
        data: data_collected
    };
    axios({
        url: '../index.php',
        method: 'POST',
        data: data,
        responseType: 'blob',
    }).then((response) => {
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'report.csv');
        document.body.appendChild(link);
        link.click();
        document.getElementById("div_loader").style.display = "none";
        enableDownload();
    });
}

/**
 * Validate if the selected instance is "All"
 * @param {Dom element} element 
 */
function validateSelectedInstances(element) {
    all_instances_selected = false;
    var len = element.options.length;
    if (len > 0) {
        var last = element.options[len - 1].attributes[0].value;
        if (last === 'all_instances') {
            all_instances_selected = true;
        }
    }
    if (all_instances_selected) {
        document.getElementById("date-range").focus();
    }
}
