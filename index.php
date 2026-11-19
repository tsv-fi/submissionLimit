<?php

/**
 * @defgroup plugins_generic_submissionLimit Submission Limit Plugin
 */

/**
 * @file plugins/generic/submissionLimit/index.php
 *
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Wrapper for the Submission Limit plugin.
 *
 * @ingroup plugins_generic_submissionLimit
 */

require_once('SubmissionLimitPlugin.inc.php');

return new SubmissionLimitPlugin();
