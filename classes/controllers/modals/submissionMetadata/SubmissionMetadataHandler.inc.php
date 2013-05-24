<?php

/**
 * @file classes/controllers/modals/submissionMetadata/SubmissionMetadataHandler.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionMetadataHandler
 * @ingroup classes_controllers_modals_submissionMetadata
 *
 * @brief Base class for submission metadata view/edit operations
 */

import('classes.handler.Handler');

// import JSON class for use with all AJAX requests
import('lib.pkp.classes.core.JSONMessage');

class SubmissionMetadataHandler extends Handler {
	/**
	 * Constructor.
	 */
	function SubmissionMetadataHandler() {
		parent::Handler();
	}


	//
	// Implement template methods from PKPHandler.
	//
	/**
	 * @see PKPHandler::initialize()
	 */
	function initialize($request) {
		$this->setupTemplate($request);
	}


	//
	// Public handler methods
	//
	/**
	 * Display the submission's metadata
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function fetch($request, $args, $params = null) {
		// Identify the submission
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_SUBMISSION);

		// Identify the stage, if we have one.
		$stageId = $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);

		// prevent anyone but managers and editors from submitting the catalog entry form
		$userRoles = $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);
		if (!array_intersect(array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR), $userRoles)) {
			$params['hideSubmit'] = true;
			$params['readOnly'] = true;
		}

		// Form handling
		$submissionMetadataViewForm = $this->getFormInstance($submission->getId(), $stageId, $params);

		$submissionMetadataViewForm->initData($args, $request);

		$json = new JSONMessage(true, $submissionMetadataViewForm->fetch($request));
		return $json->getString();
	}

	/**
	 * Save the submission metadata form.
	 * @param $args array
	 * @param $request Request
	 */
	function saveForm($args, $request) {
		$submissionId = $request->getUserVar('submissionId');

		// Form handling
		$submissionMetadataViewForm = $this->getFormInstance($submissionId);

		$json = new JSONMessage();

		// Try to save the form data.
		$submissionMetadataViewForm->readInputData($request);
		if($submissionMetadataViewForm->validate()) {
			$submissionMetadataViewForm->execute($request);
			// Create trivial notification.
			$notificationManager = new NotificationManager();
			$user = $request->getUser();
			$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => __('notification.savedSubmissionMetadata')));
		} else {
			$json->setStatus(false);
		}

		return $json->getString();
	}

	/**
	 * Get an instance of the metadata form to be used by this handler.
	 * @param $submissionId int
	 * @return Form
	 */
	function getFormInstance($submissionId, $stageId = null, $params = null) {
		import('controllers.modals.submissionMetadata.form.CatalogEntrySubmissionReviewForm');
		return new CatalogEntrySubmissionReviewForm($submissionId, $stageId, $params);
	}
}

?>
