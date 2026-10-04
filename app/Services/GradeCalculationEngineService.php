<?php

namespace App\Services;

/**
 * GradeCalculationEngineService
 * Legacy alias extending the authoritative LibyanOfficialGradingEngine.
 */
class GradeCalculationEngineService extends LibyanOfficialGradingEngine
{
    // Inherits all Libyan grading rules, 40% final minimum, 50% grand pass, and fraction rounding rule.
}
