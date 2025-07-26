<?php

namespace ReportApi;

/*
 * Health Check
 * - Returns OK
 */

class HealthCheck extends ApiModule
{
    public function content()
    {
        return 'OK';
    }
}

ApiModule::register('health.check', 'HealthCheck');
