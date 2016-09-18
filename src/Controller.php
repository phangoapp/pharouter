<?php

namespace PhangoApp\PhaRouter;

/**
* Very simple class for create controllers. 
*
* The __construct method obtain the father route for access to methods how functions for make controller urls.
*/

class Controller {

	protected $route;
	protected $name="";

	public function __construct($route, $name, $yes_view=1)
	{

		$this->route=$route;
		$this->name=$name;
		
		if($yes_view==1)
		{
		
			\PhangoApp\PhaView\View::$folder_env[]='vendor/'.Routes::$prefix_path[$this->name].$this->name.'/views';
			\PhangoApp\PhaView\View::$media_env[]='vendor/'.Routes::$prefix_path[$this->name].$this->name;
		
		}

	}

}

