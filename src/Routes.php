<?php

namespace PhangoApp\PhaRouter;
use PhangoApp\PhaUtils\Utils;

/**
* A class used for create routes to a folder containing controllers folder
*
* You can use this class for create simple routes. Its best feature is to create an modular app based in folders with a controller folder where you place the controllers of your app. Only need a index php file where you use this class for create the routes to your controllers.
*
* The format of a url using Routes class is http://www.domain.com/app/controller_file/method/{value1,value2...}
*/

class Routes
{

	/**
	* Root path for includes app folders
	*/
	
	static public $root_path=__DIR__;
	
	/**
	* Php document root
	*/
	
	static public $base_path=__DIR__;
	
	/**
	* Principal php file. If rewrite, put to ''
	*/
	
	static public $base_file='index.php';
	
	/**
	* The root folder of php base file, tipically / or /example/
	*/
	
	static public $root_url='/';

	/**
	* The folder base
	*/

	static public $app='app';
	
	/**
	* The controllers folder into of base folder
	*/
	
	public $folder_controllers='controllers';
	
	/**
	* The prefix controller name
	*/
	
	public $prefix_controller='';
	
	/**
	* 404 controller
	*/
	
	public $default_404=array();
	
	/**
	* An array with a list of apps in the system.
	*/
	
	static public $apps=array();

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
	
	/**
	* Type petition
	*/
	
	public $request_method='';
	
	public function __construct()
	{
	
		//Routes::$app=$app;
		$this->default_home=array('controller' => 'index', 'method' => 'index', 'values' => array());
		$this->default_404=array('controller' => '404', 'method' => 'index', 'values' => array());
		
		Routes::$root_path=getcwd();
		Routes::$base_path=Routes::$root_path;
		
		$this->request_method=$_SERVER['REQUEST_METHOD'];
		
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
	
	public function add_routes($controller, $method='index', $values=array())
	{
	
		$this->arr_routes[$controller][$method]=$values;
	
	}
	
	/**
	* Method for add the routes values.
	*
	* With this method you can load
	*
	*/
	
	public function add_routes_apps()
	{
	
		/*foreach(Routes::$apps as $app)
		{*/
		
		$file_path=Routes::$root_path.'/'.Routes::$app.'/'.$this->folder_controllers.'/routes.php';
		
		if(is_file($file_path))
		{
			include($file_path);
			
			$func_name='obtain_routes_from_'.Routes::$app;
			
			$this->arr_routes=$func_name($this);
			
		}
			
		//}
	
	}
	
	public function ret_routes()
	{
	
		return $this->arr_routes;
	
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
		
		if(Routes::$root_url!='/')
		{
		
			$url=str_replace(Routes::$root_url, '', $url);
		
		}
		
		$url=str_replace(Routes::$base_file, '', $url);
		
		$url=substr($url, 1);
		
		$last_slash=substr($url, -1);
		
		if($last_slash=='/')
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

					$_GET[$arr_variables[$x]]=Utils::form_text(urldecode(substr($arr_variables[$x+1], 0, 255)));
					

				}

			}
		
		}
		
		//Delete $_GET elements.
	
		$url=preg_replace('/\/\?.*$/', '', $url);
		
		//Delete get elements.
		//Here can make a optimitation with the split used in obtain get...
		
		$url=preg_replace('/\/get\/.*$/', '', $url);
		
		$arr_url=explode('/', $url);
		
		//Set defaults or chosen controllers
		
		$controller='index';
		$method='index';
		$params=array();
		
		$method_path_index=2;
		
		//Checking of path url...
		
		$arr_url[0]=trim($arr_url[0]);
		
		if(isset($arr_url[0]) && $arr_url[0]!='')
		{
		
			Routes::$app=Utils::slugify($arr_url[0], $respect_upper=1, $replace_space='-', $replace_dot=1, $replace_barr=1);
			
		}
		
		$path_controller=Routes::$root_path.'/'.Routes::$app.'/'.$this->folder_controllers.'/controller_'.$controller.'.php';
		
		if(isset($arr_url[1]) && $arr_url[1]!='')
		{
		
			$controller=Utils::slugify($arr_url[1], $respect_upper=1, $replace_space='-', $replace_dot=1, $replace_barr=1);
			
			//Check is exists file controller, if not, use $arr_url[2] how path friends.
			
			$path_controller=Routes::$root_path.'/'.Routes::$app.'/'.$this->folder_controllers.'/controller_'.$controller.'.php';
			
			if(!file_exists($path_controller))
			{
			
				if(isset($arr_url[$method_path_index]))
				{
				
					$folder_controller=$controller;
					$controller=$arr_url[$method_path_index];
				
					$path_controller=Routes::$root_path.'/'.Routes::$app.'/'.$this->folder_controllers.'/'.$folder_controller.'/controller_'.$controller.'.php';
					
					$method_path_index++;
				
				}
			
			}
			
			if(isset($arr_url[$method_path_index]))
			{
			
				$method=Utils::slugify($arr_url[2], $respect_upper=1, $replace_space='-', $replace_dot=1, $replace_barr=1);;
				
			}
			
		}
		
 		
		if(is_file($path_controller) && in_array(Routes::$app, Routes::$apps))
		{
			
			
			//Pass this route to the controller.
			
			include($path_controller);
			
			$controller_name=$controller.$this->prefix_controller.'Controller';
			
			//Check if class exists
			
			if(class_exists($controller_name) && method_exists($controller_name, $method))
			{
			
				$controller_class=new $controller_name($this);
				
				$p = new \ReflectionMethod($controller_name, $method); 
						
				$num_parameters=$p->getNumberOfRequiredParameters();
				
				$num_parameters_total=count($p->getParameters());
				
				$c=$method_path_index+1;
				$x=0;
				
				$arr_values=array_slice($arr_url, $c);
				
				settype($this->arr_routes[$controller][$method], 'array');
				
				$c_param=count($arr_values);
				
				if($c_param<=$num_parameters_total && $c_param>=$num_parameters)
				{
				
					for($x=0;$x<$c_param;$x++)
					{
					
						settype($arr_url[$c], 'string');
						settype($this->arr_routes[$controller][$method][$x], 'string');
						
						if($this->arr_routes[$controller][$method][$x]=='')
						{
						
							$this->arr_routes[$controller][$method][$x]='check_string';
							
						
						}
						
						$format_func=$this->arr_routes[$controller][$method][$x];
						
						$params[]=$this->$format_func($arr_url[$c]);
				
						$c++;
						//$x++;
					
					}
					
					if(!call_user_func_array(array($controller_class, $method), $params)===false)
					{
						
						throw new Exception('Not exists method in this controller');
					
					}
				
				}
				
				/*if($num_parameters==$c_param)
				{
					
					//Make a foreach for check parameters passed to the method
					
					$parameters=$p->getParameters();
					
					for($x=0;$x<$c_param;$x++)
					{
					
						settype($arr_url[$c], 'string');
						settype($this->arr_routes[$controller][$method][$x], 'string');
						
						if($this->arr_routes[$controller][$method][$x]=='')
						{
						
							$this->arr_routes[$controller][$method][$x]='check_string';
							
						
						}
						
						$format_func=$this->arr_routes[$controller][$method][$x];
						
						$params[]=$this->$format_func($arr_url[$c]);
				
						$c++;
						//$x++;
					
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
				
				}*/
				
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
		
		$url404=$this->make_url($this->default_404['controller'], $this->default_404['method'], $this->default_404['values']);
		
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
	
	public function check_integer($value)
	{
	
		settype($value, 'integer');
		
		return $value;
	
	}
	
	/**
	* Method used for check integer values for a controller method
	*/
	
	public function check_string($value)
	{
	
		//Normalize the text...
		return Utils::slugify($value, 1);
	
	}
	
	/**
	* Method for create urls for this route.
	*/
	
	static public function make_url($controller, $method='index', $values=array(), $get=array())
	{
	
		
	
		return Routes::$root_url.Routes::$base_file.'/'.Routes::$app.'/'.$controller.'/'.$method.'/'.implode('/', $values);
	
	}
	
	/**
	* Method for create urls for all routes in the site.
	*/
	
	static public function make_module_url($app, $controller, $method='index', $values=array(), $get=array())
	{
		$url_fancy=Routes::$root_url.Routes::$base_file.'/'.$app.'/'.$controller.'/'.$method.'/'.implode('/', $values);
		
		$url=Routes::add_get_parameters($url_fancy, $get);
	
		return $url;
	
	}
	
	/**
	* Method for create urls for all routes in differents sites.
	*/
	
	static public function make_direct_url($base_url, $app, $controller, $method='index', $values=array(), $get=array())
	{
	
		$url_fancy=$base_url.'/'.$app.'/'.$controller.'/'.$method.'/'.implode('/', $values);
		
		$url=Routes::add_get_parameters($url_fancy, $get);
		
		return $url;
	
	}
	
	/**
	* Function used for add get parameters to a well-formed url based on make_fancy_url, make_direct_url and others.
	*
	* @param string $url_fancy well-formed url
	* @param string $arr_data Hash with format key => value. The result is $_GET['key']=value
	*/

	static public function add_get_parameters($url_fancy, $arr_data)
	{

		$arr_get=array();
		
		$sep='';
		
		$get_final='';
		
		if(count($arr_data)>0)
		{

			foreach($arr_data as $key => $value)
			{

				$arr_get[]=$key.'/'.$value;

			}

			$get_final=implode('/', $arr_get);

			$sep='/get/';

			if(preg_match('/\/$/', $url_fancy))
			{

				$sep='get/';

			}
			
			
			if(preg_match('/\/get\//', $url_fancy))
			{

				$sep='/';

			}
			
		}

		return $url_fancy.$sep.$get_final;

	}
	
	/**
	* Method for redirects.
	*/
	
	static public function redirect($url)
	{
	
		header('Location: '.$url);
	
		die;
	
	}

}


