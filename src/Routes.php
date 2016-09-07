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
    * Prefix for the path
    */
    
    static public $prefix_path=[];
	
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
    * An array with a list of apps in the system.
    */
    
    static public $apps=array();
	
	/**
    * Accept easy urls. For development is cool. For deployment you can use cool urls. 
    * @warning if you set this property to 0 or false and you don't defined pretty urls, your application will crash
    */
    
    static public $accept_easy_urls=1;
    
    /**
    * An array where load the urls from a module
    */
    
    static public $urls=array();
	
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
	* An array with config_paths for load configurations.
	*/
	
	public $config_path=['settings'];
	
	/**
	* An array with callbacks to load in the end of request.
	*/
	
	public $arr_finish_callbacks=array();
	
	/**
	* Type petition
	*/
	
	static public $request_method='';
	
	/**
	* Caching routes, this boolean set if you cache the routes in external file with a hash.
	*/
	
	static public $cache_routes=false;
	
	/**
	* array where the cached routes are saved.
	*/
	
	static public $cached_routes=[];
	
	public function __construct()
	{
	
		$this->default_home=array('controller' => 'index', 'method' => 'home', 'values' => array());
		$this->default_404=array('controller' => '404', 'method' => 'home', 'values' => array());
		
		Routes::$base_path=getcwd();
		Routes::$root_path=Routes::$base_path;
		
		Routes::$request_method=$_SERVER['REQUEST_METHOD'];
		
		$this->load_config();
	
	}
	
	/**
	* Simple method for load the configuration for this lib
	*/
	
	public function load_config()
	{
	
        //Load config here
        foreach($this->config_path as $config)
        {
        
            Utils::load_config("config_routes", $config);
        
        }
	
	}
	
	/**
	* Check the correspondent url and return a response from a controller
	*
	* @param string $url A string containing the url to analize for response
	* @param boolean $return_404 If true, this method return false if controller not exists, if false, return page 404.
	*/
	
	public function response($url, $return_404=1)
	{
		//Clean the routes
		
		ob_start();
            
        Routes::$app=basename(Routes::$app);
		
        $apps=[];
        
		foreach(Routes::$apps as $key_app => $app)
		{
		
            $arr_app=explode('/', $app);
            
            settype($arr_app[1], 'string');
            
            Routes::$prefix_path[$arr_app[1]]='/'.$arr_app[0].'/';
            $apps[$key_app]=$arr_app[1];
		
		}
		
		//Clean the url
		
		//Delete $_GET elements.
    
        $url=preg_replace('/\?.*$/', '', $url);

        $c=strlen(Routes::$root_url);
        
        $url=substr($url, $c, strlen($url)-$c);
		
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
        
        $get_filtered=[];
		
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
                    $get_filtered[$arr_variables[$x]]=Utils::form_text(urldecode(substr($arr_variables[$x+1], 0, 255)));
					

				}

			}
		
		}
        
        //Drop other get variables
        
        $_GET=array_diff_key($get_filtered, $_GET);
		
		//Delete get elements.
		//Here can make a optimitation with the split used in obtain get...
		
		$url=preg_replace('/\/get\/.*$/', '', $url);
		
		$arr_url=explode('/', $url);
		
		//Set defaults or chosen controllers
		
		$controller='index';
		$method='home';
		$params=array();
		
		$method_path_index=2;
		
		//Checking of path url...
		
		$path_controller='';
		
		//First check urls.php
		
		$arr_url[0]=trim($arr_url[0]);

        $loaded_url=0;

        if(isset($arr_url[0]) && $arr_url[0]!='')
        {
        
            Routes::$app=Utils::slugify($arr_url[0], $respect_upper=1, $replace_space='-', $replace_dot=1, $replace_barr=1);
            
            settype(Routes::$prefix_path[Routes::$app], 'string');
            
            //Load cached urls
            
            //Load url from this module
            
            Utils::load_config('urls', Routes::$root_path.Routes::$prefix_path[Routes::$app].Routes::$app);

            foreach(Routes::$urls as $url_def => $route)
            {
            
                if(preg_match('/^'.$url_def.'$/', $url, $matches))
                {
                    
                    $c_matches=count($matches);
                    
                    $method_path_index=count($arr_url)-($c_matches);
                    
                    $index=$method_path_index;
                
                    for($x=1;$x<$c_matches;$x++)
                    {
                        
                        $arr_url[$index]=$matches[$x];
                    
                    }
                    
                    $controller=$route[0];
                    
                    $method=$route[1];
                
                    $loaded_url=1;
                    
                    //Add to cache
                    
                    break;
                
                }
            
            }
            
        }
        
        $pos_slash=strpos($controller, '/');
                    
        if($pos_slash)
        {
        
            $prefix_folder=substr($controller, 0, $pos_slash);
            
            $this->folder_controllers=$this->folder_controllers.'/'.$prefix_folder;
            
            $controller=basename($controller);
        
        }
        
        $path_controller=Routes::$root_path.Routes::$prefix_path[Routes::$app].Routes::$app.'/'.$this->folder_controllers.'/controller_'.$controller.'.php';
		
		//Search normal urls if $accept_easy_urls is true
		
		if(Routes::$accept_easy_urls && $loaded_url==0)
		{
            
            if(isset($arr_url[1]) && $arr_url[1]!='')
            {
            
                $controller=Utils::slugify($arr_url[1], $respect_upper=1, $replace_space='-', $replace_dot=1, $replace_barr=1);
                
                //Check is exists file controller, if not, use $arr_url[2] how path friends.
                
                $path_controller=Routes::$root_path.Routes::$prefix_path[Routes::$app].Routes::$app.'/'.$this->folder_controllers.'/controller_'.$controller.'.php';
                
                if(!file_exists($path_controller))
                {
                
                    if(isset($arr_url[$method_path_index]))
                    {
                    
                        $folder_controller=$controller;
                        $controller=$arr_url[$method_path_index];
                    
                        $path_controller=Routes::$root_path.Routes::$prefix_path[Routes::$app].Routes::$app.'/'.$this->folder_controllers.'/'.$folder_controller.'/controller_'.$controller.'.php';
                        
                        $method_path_index++;
                    
                    }
                
                }
                
                if(isset($arr_url[$method_path_index]))
                {
                
                    $method=Utils::slugify($arr_url[2], $respect_upper=1, $replace_space='-', $replace_dot=1, $replace_barr=1);;
                    
                }
                
            }
            
        }

		if(is_file($path_controller) && in_array(Routes::$app, $apps))
		{
			
			//Pass this route to the controller.
			
			include($path_controller);
			
			$controller_name=$controller.$this->prefix_controller.'Controller';
			
			//Check if class exists
			
			if(class_exists($controller_name) && method_exists($controller_name, $method))
			{
			
				$controller_class=new $controller_name($this, Routes::$app);
				
				$p = new \ReflectionMethod($controller_name, $method); 
						
				$num_parameters=$p->getNumberOfRequiredParameters();
				
				$num_parameters_total=count($p->getParameters());
				
				$c=$method_path_index+1;
				$x=0;
				
				$arr_values=array_slice($arr_url, $c);
				
				settype($this->arr_routes[$controller][$method], 'array');
				
				$c_param=count($arr_values);
				
				if($c_param<=$num_parameters_total && $c_param>=$num_parameters && $method!=='__construct' && $p->isPublic())
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
                        
                        throw new \Exception('Not exists method in this controller');
                    
                    }
                    else
                    {
                    
                        //Execute post tasks
                        
                        foreach($this->arr_finish_callbacks as $callback => $arguments)
                        {
                        
                            call_user_func_array($callback, $arguments);
                        
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
        
        $content=ob_get_contents();
		
		ob_end_clean();
        
        echo trim($content);
	
	}
	
	/**
	* If response fail, you can use this for response 404 page.
	*
	*/
	
	public function response404()
	{
	
		header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found"); 
		
		/*$url404=$this->make_url($this->default_404['controller'], $this->default_404['method'], $this->default_404['values']);
		
		//Use views for this thing.
		
		if(!$this->response($url404, 0))
		{*/
		
		//use a view
		
        echo 'Error: page not found...';
			
		//}
		
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
	
		//Quit shitty things how slash and quotes
		return strtr($value,'/"`', '---');
	
	}
	
	/**
	* Method for create urls for this route.
	*/
	
	static public function make_url($controller, $method='home', $values=array(), $get=array())
	{
	
		
	
		return Routes::$root_url.Routes::$base_file.'/'.Routes::$app.'/'.$controller.'/'.$method.'/'.implode('/', $values);
	
	}
	
	/**
	* Method for create urls for all routes in the site.
	*/
	
	static public function make_module_url($app, $controller, $method='home', $values=array(), $get=array())
	{
		$url_fancy=Routes::$root_url.Routes::$base_file.'/'.$app.'/'.$controller.'/'.$method.'/'.implode('/', $values);
		
		$url=Routes::add_get_parameters($url_fancy, $get);
	
		return $url;
	
	}
	
	/**
	* Method for create urls for all routes in differents sites.
	*/
	
	static public function make_direct_url($base_url, $app, $controller, $method='home', $values=array(), $get=array())
	{
	
		$url_fancy=$base_url.'/'.$app.'/'.$controller.'/'.$method.'/'.implode('/', $values);
		
		$url=Routes::add_get_parameters($url_fancy, $get);
		
		return $url;
	
	}
	
	/**
	* Method for create arbitrary urls. Is useful when use urls.php in your module.
	*/
	
	static public function make_simple_url($url_path, $values=array(), $get=array())
	{
	
        $url=Routes::$root_url.Routes::$base_file.'/'.$url_path.'/'.implode('/', $values);
        
        return Routes::add_get_parameters($url, $get);
	
	}
    
    /**
    * Alias of make_simple_url. Probably this method deprecated make_simple_url
    */
    
    static public function get_url($url_path, $values=array(), $get=array())
	{
        return Routes::make_simple_url($url_path, $values, $get);
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
	* Method for make simple redirecs using header function.
	* @param string $url The url to redirect
	*/
	
	static public function redirect($url)
	{
	
		header('Location: '.$url);
	
		die;
	
	}

}


