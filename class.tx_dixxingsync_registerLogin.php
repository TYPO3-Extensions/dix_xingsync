<?php

class tx_dixxingsync_registerLogin {

	function process($userinfo, $details, &$parent) {
		$storeData = array(
			'userData' => $details,
			'provider' => $parent->provider,
			'timestamp' => time(),
			'id' => $userinfo['id'],
		);
		$GLOBALS["TSFE"]->fe_user->setKey("ses", "xingsync", serialize($storeData));
		$GLOBALS["TSFE"]->fe_user->storeSessionData();
	}


}