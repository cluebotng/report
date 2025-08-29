<?php

namespace ReportApi;

/*
 * Metrics
 * - Returns basic metrics formatted for Prometheus
 */

class Metrics extends ApiModule
{
    public function header()
    {
        header("Content-Type: text/plain; version=0.0.4; charset=utf-8");
    }

    public function content()
    {
        global $statuses, $mysql;

        $output = "";

        $output .= "# HELP cbng_report_number_of_reports_by_status Number of reports in by status\n";
        $output .= "# TYPE cbng_report_number_of_reports_by_status gauge\n";

        $report_count_by_status = mysqli_query($mysql, "SELECT COUNT(*) as `count`, `status` FROM `reports` GROUP BY `status`");
        while ($row = mysqli_fetch_assoc($report_count_by_status)) {
            $output .= "cbng_report_number_of_reports_by_status{status=\"" . $statuses[$row["status"]] . "\"} " . $row["count"] . "\n";
        }

        return $output;
    }
}

ApiModule::register("metrics", "Metrics");
