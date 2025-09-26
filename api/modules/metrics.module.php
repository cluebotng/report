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
        global $mysql;

        $output = "";

        // Reports
        $output .= "# HELP cbng_report_number_of_reports_by_status Number of reports in by status\n";
        $output .= "# TYPE cbng_report_number_of_reports_by_status gauge\n";

        $report_count_by_status = mysqli_query($mysql, "SELECT COUNT(*) as `count`, `status` FROM `reports` GROUP BY `status`");
        while ($row = mysqli_fetch_assoc($report_count_by_status)) {
            $output .= "cbng_report_number_of_reports_by_status{status=\"" . (STATUSES[$row["status"]] ?? '') . "\"} " . $row["count"] . "\n";
        }

        // Vandalism
        $output .= "# HELP cbng_bot_number_of_vandalised_edits Number of vandalised edits detected\n";
        $output .= "# TYPE cbng_bot_number_of_vandalised_edits gauge\n";

        $vandalism_count = mysqli_query($mysql, "SELECT COUNT(*) as `count`, `reverted` FROM `vandalism` GROUP BY `reverted`");
        while ($row = mysqli_fetch_assoc($vandalism_count)) {
            $output .= "cbng_bot_number_of_vandalised_edits{reverted=\"" . $row["reverted"] . "\"} " . $row["count"] . "\n";
        }

        // Beaten
        $output .= "# HELP cbng_bot_number_of_reverts_beaten Number of attempted reverts that where beaten\n";
        $output .= "# TYPE cbng_bot_number_of_reverts_beaten gauge\n";

        $row = mysqli_fetch_assoc(mysqli_query($mysql, "SELECT COUNT(*) as `count` FROM `beaten`"));
        $output .= "cbng_bot_number_of_reverts_beaten " . $row["count"] . "\n";

        return $output;
    }
}

ApiModule::register("metrics", "Metrics");
