<?php

namespace PhaRouter;

/**
* Very simple class for create controllers. 
*
* The __construct method obtain the father route for access to methods how functions for make controller urls.
*/

class Controller {

	protected $route;

	public function __construct($route)
	{

		$this->route=$route;

	}

}

?>