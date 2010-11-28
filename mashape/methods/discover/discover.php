<?php

/*
 * Mashape PHP library.
 *
 * Copyright (C) 2010 Mashape, Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * The author of this software is Mashape, Inc.
 * For any question or feedback please contact us at: support@mashape.com
 *
 */

require_once(dirname(__FILE__) . "/../iMethodHandler.php");
require_once(dirname(__FILE__) . "/../../configuration/restConfigurationLoader.php");
require_once(dirname(__FILE__) . "/../../net/httpUtils.php");
require_once(dirname(__FILE__) . "/../../init/init.php");
require_once(dirname(__FILE__) . "/helpers/discoverMethods.php");
require_once(dirname(__FILE__) . "/helpers/discoverObjects.php");

define("HEADER_SERVER_KEY", "X-Mashape-Server-Key");

class Discover implements iMethodHandler {

	public function handle($instance, $parameters, $httpRequestMethod) {
		// Validate HTTP Verb
		if (strtolower($httpRequestMethod) != "get") {
			throw new MashapeException(EXCEPTION_INVALID_HTTPMETHOD, EXCEPTION_INVALID_HTTPMETHOD_CODE);
		}
		// Validate request
		$configuration = RESTConfigurationLoader::reloadConfiguration();
		if ($this->validateRequest($configuration) == false) {
			throw new MashapeException(EXCEPTION_AUTH_INVALID_SERVERKEY, EXCEPTION_AUTH_INVALID_SERVERKEY_CODE);
		}

		$resultJson = "{";

		$objectsFound = array();
		$methods = discoverMethods($instance, $configuration, $objectsFound);
		$objects = discoverObjects($configuration, $objectsFound);
		$pluginVersion = '"version":"' . LIBRARY_VERSION . '"';

		$resultJson .= $methods . "," . $objects . "," . $pluginVersion;

		$resultJson .= "}";
		return $resultJson;
	}

	private function validateRequest($configuration) {
		// If the request comes from the local computer, then don't require authorization,
		// otherwise check the headers
		if (HttpUtils::isLocal()) {
			return true;
		} else {
			$serverkey =  $configuration->getServerkey();
			$providedServerkey = HttpUtils::getHeader(HEADER_SERVER_KEY);
			if (empty($serverkey)) {
				throw new MashapeException(EXCEPTION_EMPTY_SERVERKEY, EXCEPTION_XML_CODE);
			}
			if ($providedServerkey != null && md5($serverkey) == $providedServerkey) {
				return true;
			}
			return false;
		}
	}

}

?>