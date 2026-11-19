<?php

/**
 * @file plugins/generic/submissionLimit/SubmissionLimitPlugin.inc.php
 *
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionLimitPlugin
 * @ingroup plugins_generic_submissionLimit
 *
 * @brief Only allow editorial users to create new submissions with an API key.
 */

import('lib.pkp.classes.plugins.GenericPlugin');
import('lib.pkp.classes.security.Role');

class SubmissionLimitPlugin extends GenericPlugin {

	// Roles that can create submissions
	const ALLOWED_ROLES = [
		ROLE_ID_MANAGER,
		ROLE_ID_SUB_EDITOR,
		ROLE_ID_ASSISTANT,
	];

	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path, $mainContextId = null) {
		$success = parent::register($category, $path, $mainContextId);

		if ($success && $this->getEnabled()) {
			HookRegistry::register('Submission::validate', [$this, 'validateNewSubmission']);
		}

		return $success;
	}

	/**
	 * @copydoc Plugin::isSitePlugin()
	 */
	function isSitePlugin() {
		return true;
	}

	/**
	 * Block new submissions created with an API key by users without an editorial role.
	 *
	 * @param $hookName string
	 * @param $args array [&$errors, $action, $props, $allowedLocales, $primaryLocale]
	 * @return boolean
	 */
	function validateNewSubmission($hookName, $args) {
		$errors =& $args[0];
		$action = $args[1];

		if ($action !== VALIDATE_ACTION_ADD) {
			return false;
		}

		$request = Application::get()->getRequest();
		if (!$this->isApiKeyRequest($request)) {
			return false;
		}

		$context = $request->getContext();
		$user = $request->getUser();
		if (!$context || !$user) {
			return false;
		}

		// Check if the user has an allowed role in the current context or is a site admin
		$isAllowed = $user->hasRole(self::ALLOWED_ROLES, $context->getId())
			|| $user->hasRole([ROLE_ID_SITE_ADMIN], CONTEXT_SITE);

		if (!$isAllowed) {
			$errors['submissionLimit'] = [__('plugins.generic.submissionLimit.error.apiKey')];
			error_log("[SubmissionLimit] Blocked new submission via API key for user {$user->getId()} ({$user->getUsername()}) in context {$context->getId()}");
		}

		return false;
	}

	/**
	 * Was the current request authenticated with an API key?
	 *
	 * @param $request PKPRequest
	 * @return boolean
	 */
	protected function isApiKeyRequest($request) {
		$router = $request->getRouter();
		if (!($router instanceof APIRouter)) {
			return false;
		}
		$handler = $router->getHandler();
		return $handler && (bool) $handler->getApiToken();
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.submissionLimit.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.submissionLimit.description');
	}
}
