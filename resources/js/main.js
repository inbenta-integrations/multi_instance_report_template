var form;
var choices;
var processed = 0;
var dataCollected = {};
var allInstances = [];
var allInstancesSelected = false;
var lang = '';
var langTags = {};

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
var getLanguage = function (callback) {
    lang = 'en'; //Set the default lang, in case there is no one configured
    axios.get('./index.php', { params: { action: 'language' } }).then(function (response) {
        if (response.data !== undefined) {
            if (Object.keys(response.data).length > 0) {
                lang = response.data.lang;
                langTags = response.data.tags;
                Object.keys(langTags).forEach(key => {
                    if (key.indexOf('select_') == -1 && key.indexOf('placeholder_') == -1 && key.indexOf('table_') == -1) {
                        document.getElementById(key).innerHTML = langTags[key];
                    }
                    else if (key.indexOf('placeholder_') >= 0) {
                        document.getElementById(key.replace('placeholder_', '')).placeholder = langTags[key];
                    }
                });
                callback();
                return;
            }
        }
        callback();
    }).catch(function () {
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
var getCalendarLang = function (callback, param) {
    var script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = 'https://npmcdn.com/flatpickr/dist/l10n/' + lang + '.js';
    document.getElementsByTagName('head')[0].appendChild(script);
    setTimeout(function () {
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
    dataCollected = {};
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
    if (allInstancesSelected) {
        instances = allInstances;
    }

    startSearch();
    for (var i = 0; i < instances.length; i++) {
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
    axios.get('../index.php', { params: data })
        .then((response) => {
            if (response.data !== undefined) {
                document.getElementById("div_table").style.display = "";
                insertIntoTable(response.data);
                Object.assign(dataCollected, response.data);
                processed++;

                var countInstances = allInstancesSelected ? allInstances.length : choices.getValue(true).length;
                if (processed === countInstances) {
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
        'sessions_not_found',
        'uqs_total',
        'uqs_found',
        'uqs_without_click',
        'uqs_without_answer'
    ];
    var tbody = document.getElementById('data_table');
    var newRow = '<tr>';
    var classCell = '';
    Object.keys(data).forEach(key => {
        newRow += '<td>' + key + '</td>';
        for (var i = 0; i < columns.length; i++) {
            classCell = columns[i].indexOf('uqs') >= 0 ? 'userQstnCol' : (columns[i] === 'date' ? '' : ' sessionCol');
            newRow += '<td class="' + classCell + '">' + data[key][columns[i]] + '</td>';
        }
    });
    newRow += '</tr>';
    tbody.innerHTML += newRow;
}

/**
 * Insert total on the table footer (only when there are more than one inscance selected)
 */
function insertIntoFooter() {
    var columns = {
        sessions_total: 0,
        sessions_found: 0,
        sessions_not_found: 0,
        uqs_total: 0,
        uqs_found: 0,
        uqs_without_click: 0,
        uqs_without_answer: 0
    };
    var totalInfo = {
        total: columns
    };

    Object.keys(dataCollected).forEach(key => {
        Object.keys(columns).forEach(key2 => {
            columns[key2] += isNaN(dataCollected[key][key2 + '_raw']) ? 0 : dataCollected[key][key2 + '_raw'];
        });
    });
    var sessionTotal = columns.sessions_total;
    var uqsTotal = columns.uqs_total;
    var totalToProcess = 0;

    var tfooter = document.getElementById('data_table_footer');
    var row = '<tr>';
    row += '<th colspan="2" class="text-right">' + langTags.table_total + ' </th>';
    Object.keys(columns).forEach(key => {
        totalToProcess = key.indexOf('uqs') >= 0 ? uqsTotal : sessionTotal;
        if (totalToProcess > 0) {
            if (key.indexOf('total') >= 0) {
                row += '<th class="text-center">100%<br>(' + new Intl.NumberFormat('en-US').format(columns[key]) + ')</th>';
                totalInfo.total[key] = '100%<br>(' + new Intl.NumberFormat('en-US').format(columns[key]) + ')';
            }
            else {
                row += '<th class="text-center">' + ((columns[key] * 100) / totalToProcess).toFixed(2) + '%<br>(' + new Intl.NumberFormat('en-US').format(columns[key]) + ')</th>';
                totalInfo.total[key] = ((columns[key] * 100) / totalToProcess).toFixed(2) + '%<br>(' + new Intl.NumberFormat('en-US').format(columns[key]) + ')';
            }
        }
        else {
            row += '<th></th>';
            totalInfo.total[key] = '';
        }
    });
    row += '</tr>';
    tfooter.innerHTML = row;
    Object.assign(dataCollected, totalInfo);
}

/**
 * Get a list of configured instances
 */
function getInstances() {
    axios.get('./index.php', { params: { action: 'instances' } }).then(function (res) {
        var instances = res.data;
        allInstances = instances;
        var dataArray = [];
        var tagAllInstances = langTags.select_all_instances !== undefined ? langTags.select_all_instances : 'All instances';
        dataArray.push({ label: tagAllInstances, value: 'allInstances' });
        for (var index = 0; index < instances.length; index++) {
            dataArray.push({ label: instances[index], value: instances[index] });
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
        data: dataCollected
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
        data: dataCollected
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
    allInstancesSelected = false;
    var len = element.options.length;
    if (len > 0) {
        var last = element.options[len - 1].attributes[0].value;
        if (last === 'allInstances') {
            allInstancesSelected = true;
        }
    }
    if (allInstancesSelected) {
        document.getElementById("date-range").focus();
    }
}
