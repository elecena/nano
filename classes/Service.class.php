<?php

/**
 * Abstract class for representing nanoPortal's application service
 *
 * $Id$
 */

abstract class Service extends NanoObject {
	function __construct(NanoApp $app) {
		parent::__construct($app);
	}
}