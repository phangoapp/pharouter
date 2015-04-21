<?php

namespace PhangoApp\PhaRouter;
use PhangoApp\PhaUtils\Utils;

/**
* A class used for create routes to a folder containing controllers folder
*
* You can use this class for create simple routes. Its best feature is to create an modular app based in folders with a controller folder where you place the controllers of your app. Only need a index php file where you use this class for create the routes to your controllers.
*
* The format of a url using Routes class is http://www.domain.com/folder_base/controller_file/method/{value1,value2...}
*/

class Routes
{

	/**
	* Root path for includes app folders
	*/
	
	public $root_path=__DIR__;

	/**
	* The .php file where is contained the routes. Tipically index.php 
	*/
	
	public $base_file='index.php';
	
	/**
	* The root folder of php base file, tipically / or /example/
	*/
	
	public $root_file='/';

	/**
	* The folder base
	*/

	public $folder_base='app';
	
	/**
	* The controllers folder into of base folder
	*/
	
	public $folder_controllers='controllers';
	
	/**
	* 404 controller
	*/
	
	public $default_404=array();

	/**
	* An array where the routes are saved
	*/
	
	protected $arr_routes=array();
	
	/**
	* Default controller, method and values when is not specified any url.
	*/
	
	public $default_home=array();
	
	/**
	* Array where beauty get variables are saved
	*/
	
	public $get=array();
	
	public function __construct($folder_base)
	{
	
		$this->folder_base=$folder_base;
		$this->default_home=array('controller' => 'index', 'method' => 'index', 'values' => array());
		$this->default_404=array('controller' => '404', 'method' => 'index', 'values' => array());
		$this->root_path=getcwd();
		//Prepare values how ip, etc...
		
		
	
	}
	
	/**
	* Method for add the routes values.
	*
	* With this method you define the checking of parameters value of the controller.
	*
	* @param string $controller The name of the controller
	* @param string $method the method loaded by the controller
	* @param array $values A set of values where is found
	*/
	
	public function addRoutes($controller, $method='index', $values=array())
	{
	
		$this->arr_routes[$controller][$method]=$values;
	
	}
	
	public function addRoutesFile($file_path)
	{
	
		include($file_path);
		
		$this->arr_routes=obtain_routes();
	
	}
	
	/**
	* Check the correspondent url and return a response from a controller
	*
	* @param string $url A string containing the url to analize for response
	* @param boolean $return_404 If true, this method return false if controller not exists, if false, return page 404.
	*/
	
	public function response($url, $return_404=1)
	{
		
		//Clean the url
		
		if($this->root_file!='/')
		{
		
			$url=str_replace($this->root_file, '', $url);
		
		}
		
		$url=str_replace($this->base_file, '', $url);
		
		$url=substr($url, 1);
		
		if(strpos($url, '/')==strlen($url)-1)
		{
			
			$url=substr($url, 0, strlen($url)-1);
		
		}
		
		//Obtain get elements 
		
		$arr_extra_get=explode('/get/', $url);
	
		//Load Get variables from url
		
		$arr_name_get=array();
		
		if(isset($arr_extra_get[1]))
		{
		
			$arr_variables=explode('/', $arr_extra_get[1]);
		
			$cget=count($arr_variables);

			if($cget % 2 !=0 ) 
			{

				$arr_variables[]='';
				$cget++;
			}

			if($cget % 2 ==0 )
			{
				//Get variables

				for($x=0;$x<$cget;$x+=2)
				{
					
					//Cut big variables...

					$this->get[$arr_variables[$x]]=Utils::form_text(urldecode(substr($arr_variables[$x+1], 0, 255)));

				}

			}
		
		}
		
		//Delete $_GET elements.
	
		$url=preg_replace('/\/\?.*$/', '', $url);
		
		//Delete get elements.
		
		$url=preg_replace('/\/get\/.*$/', '', $url);
		
		$arr_url=explode('/', $url);
		
		//Set defaults or chosen controllers
		
		$controller='index';
		$method='index';
		$params=array();
		
		$arr_url[0]=trim($arr_url[0]);
		
		if(isset($arr_url[0]) && $arr_url[0]!='')
		{
		
			$controller=$arr_url[0];
			
		}
		
		if(isset($arr_url[1]))
		{
		
			$method=$arr_url[1];
			
		}
		
		$path_controller=$this->root_path.'/'.$this->folder_base.'/'.$this->folder_controllers.'/controller_'.$controller.'.php';
// 		
		if(is_file($path_controller))
		{
			
			
			//Pass this route to the controller.
			
			include($path_controller);
			
			$controller_name=$controller.'Controller';
			
			//Check if class exists
			
			if(class_exists($controller_name) && method_exists($controller_name, $method))
			{
			
				$controller_class=new $controller_name($this);
				
				$p = new \ReflectionMethod($controller_name, $method); 
						
				$num_parameters=$p->getNumberOfRequiredParameters();
			
				$c=2;
				$x=0;
				
				$arr_values=array_slice($arr_url, $c);
				
				settype($this->arr_routes[$controller][$method], 'array');
				
				if($num_parameters==count($arr_values))
				{
			
					//Make a foreach for check parameters passed to the method
					
					foreach($p->getParameters() as $parameter)
					{
						settype($arr_url[$c], 'string');
						settype($this->arr_routes[$controller][$method][$x], 'string');
						
						if($this->arr_routes[$controller][$method][$x]=='')
						{
						
							$this->arr_routes[$controller][$method][$x]='checkString';
							
						
						}
						
						$format_func=$this->arr_routes[$controller][$method][$x];
						
						$params[]=$this->$format_func($arr_url[$c]);
				
						$c++;
						$x++;
					
					}
					
					//Call to the method of the controller
					
					if(!call_user_func_array(array($controller_class, $method), $params)===false)
					{
					
						if($return_404==1)
						{
					
							$this->response404();
							
						}
						else
						{
						
							return false;
						
						}
					
					}
					
				}
				else
				{
				
					$this->response404();
					die;
				
				}
				
			}
			else
			{
			
				if($return_404==1)
				{
			
					$this->response404();
					
				}
				else
				{
				
					return false;
				
				}
				
			}
		
		}
		else
		{
			
			if($return_404==1)
			{
		
				$this->response404();
				
			}
			else
			{
			
				return false;
			
			}
		
		}
	
	}
	
	/**
	* If response fail, you can use this for response 404 page.
	*
	*/
	
	public function response404()
	{
	
		header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found"); 
		
		$url404=$this->makeUrl($this->default_404['controller'], $this->default_404['method'], $this->default_404['values']);
		
		//Use views for this thing.
		
		if(!$this->response($url404, 0))
		{
		
			echo 'Error: page not found...';
			
		}
		
		die;
		
		//$this->response($url404);
	}
	
	/**
	* Method used for check integer values for a controller method
	*/
	
	public function checkInteger($value)
	{
	
		settype($value, 'integer');
		
		return $value;
	
	}
	
	/**
	* Method used for check integer values for a controller method
	*/
	
	public function checkString($value)
	{
	
		//Normalize the text...
		return Utils::slugify($value, 1);
	
	}
	
	/**
	* Method for create urls for this route.
	*/
	
	public function makeUrl($controller, $method='index', $values=array())
	{
	
		
	
		return $this->root_file.$this->base_file.'/'.$controller.'/'.$method.'/'.implode('/', $values);
	
	}
	
	/**
	* Method for create urls for all routes in the site.
	*/
	
	static public function makeStaticUrl($base_file, $controller, $method='index', $values=array())
	{
	
		return $this->root_file.$base_file.'/'.$controller.'/'.$method.'/'.implode('/', $values);
	
	}

}


