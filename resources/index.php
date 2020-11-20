<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="icon" href="https://www.inbenta.com/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <script src="../resources/js/main.js?t=20201120"></script>
    <!-- Include base CSS (optional) -->

    <!-- Include Choices CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <!-- Include Choices JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css" integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2" crossorigin="anonymous">

    <link rel="stylesheet" href="../resources/css/main.css">
    <title>Multi Instance Reporting</title>
</head>

<body>
    <div class="container">
        <div class="row">
            <div class="col-4">
                <a><img src="https://www.inbenta.com/wp-content/themes/inbenta/img/logo-inbenta.svg" alt="Inbenta" width="200"></a>
            </div>
            <div class="col-8 text-center">
                <h1 id="tag_title">Multi Instance Reporting</h1>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-12">
                <form id="form">
                    <div class="row">
                        <div class="col-12 form-group">
                            <div>
                                <label for="select-instances" id="tag_instances">Instances:</label>
                                <select name="instances" id="select-instances" placeholder="Instances" multiple onchange="validateSelectedInstances(this)" style="height: 20px;"></select>
                            </div>
                        </div>
                        <div class="col-6 form-group">
                            <div>
                                <label for="date-range" id="tag_date_range">Date range:</label>
                                <input id="date-range" class="form-control" name="date" type="text" placeholder="From - To">
                            </div>
                        </div>
                        <div class="col-2 text-center mt-4">
                            <button type="submit" id="btn_submit" class="btn btn-primary">Search</button>
                        </div>
                        <div class="col-2 text-center mt-4">
                            <button type="button" class="btn btn-secondary btn_download" id="btn_download_xls" onclick="downloadReportXLS()" disabled>Download XLS</button>
                        </div>
                        <div class="col-2 text-center mt-4">
                            <button type="button" class="btn btn-secondary btn_download" id="btn_download_csv" onclick="downloadReportCSV()" disabled>Download CSV</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <hr>
        <div class="row mt-5">
            <div class="table-responsive" id="div_table" style="display:none;">
                <table class="table table-sm table-bordered table-hover">
                    <thead>
                        <tr>
                            <th id="col_instance_name">Instance Name</th>
                            <th id="col_date">Date</th>
                            <th id="col_total_sessions" class="sessionCol">Total Sessions</th>
                            <th id="col_found_sessions" class="sessionCol">Found Sessions</th>
                            <th id="col_not_found_sessions" class="sessionCol">Not Found Sessions</th>
                            <th id="col_total_uqs" class="userQstnCol">Total User Questions</th>
                            <th id="col_found_uqs" class="userQstnCol">User Questions With Click</th>
                            <th id="col_no_clicks_uqs" class="userQstnCol">User Questions Without Click</th>
                            <th id="col_not_found_uqs" class="userQstnCol">User Questions With No Answer</th>
                        </tr>
                    </thead>
                    <tbody id="data_table">
                    </tbody>
                    <tfoot id="data_table_footer">
                    </tfoot>
                </table>
            </div>
            <div class="col-12 text-center mt-3" id="div_loader" style="display:none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
        </div>
    </div>
</body>

</html>