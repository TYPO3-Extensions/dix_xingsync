<?php

class ux_tx_dixeasylogin_oauth1 extends tx_dixeasylogin_oauth1 {

	function getUserInfo($accessTokenParams, &$error) {
		$endpoint = $this->provider['requestProfileUrl'];
		$markerNames = $this->extractMarker($endpoint);
		foreach ($markerNames as $v) {
			$endpoint = str_replace('###'.$v.'###', $accessTokenParams[$v], $endpoint);
		}
		$tokenObj = t3lib_div::makeInstance('OAuthConsumer', $this->oauth_token, $this->oauth_token_secret);
	  $req = OAuthRequest::from_consumer_and_token($this->consumer, $tokenObj, "GET", $endpoint, array());
	  $req->sign_request($this->sigMethod, $this->consumer, $tokenObj);

		$response = tx_dixeasylogin_div::makeCURLRequest((string)$req, 'GET', array());
		$details = json_decode($response, true);
		if ($details['users']) { $details = $details['users']; } // when the details are stored in an object capsulated in an array capsulated in an object (xing)
		if ($details[0]) { $details = $details[0]; } // when the details are stored in an object capsulated in an array (twitter)
		
		$userinfo = array();
		foreach ($this->provider['profileMap.'] as $dbField => $detailsField) {
			$userinfo[$dbField] = $details[$detailsField];
		}
		if (!$userinfo['id']) {
			$error = $GLOBALS['piObj']->pi_getLL('error_getting_userinfo'); // Error: While retrieving user details, the user id was empty
		}
		$userinfo['id'] = 'oauth1-'.$this->provider['key'].'-'.$userinfo['id'];

		/* NEW */
		$storeData = array(
			'userData' => $details,
			'provider' => $this->provider,
			'timestamp' => time(),
			'id' => $userinfo['id'],
		);
		$GLOBALS["TSFE"]->fe_user->setKey("ses", "xingsync", serialize($storeData));
		$GLOBALS["TSFE"]->fe_user->storeSessionData();
		/* NEW end */

		return $userinfo;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dix_xingsync/class.ux_tx_dixeasylogin_oauth1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dix_xingsync/class.ux_tx_dixeasylogin_oauth1.php']);
}

?>