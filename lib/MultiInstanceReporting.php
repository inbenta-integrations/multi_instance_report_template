<?php

namespace Inbenta;

use Inbenta\ReportingApi\ReportingApiClient;


class MultiInstanceReporting
{
	protected $apiClients, $config;
	protected $lang;
	protected $langTags;

	/**
	 * Creates a new instance
	 * @param array $config
	 * @param string $dir
	 */
	function __construct(array $config, string $dir)
	{
		$this->config = $config;
		$this->setLanguage();
		$this->setLanguageTags($dir);
	}

	/**
	 * Set an API Client for each instance set in the config
	 * @return void
	 */
	private function setApiClients()
	{
		foreach ($this->config['instances'] as $key => $instance) {
			$this->apiClients[$key] = new ReportingApiClient(
				$instance['apiKey'],
				$instance['secretKey'],
				$instance['signatureKey']
			);
		}
	}

	/**
	 * Set the default language
	 * @return void
	 */
	private function setLanguage()
	{
		$this->lang = $this->config['lang'];
	}

	/**
	 * Set the language tags
	 * @param string $dir
	 * @return void
	 */
	private function setLanguageTags(string $dir)
	{
		if (!is_null($this->config["lang"]) &&  $this->config["lang"] !== "") {
			$fileLang = $dir . '/lang/' . $this->config["lang"] . '.php';
			if (is_file($fileLang)) {
				$lang = require_once $fileLang;
				$this->langTags = $lang;
				return;
			}
		}
		$lang = require_once $dir . '/lang/en.php';
		$this->langTags = $lang;
	}

	/**
	 * Handle the incoming request
	 * @param array $request
	 */
	public function handleRequest(array $request)
	{
		$action = $request["action"];

		switch ($action) {
			case 'language':
				echo json_encode(['lang' => $this->lang, 'tags' => $this->langTags]);
				break;
			case 'reports':
				$this->setApiClients();
				$params = [
					"date_from" => substr($request["dateFrom"], 0, 10),
					"date_to" => substr($request["dateTo"], 0, 10),
					"timezone" => "US/Pacific",
					"offset" => 0
				];

				$response = [];
				foreach ($request['instances'] as $instanceName) {
					try {
						$client = isset($this->apiClients[$instanceName]) ? $this->apiClients[$instanceName] : null;
						if (!is_null($client)) {
							$response[$instanceName] = [];

							$params["data_keys"] = "SEARCH,INSTANT_SEARCH"; //Total sessions
							$response[$instanceName]['sessions_total'] = $client->getAggregatesById('session_details', $params);

							$params["data_keys"] = "INSTANT_CLICK,CLICK_AUTOCOMPLETER,CLICK_MATCH"; //SEARCH + Found + Click
							$response[$instanceName]['sessions_clicks'] = $client->getAggregatesById('session_details', $params);

							unset($params["data_keys"]);
							$params["main_data_key"] = "SEARCH"; //TOTAL SEARCH + Not Found
							$response[$instanceName]['sessions_not_found'] = $client->getAggregatesById('session_details', $params);

							unset($params["main_data_key"]);
							$response[$instanceName]['total_uqs_by_clicks_and_matchings'] = $client->getAggregatesById('total_uqs_by_clicks_and_matchings', $params);

							if (isset($response[$instanceName]['sessions_total']->error)) {
								$response[$instanceName]['sessions_total']->total_count = $this->langTags["table_instance_connection_error"];
							}
							if (isset($response[$instanceName]['sessions_clicks']->error)) {
								$response[$instanceName]['sessions_clicks']->total_count = 0;
							}
							if (isset($response[$instanceName]['sessions_not_found']->error)) {
								$response[$instanceName]['sessions_not_found']->total_count = 0;
							}
							if (isset($response[$instanceName]['total_uqs_by_clicks_and_matchings']->error)) {
								$response[$instanceName]['total_uqs_by_clicks_and_matchings']->total_count = 0;
							}
						}
					} catch (\Throwable $th) {
						echo "error: " . $th;
						die;
					}
				}
				$data = $this->processResponse($response, $params);
				echo json_encode($data);
				break;
			case 'instances':
				echo json_encode(array_keys($this->config['instances']));
				break;

			case 'downloadXLS':
				$this->downloadXLS($request);
				break;
			case 'downloadCSV':
				$this->downloadCSV($request);
				break;
			default:
				echo json_encode('Invalid action');
				break;
		}
	}


	/**
	 * Process the response from API
	 * @param array $data
	 * @param array $dates
	 * @return array $response Data processed
	 */
	private function processResponse(array $data, array $dates)
	{
		$response = [];
		foreach ($data as $key => $instance) {
			$found = ($instance["sessions_total"]->total_count !== $this->langTags["table_instance_connection_error"])? $instance["sessions_total"]->total_count - $instance["sessions_not_found"]->total_count : 0;
			$response[$key] = [
				"date" => $dates["date_from"] . " to " . $dates["date_to"],
				"sessions_total" => $instance["sessions_total"]->total_count,
				"sessions_total_raw" => $instance["sessions_total"]->total_count,
				"sessions_found" => $found,
				"sessions_found_raw" => $found,
				"sessions_not_found" => $instance["sessions_not_found"]->total_count,
				"sessions_not_found_raw" => $instance["sessions_not_found"]->total_count,
				"uqs_total" => 0,
				"uqs_total_raw" => 0,
				"uqs_found" => 0,
				"uqs_found_raw" => 0,
				"uqs_with_click" => 0,
				"uqs_with_click_raw" => 0,
				"uqs_without_click" => 0,
				"uqs_without_click_raw" => 0,
				"uqs_without_answer" => 0,
				"uqs_without_answer_raw" => 0
			];
			if (isset($instance["total_uqs_by_clicks_and_matchings"]->results)) {
				foreach ($instance["total_uqs_by_clicks_and_matchings"]->results as $val) {
					$response[$key]["uqs_total"] += $val->num_user_questions;
					$response[$key]["uqs_found"] += ($val->num_user_questions - $val->num_without_answer);
					$response[$key]["uqs_with_click"] += $val->num_with_click;
					$response[$key]["uqs_without_click"] += $val->num_without_click;
					$response[$key]["uqs_without_answer"] += $val->num_without_answer;

					$response[$key]["uqs_total_raw"] += $val->num_user_questions;
					$response[$key]["uqs_found_raw"] += ($val->num_user_questions - $val->num_without_answer);
					$response[$key]["uqs_with_click_raw"] += $val->num_with_click;
					$response[$key]["uqs_without_click_raw"] += $val->num_without_click;
					$response[$key]["uqs_without_answer_raw"] += $val->num_without_answer;
				}
			} else {
				$response[$key]["uqs_total"] = $response[$key]["uqs_total_raw"] = $this->langTags["table_instance_connection_error"];
			}
		}

		foreach ($response as $key => $instance) {
			$sessionsTotal = 0;
			$uqsTotal = 0;
			if (($instance["sessions_total"] !== $this->langTags["table_instance_connection_error"])) {
				$sessionsTotal = $instance["sessions_total"];
				$response[$key]["sessions_total"] = "100%<br>(" . number_format($sessionsTotal) . ")";
			}
			if ($instance["uqs_total"] !== $this->langTags["table_instance_connection_error"]) {
				$uqsTotal = $instance["uqs_total"];
				$response[$key]["uqs_total"] = "100%<br>(" . number_format($uqsTotal) . ")";
			}
			foreach ($instance as $column => $val) {
				if ($column !== "date" && strpos($column, "total") === false && strpos($column, "raw") === false) {
					$totalToProcess = strpos($column, "sessions") !== false ? $sessionsTotal : $uqsTotal;
					if ($totalToProcess > 0) {
						$response[$key][$column] = number_format(($val * 100) / $totalToProcess, 2) . "%<br>(" . number_format($val) . ")";
					} else {
						$response[$key][$column] = "No data";
					}
				}
			}
		}
		return $response;
	}


	/**
	 * Generate the file XLS to download
	 * @param array $request
	 */
	private function downloadXLS(array $request)
	{
		if (isset($request["data"])) {
			$colorSession = "style='background: #e1f0fd; text-align:center;'";
			$colorUqs = "style='background: #fbfbdc; text-align:center;'";
			$table = "<table width='100%' border='1'>";
			$table .= "<tr>";
			$table .= "<th>" . $this->langTags["col_instance_name"] . "</th>";
			$table .= "<th>" . $this->langTags["col_date"] . "</th>";
			$table .= "<th " . $colorSession . ">" . $this->langTags["col_total_sessions"] . "</th>";
			$table .= "<th " . $colorSession . ">" . $this->langTags["col_found_sessions"] . "</th>";
			$table .= "<th " . $colorSession . ">" . $this->langTags["col_not_found_sessions"] . "</th>";
			$table .= "<th " . $colorUqs . ">" . $this->langTags["col_total_uqs"] . "</th>";
			$table .= "<th " . $colorUqs . ">" . $this->langTags["col_found_uqs"] . "</th>";
			$table .= "<th " . $colorUqs . ">" . $this->langTags["col_no_clicks_uqs"] . "</th>";
			$table .= "<th " . $colorUqs . ">" . $this->langTags["col_not_found_uqs"] . "</th>";
			$table .= "</tr>";
			foreach ($request["data"] as $key => $instance) {
				$table .= "<tr>";
				if ($key !== "total") {
					$table .= "<td>" . $key . "</td>";
					$table .= "<td>" . $instance["date"] . "</td>";
				} else {
					$table .= "<td colspan='2' style='text-align:right; font-weight: bold;'>" . $this->langTags["table_total"] . " </td>";
					$colorSession = "style='font-weight: bold; text-align:center;'";
					$colorUqs = "style='font-weight: bold; text-align:center;'";
				}
				$table .= "<td " . $colorSession . ">" . str_replace("<br>", " ", $instance["sessions_total"]) . "</td>";
				$table .= "<td " . $colorSession . ">" . str_replace("<br>", " ", $instance["sessions_found"]) . "</td>";
				$table .= "<td " . $colorSession . ">" . str_replace("<br>", " ", $instance["sessions_not_found"]) . "</td>";
				$table .= "<td " . $colorUqs . ">" . str_replace("<br>", " ", $instance["uqs_total"]) . "</td>";
				$table .= "<td " . $colorUqs . ">" . str_replace("<br>", " ", $instance["uqs_found"]) . "</td>";
				$table .= "<td " . $colorUqs . ">" . str_replace("<br>", " ", $instance["uqs_without_click"]) . "</td>";
				$table .= "<td " . $colorUqs . ">" . str_replace("<br>", " ", $instance["uqs_without_answer"]) . "</td>";
				$table .= "</tr>";
			}
			$table .= "</table>";

			header('Content-Disposition: attachment; filename="report.xls"');
			header('Content-type: attachment; application/vnd.ms-excel');
			echo $table;
			die;
		}
		echo json_encode(['error' => 'no_data']);
		die;
	}

	/**
	 * Generate the CSV file to download
	 * @param array $request
	 */
	private function downloadCSV(array $request)
	{
		if (isset($request["data"])) {
			$csv = $this->langTags["col_instance_name"] . ",";
			$csv .= $this->langTags["col_date"] . ",";
			$csv .= $this->langTags["col_total_sessions"] . ",";
			$csv .= $this->langTags["col_found_sessions"] . ",";
			$csv .= $this->langTags["col_not_found_sessions"] . ",";
			$csv .= $this->langTags["col_total_uqs"] . ",";
			$csv .= $this->langTags["col_found_uqs"] . ",";
			$csv .= $this->langTags["col_no_clicks_uqs"] . ",";
			$csv .= $this->langTags["col_not_found_uqs"] . "\n";

			foreach ($request["data"] as $key => $instance) {
				if ($key !== "total") {
					$csv .= $key . ",";
					$csv .= $instance["date"] . ",";
				} else {
					$csv .= " ," . $this->langTags["table_total"] . ",";
				}
				$csv .= str_replace("<br>", " ", str_replace(",", "", $instance["sessions_total"])) . ",";
				$csv .= str_replace("<br>", " ", str_replace(",", "", $instance["sessions_found"])) . ",";
				$csv .= str_replace("<br>", " ", str_replace(",", "", $instance["sessions_not_found"])) . ",";
				$csv .= str_replace("<br>", " ", str_replace(",", "", $instance["uqs_total"])) . ",";
				$csv .= str_replace("<br>", " ", str_replace(",", "", $instance["uqs_found"])) . ",";
				$csv .= str_replace("<br>", " ", str_replace(",", "", $instance["uqs_without_click"])) . ",";
				$csv .= str_replace("<br>", " ", str_replace(",", "", $instance["uqs_without_answer"])) . "\n";
			}
			header("Content-Type: text/csv");
			header("Content-Disposition: attachment; filename=report.csv");
			echo $csv;
			die;
		}
		echo json_encode(['error' => 'no_data']);
		die;
	}

	/**
	 * Show the home
	 */
	public function showHome()
	{
		require_once __DIR__ . '/../resources/index.php';
	}
}
