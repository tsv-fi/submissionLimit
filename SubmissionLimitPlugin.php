<?php

/**
 * @file plugins/generic/submissionLimit/SubmissionLimitPlugin.php
 *
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionLimitPlugin
 *
 * @ingroup plugins_generic_submissionLimit
 *
 * @brief Only allow editorial users to create new submissions with an API key.
 */

namespace APP\plugins\generic\submissionLimit;

use APP\core\Application;
use PKP\core\APIRouter;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\security\Role;

class SubmissionLimitPlugin extends GenericPlugin
{
    // Roles that can create submissions
    public const ALLOWED_ROLES = [
        Role::ROLE_ID_MANAGER,
        Role::ROLE_ID_SUB_EDITOR,
        Role::ROLE_ID_ASSISTANT,
    ];

    /**
     * @copydoc Plugin::register()
     */
    public function register($category, $path, $mainContextId = null): bool
    {
        $success = parent::register($category, $path, $mainContextId);

        if ($success && $this->getEnabled($mainContextId)) {
            Hook::add('Submission::validate', [$this, 'validateNewSubmission']);
        }

        return $success;
    }

    /**
     * Block new submissions created with an API key by users without an editorial role.
     */
    public function validateNewSubmission(string $hookName, array $args): bool
    {
        $errors = &$args[0];
        $submission = $args[1];

        if ($submission !== null) {
            return Hook::CONTINUE;
        }

        $request = Application::get()->getRequest();
        if (!$this->isApiKeyRequest($request)) {
            return Hook::CONTINUE;
        }

        $context = $request->getContext();
        $user = $request->getUser();
        if (!$context || !$user) {
            return Hook::CONTINUE;
        }

        // Check if the user has an allowed role in the current context or is a site admin
        $isAllowed = $user->hasRole(self::ALLOWED_ROLES, $context->getId())
            || $user->hasRole([Role::ROLE_ID_SITE_ADMIN], Application::CONTEXT_SITE);

        if (!$isAllowed) {
            $errors['submissionLimit'] = [__('plugins.generic.submissionLimit.error.apiKey')];
            error_log("[SubmissionLimit] Blocked new submission via API key for user {$user->getId()} ({$user->getUsername()}) in context {$context->getId()}");
        }

        return Hook::CONTINUE;
    }

    /**
     * Was the current request authenticated with an API key?
     */
    protected function isApiKeyRequest($request): bool
    {
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
    public function getDisplayName(): string
    {
        return __('plugins.generic.submissionLimit.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription(): string
    {
        return __('plugins.generic.submissionLimit.description');
    }
}
